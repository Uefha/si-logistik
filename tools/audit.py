#!/usr/bin/env python3
"""Audit pra-serah (5 langkah baku + pemeriksaan tambahan) terhadap pohon proyek gabungan."""
import os, re, subprocess, sys, glob

ROOT = sys.argv[1]
errors, notes = [], []
def err(m): errors.append(m)
def rel(p): return os.path.relpath(p, ROOT)

php_files = [p for p in glob.glob(ROOT + '/**/*.php', recursive=True) if '/vendor/' not in p and '/node_modules/' not in p]
blade_files = [p for p in glob.glob(ROOT + '/resources/views/**/*.blade.php', recursive=True)]

# 1. php -l
bad = 0
for p in php_files:
    r = subprocess.run(['php', '-l', p], capture_output=True, text=True)
    if r.returncode != 0:
        bad += 1; err('LINT %s: %s' % (rel(p), (r.stdout + r.stderr).strip()))
notes.append('1. php -l: %d berkas PHP diperiksa, %d gagal' % (len(php_files), bad))

# 2. keseimbangan direktif Blade
PAIRS = [('if', 'endif'), ('foreach', 'endforeach'), ('for', 'endfor'), ('while', 'endwhile'),
         ('forelse', 'endforelse'), ('isset', 'endisset'), ('empty', 'endempty'), ('unless', 'endunless'),
         ('auth', 'endauth'), ('guest', 'endguest'), ('error', 'enderror'), ('push', 'endpush'),
         ('section', 'endsection'), ('switch', 'endswitch'), ('php', 'endphp'), ('can', 'endcan'),
         ('env', 'endenv'), ('production', 'endproduction'), ('once', 'endonce'), ('props', None)]
unbalanced = 0
for p in blade_files:
    t = open(p, encoding='utf-8').read()
    t_nocomments = re.sub(r'\{\{--.*?--\}\}', '', t, flags=re.S)
    for a, b in PAIRS:
        if b is None: continue
        # @php( ... ) inline tidak dihitung sebagai blok
        if a == 'php':
            n_open = len(re.findall(r'@php\b(?!\s*\()', t_nocomments))
        else:
            n_open = len(re.findall(r'(?<![\w@])@%s\b(?!\w)' % a, t_nocomments))
        n_close = len(re.findall(r'(?<![\w@])@%s\b' % b, t_nocomments))
        if n_open != n_close:
            unbalanced += 1; err('BLADE %s: @%s=%d vs @%s=%d' % (rel(p), a, n_open, b, n_close))
notes.append('2. Keseimbangan direktif Blade: %d berkas, %d tidak seimbang' % (len(blade_files), unbalanced))

# 3. @include dan <x-component>
missing = 0
def view_exists(name):
    base = os.path.join(ROOT, 'resources/views', name.replace('.', '/'))
    return os.path.exists(base + '.blade.php')
def component_exists(tag):
    path = tag.replace('.', '/')
    c1 = os.path.join(ROOT, 'resources/views/components', path + '.blade.php')
    c2 = os.path.join(ROOT, 'resources/views/components', path, 'index.blade.php')
    cls = ''.join(w.capitalize() for w in re.split(r'[-.]', tag))
    c3 = os.path.join(ROOT, 'app/View/Components', cls + '.php')
    return os.path.exists(c1) or os.path.exists(c2) or os.path.exists(c3)
n_inc = n_comp = 0
for p in blade_files:
    t = open(p, encoding='utf-8').read()
    for m in re.finditer(r"@include(?:If|When|Unless|First)?\(\s*['\"]([^'\"]+)['\"]", t):
        n_inc += 1
        if not view_exists(m.group(1)): missing += 1; err("INCLUDE %s -> view '%s' tidak ada" % (rel(p), m.group(1)))
    for m in re.finditer(r'<x-([a-z0-9][a-z0-9\.\-]*)', t):
        tag = m.group(1)
        if tag.startswith('slot') or tag.startswith('dynamic-component'): continue
        n_comp += 1
        if not component_exists(tag): missing += 1; err("KOMPONEN %s -> <x-%s> tidak ada" % (rel(p), tag))
