<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BarangFotoController extends Controller
{
    public function __invoke(string $path): BinaryFileResponse
    {
        $path = str_replace('\\', '/', $path);
        $segments = explode('/', $path);
        abort_if($path === '' || str_starts_with($path, '/') || str_contains($path, "\0") || in_array('..', $segments, true) || in_array('.', $segments, true), 404);

        $disk = Storage::disk('public');
        abort_unless($disk->exists($path), 404);

        $file = $disk->path($path);
        $mime = mime_content_type($file) ?: 'application/octet-stream';
        abort_unless(in_array($mime, ['image/jpeg', 'image/png', 'image/webp', 'image/gif'], true), 404);

        return response()->file($file, [
            'Content-Type' => $mime,
            'Cache-Control' => 'private, max-age=300',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
