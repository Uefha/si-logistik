<?php

namespace App\Http\Controllers;

use App\Models\AktivitasLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(): View
    {
        return view('audit-log.index', [
            'petugas' => User::orderBy('name')->get(['id', 'name']),
            'modulList' => AktivitasLog::query()->distinct()->orderBy('modul')->pluck('modul'),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $filter = $request->validate([
            'dari' => ['nullable', 'date'], 'sampai' => ['nullable', 'date', 'after_or_equal:dari'],
            'modul' => ['nullable', 'string', 'max:50'], 'user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);
        $draw = max(0, (int) $request->input('draw', 0));
        $start = max(0, (int) $request->input('start', 0));
        $length = min(100, max(10, (int) $request->input('length', 10)));
        $query = AktivitasLog::query()->with('user')->select(['id', 'user_id', 'aktivitas', 'modul', 'subjek_type', 'subjek_id', 'data', 'ip_address', 'created_at']);
        $recordsTotal = (clone $query)->count();
        $query->when($filter['dari'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
            ->when($filter['sampai'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '<=', $date))
            ->when($filter['modul'] ?? null, fn ($q, $module) => $q->where('modul', $module))
            ->when($filter['user_id'] ?? null, fn ($q, $id) => $q->where('user_id', $id));
        $search = trim((string) $request->input('search.value', ''));
        if ($search !== '') {
            $like = '%'.addcslashes($search, '\\%_').'%';
            $query->where(fn ($q) => $q->where('aktivitas', 'like', $like)->orWhere('modul', 'like', $like)->orWhere('ip_address', 'like', $like)->orWhereHas('user', fn ($user) => $user->where('name', 'like', $like)));
        }
        $recordsFiltered = (clone $query)->count();
        $sorts = ['created_at', 'user_id', 'modul', 'aktivitas', 'subjek_id', null, 'ip_address'];
        $sortIndex = (int) $request->input('order.0.column', 0);
        $sort = $sorts[$sortIndex] ?? 'created_at';
        $direction = $request->input('order.0.dir') === 'asc' ? 'asc' : 'desc';
        $rows = $query->orderBy($sort ?? 'created_at', $direction)->orderBy('id', 'desc')->skip($start)->take($length)->get();

        return response()->json([
            'draw' => $draw, 'recordsTotal' => $recordsTotal, 'recordsFiltered' => $recordsFiltered,
            'data' => $rows->map(function (AktivitasLog $log) {
                $esc = fn ($value) => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

                return [
                    'waktu' => $esc($log->created_at?->format('d-m-Y H:i:s')), 'petugas' => $esc($log->user?->name ?? 'Sistem'),
                    'modul' => $esc($log->modul), 'aktivitas' => $esc($log->aktivitas), 'subjek' => $esc(trim(class_basename((string) $log->subjek_type).' #'.($log->subjek_id ?? ''), ' #')),
                    'ip' => $esc($log->ip_address ?? '—'), 'detail' => $esc($this->ringkasan($log->data)),
                ];
            }),
        ]);
    }

    /** Ubah data audit terstruktur menjadi kalimat pendek tanpa sintaks JSON. */
    private function ringkasan(?array $data): string
    {
        if (!$data) {
            return 'Tidak ada detail';
        }

        $bagian = [];
        foreach ($data as $kunci => $nilai) {
            if (is_array($nilai)) {
                $rincian = [];
                foreach ($nilai as $nama => $isi) {
                    $label = mb_strtolower(str_replace('_', ' ', \Illuminate\Support\Str::headline((string) $nama)));
                    if (is_array($isi) && array_key_exists('dari', $isi) && array_key_exists('menjadi', $isi)) {
                        $rincian[] = ucfirst($label).' berubah dari '.$this->teks($isi['dari']).' menjadi '.$this->teks($isi['menjadi']);
                    } elseif (is_array($isi)) {
                        $rincian[] = ucfirst($label).': '.$this->ringkasan($isi);
                    } else {
                        $rincian[] = ucfirst($label).' '.$this->teks($isi);
                    }
                }
                $bagian[] = ucfirst(mb_strtolower(str_replace('_', ' ', \Illuminate\Support\Str::headline((string) $kunci))).': '.implode('. ', $rincian));
                continue;
            }

            $label = mb_strtolower(str_replace('_', ' ', \Illuminate\Support\Str::headline((string) $kunci)));
            $bagian[] = ucfirst($label).': '.$this->teks($nilai);
        }

        return implode('. ', $bagian).'.';
    }

    private function teks(mixed $nilai): string
    {
        return match (true) {
            $nilai === null, $nilai === '' => 'kosong',
            $nilai === true => 'ya',
            $nilai === false => 'tidak',
            default => trim((string) $nilai),
        };
    }
}