notes.append('3. @include (%d) dan <x-...> (%d): %d referensi hilang' % (n_inc, n_comp, missing))

# 4. route('...') terhadap nama rute terdaftar
RESOURCE_ACTIONS = ['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']
names = set()
route_controllers = []   # (berkas, kelas, metode)

def parse_routes(path):
    # Mengembalikan nama rute (termasuk hasil Route::resource dan awalan group ->name('x.')) dan referensi controller.
    lines = open(path, encoding='utf-8').read().split('\n')
    out = set(); prefix_stack = []
    uses = {}
    for ln in lines:
        m = re.match(r"use\s+([\w\\]+);", ln)
        if m: uses[m.group(1).split('\\')[-1]] = m.group(1)
    text = '\n'.join(lines)
    # tangani per pernyataan: potong di ';' tingkat atas sederhana
    i = 0
    stack = []   # (kedalaman_kurung_kurawal, prefix)
    depth = 0
    cur = ''
    for ch in text:
        cur += ch
        if ch == '{':
            depth += 1
            m = re.search(r"->name\(\s*['\"]([^'\"]*)['\"]\s*\)->group\(function", cur)
            stack.append((depth, m.group(1) if m else ''))
            cur = ''
        elif ch == '}':
            if stack and stack[-1][0] == depth: stack.pop()
            depth -= 1
            cur = ''
        elif ch == ';':
            pref = ''.join(p for _, p in stack)
            stmt = cur
            for mm in re.finditer(r"->name\(\s*['\"]([^'\"]+)['\"]\s*\)", stmt):
                if '->group(' in stmt: continue
                out.add(pref + mm.group(1))
            r = re.search(r"Route::resource\(\s*['\"]([\w\-]+)['\"]\s*,\s*(\w+)::class\s*\)", stmt)
            if r:
                seg, ctrl = r.group(1), r.group(2)
                acts = list(RESOURCE_ACTIONS)
                only = re.search(r"->only\(\[([^\]]*)\]\)", stmt)
                exc = re.search(r"->except\(\[([^\]]*)\]\)", stmt)
                if only: acts = [a for a in acts if a in re.findall(r"['\"](\w+)['\"]", only.group(1))]
                if exc: acts = [a for a in acts if a not in re.findall(r"['\"](\w+)['\"]", exc.group(1))]
                for a in acts:
                    out.add(pref + seg + '.' + a)
                    route_controllers.append((path, uses.get(ctrl, ctrl), a))
            for g in re.finditer(r"\[\s*(\w+)::class\s*,\s*['\"](\w+)['\"]\s*\]", stmt):
                route_controllers.append((path, uses.get(g.group(1), g.group(1)), g.group(2)))
            for g in re.finditer(r"Route::(?:get|post|put|patch|delete)\([^;]*?,\s*(\w+)::class\s*\)", stmt):
                route_controllers.append((path, uses.get(g.group(1), g.group(1)), '__invoke'))
            cur = ''
    return out

for rf in glob.glob(ROOT + '/routes/*.php'):
    names |= parse_routes(rf)
bad_routes = 0; n_route = 0
scan = blade_files + [p for p in php_files if '/app/' in p or '/tests/' in p]
for p in scan:
    t = open(p, encoding='utf-8').read()
    for m in re.finditer(r"(?<![\w>:])route\(\s*['\"]([^'\"]+)['\"]", t):
        n_route += 1
        if m.group(1) not in names: bad_routes += 1; err("ROUTE %s -> route('%s') tidak terdaftar" % (rel(p), m.group(1)))
    for m in re.finditer(r"routeIs\(\s*['\"]([^'\"]+)['\"]", t):
        pat = m.group(1)
        if not any(re.fullmatch(re.escape(pat).replace(r'\*', '.*'), n) for n in names):
            bad_routes += 1; err("ROUTEIS %s -> routeIs('%s') tidak cocok dengan rute apa pun" % (rel(p), pat))
notes.append('4. route()/routeIs(): %d pemanggilan, %d tidak cocok (rute terdaftar: %s)' % (n_route, bad_routes, ', '.join(sorted(names))))

