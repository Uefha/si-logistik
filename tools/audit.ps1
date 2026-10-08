param([string] $ProjectRoot = (Split-Path -Parent $PSScriptRoot))

$ErrorActionPreference = 'Stop'
$root = (Resolve-Path $ProjectRoot).Path
$viewRoot = Join-Path $root 'resources/views'
$bladeFiles = @(Get-ChildItem $viewRoot -Recurse -Filter *.blade.php)
$phpFiles = @(Get-ChildItem $root -Recurse -Filter *.php | Where-Object {
    $_.FullName -notmatch '[\\/](vendor|node_modules|storage)[\\/]'
})
$issues = [System.Collections.Generic.List[string]]::new()

foreach ($file in $phpFiles) {
    $result = & php -l $file.FullName 2>&1
    if ($LASTEXITCODE -ne 0) { $issues.Add("PHP lint: $($file.FullName): $result") }
}

$pairs = @(
    @('if', 'endif'), @('foreach', 'endforeach'), @('for', 'endfor'), @('while', 'endwhile'),
    @('forelse', 'endforelse'), @('isset', 'endisset'), @('empty', 'endempty'), @('unless', 'endunless'),
    @('auth', 'endauth'), @('guest', 'endguest'), @('error', 'enderror'), @('push', 'endpush'),
    @('section', 'endsection'), @('switch', 'endswitch'), @('php', 'endphp'), @('can', 'endcan'),
    @('canany', 'endcanany'), @('hasSection', 'endhasSection'), @('production', 'endproduction'),
    @('once', 'endonce'), @('fragment', 'endfragment')
)

function Test-View([string] $name) {
    $path = Join-Path $viewRoot (($name.Replace('.', [IO.Path]::DirectorySeparatorChar)) + '.blade.php')
    return Test-Path $path
}

function Test-Component([string] $name) {
    $path = $name.Replace('.', [IO.Path]::DirectorySeparatorChar)
    $candidate = Join-Path (Join-Path $viewRoot 'components') ($path + '.blade.php')
    $index = Join-Path (Join-Path $viewRoot 'components') (Join-Path $path 'index.blade.php')
    return (Test-Path $candidate) -or (Test-Path $index)
}

$directiveErrors = 0
$includeCount = 0
$componentCount = 0
foreach ($file in $bladeFiles) {
    $content = Get-Content -Raw $file.FullName
    $content = [regex]::Replace($content, '\{\{--.*?--\}\}', '', 'Singleline')
    foreach ($pair in $pairs) {
        $opening = $pair[0]
        $closing = $pair[1]
        if ($opening -eq 'php') {
            $openCount = [regex]::Matches($content, '@php\b(?!\s*\()').Count
        } else {
            $openCount = [regex]::Matches($content, "(?<![\w@])@$opening\b(?!\w)").Count
        }
        if ($opening -eq 'empty') {
            # @empty juga merupakan cabang @forelse dan ditutup oleh @endforelse.
            $forelseBranches = [regex]::Matches($content, '(?<![\w@])@forelse\b').Count
            $openCount -= [Math]::Min($openCount, $forelseBranches)
        }
        $closeCount = [regex]::Matches($content, "(?<![\w@])@$closing\b").Count
        if ($openCount -ne $closeCount) {
            $directiveErrors++
            $issues.Add("Blade directive $($file.FullName): @$opening=$openCount @$closing=$closeCount")
        }
    }
    foreach ($match in [regex]::Matches($content, '@include(?:If|When|Unless|First)?\(\s*[\x27"]([^\x27"]+)[\x27"]')) {
        $includeCount++
        if (-not (Test-View $match.Groups[1].Value)) { $issues.Add("Missing include $($match.Groups[1].Value) in $($file.FullName)") }
    }
    foreach ($match in [regex]::Matches($content, '<x-([a-z0-9][a-z0-9.\-]*)')) {
        $tag = $match.Groups[1].Value
        if ($tag -in @('slot', 'dynamic-component')) { continue }
        $componentCount++
        if (-not (Test-Component $tag)) { $issues.Add("Missing component <x-$tag> in $($file.FullName)") }
    }
}

$routes = @(& php (Join-Path $root 'artisan') route:list --json | ConvertFrom-Json)
$routeNames = @($routes | ForEach-Object { $_.name } | Where-Object { $_ })
$routeReferences = 0
$routeErrors = 0
$scanFiles = @($bladeFiles) + @($phpFiles | Where-Object { $_.FullName -match '[\\/]app[\\/]Http[\\/]Controllers[\\/]' })
foreach ($file in $scanFiles) {
    $content = Get-Content -Raw $file.FullName
    foreach ($match in [regex]::Matches($content, '(?<![\w>:])route\(\s*[\x27"]([^\x27"]+)[\x27"]')) {
        $routeReferences++
        if ($match.Groups[1].Value -notin $routeNames) {
            $routeErrors++
            $issues.Add("Unregistered route $($match.Groups[1].Value) in $($file.FullName)")
        }
    }
    foreach ($match in [regex]::Matches($content, 'routeIs\(\s*[\x27"]([^\x27"]+)[\x27"]')) {
        $pattern = '^' + [regex]::Escape($match.Groups[1].Value).Replace('\*', '.*') + '$'
        if (-not ($routeNames | Where-Object { $_ -match $pattern })) {
            $routeErrors++
            $issues.Add("Unmatched routeIs $($match.Groups[1].Value) in $($file.FullName)")
        }
    }
}

$viewReferences = 0
foreach ($file in $phpFiles | Where-Object { $_.FullName -match '[\\/]app[\\/]' }) {
    $content = Get-Content -Raw $file.FullName
    foreach ($match in [regex]::Matches($content, '(?<![\w>:])view\(\s*[\x27"]([^\x27"]+)[\x27"]')) {
        $viewReferences++
        if (-not (Test-View $match.Groups[1].Value)) { $issues.Add("Missing view $($match.Groups[1].Value) in $($file.FullName)") }
    }
}

$controllerErrors = 0
foreach ($route in $routes) {
    if ($route.action -match '^([^@]+)@([^@]+)$' -and $Matches[1] -like 'App\*') {
        $classPath = $Matches[1].Substring(4).Replace('\', [IO.Path]::DirectorySeparatorChar) + '.php'
        $controllerFile = Join-Path (Join-Path $root 'app') $classPath
        if (-not (Test-Path $controllerFile)) {
            $controllerErrors++
            $issues.Add("Missing route controller $($route.action)")
        } elseif (-not (Select-String -Path $controllerFile -Pattern "function\s+$([regex]::Escape($Matches[2]))\s*\(" -Quiet)) {
            $controllerErrors++
            $issues.Add("Missing route method $($route.action)")
        }
    }
}

Write-Output "PHP lint: $($phpFiles.Count) berkas; Blade: $($bladeFiles.Count); include: $includeCount; komponen: $componentCount"
Write-Output "Rute: $($routeNames.Count) nama; route() $routeReferences; view() $viewReferences"
if ($issues.Count) {
    Write-Output "AUDIT GAGAL: $($issues.Count) temuan"
    $issues | ForEach-Object { Write-Output " - $_" }
    exit 1
}
Write-Output 'AUDIT LULUS: 0 temuan'