# 4b. setiap [Controller::class, 'metode'] / resource action benar-benar ada
bad_ctrl = 0
for (rf, kelas, metode) in route_controllers:
    f = class_file_for(kelas) if 'class_file_for' in globals() else None
    if kelas.startswith('App\\'):
        f = os.path.join(ROOT, 'app', kelas[4:].replace('\\', '/') + '.php')
    if not f or not os.path.exists(f):
        bad_ctrl += 1; err("CONTROLLER %s -> %s tidak ditemukan" % (rel(rf), kelas)); continue
    src = open(f, encoding='utf-8').read()
    if not re.search(r"function\s+%s\s*\(" % re.escape(metode), src):
        # metode bisa diwarisi dari kelas dasar di folder yang sama
        bad_ctrl += 1; err("CONTROLLER %s -> %s::%s() tidak ada" % (rel(rf), kelas.split('\\')[-1], metode))
notes.append('4b. Rute -> metode controller: %d rujukan, %d tidak ada' % (len(route_controllers), bad_ctrl))

# 5. view('...') di controller
bad_views = 0; n_view = 0
for p in php_files:
    if '/app/' not in p: continue
    t = open(p, encoding='utf-8').read()
    for m in re.finditer(r"(?<![\w>:])view\(\s*['\"]([^'\"]+)['\"]", t):
        n_view += 1
        if not view_exists(m.group(1)): bad_views += 1; err("VIEW %s -> view('%s') tidak ada" % (rel(p), m.group(1)))
notes.append("5. view(): %d pemanggilan, %d berkas view hilang" % (n_view, bad_views))

# Tambahan A: class pada rute dan use statement ada di pohon proyek
bad_cls = 0
def class_file(fqcn):
    if fqcn.startswith('App\\'): return os.path.join(ROOT, 'app', fqcn[4:].replace('\\', '/') + '.php')
    if fqcn.startswith('Database\\Seeders\\'): return os.path.join(ROOT, 'database/seeders', fqcn[len('Database\\Seeders\\'):].replace('\\', '/') + '.php')
    if fqcn.startswith('Database\\Factories\\'): return os.path.join(ROOT, 'database/factories', fqcn[len('Database\\Factories\\'):].replace('\\', '/') + '.php')
    if fqcn.startswith('Tests\\'): return os.path.join(ROOT, 'tests', fqcn[6:].replace('\\', '/') + '.php')
    return None
n_use = 0
for p in php_files:
    t = open(p, encoding='utf-8').read()
    for m in re.finditer(r'^use\s+((?:App|Database|Tests)\\[A-Za-z0-9_\\]+);', t, flags=re.M):
        n_use += 1
        f = class_file(m.group(1))
        if f and not os.path.exists(f): bad_cls += 1; err('USE %s -> %s tidak ada' % (rel(p), m.group(1)))
notes.append('A. use App/Database/Tests: %d statement, %d kelas tidak ditemukan' % (n_use, bad_cls))

# Tambahan B: timestamp migration unik dan berurutan; setiap migration punya up() dan down()
migs = sorted(glob.glob(ROOT + '/database/migrations/*.php'))
stamps = [os.path.basename(m)[:17] for m in migs]
if len(set(stamps)) != len(stamps): err('MIGRATION timestamp ganda')
for m in migs:
    t = open(m, encoding='utf-8').read()
    if 'function up()' not in t or 'function down()' not in t: err('MIGRATION %s tanpa up()/down()' % rel(m))
notes.append('B. Migration: %d berkas, timestamp unik, up()/down() lengkap' % len(migs))

# Tambahan C: mass assignment / input mentah di controller (tidak boleh $request->all())
for p in php_files:
    if '/app/Http/Controllers/' in p and '$request->all()' in open(p, encoding='utf-8').read():
        err('KEAMANAN %s memakai $request->all()' % rel(p))
notes.append('C. Tidak ada $request->all() di controller')

print('\n'.join(notes))
print()
if errors:
    print('TEMUAN (%d):' % len(errors)); print('\n'.join(' - ' + e for e in errors)); sys.exit(1)
print('AUDIT LULUS: 0 temuan')
