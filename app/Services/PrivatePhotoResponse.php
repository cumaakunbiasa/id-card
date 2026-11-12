<?php

namespace App\Services;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class PrivatePhotoResponse
{
    /**
     * Decode a privately stored upload and re-encode it as a browser-safe PNG.
     * Resizing keeps memory use bounded for large source photos.
     */
    public function make(string $path): Response
    {
        $disk = Storage::disk('local');

        abort_unless($disk->exists($path), 404);
        abort_unless(
            function_exists('imagecreatefromstring')
                && function_exists('imagepng')
                && function_exists('imagecopyresampled'),
            500,
            'Ekstensi GD PHP diperlukan untuk memproses foto.'
        );

        $source = @imagecreatefromstring($disk->get($path));
        abort_unless($source instanceof \GdImage, 404, 'File foto tidak dapat dibaca.');

        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        $maxDimension = 1800;
        $scale = min(1, $maxDimension / max($sourceWidth, $sourceHeight));
        $targetWidth = max(1, (int) round($sourceWidth * $scale));
        $targetHeight = max(1, (int) round($sourceHeight * $scale));

        $normalized = imagecreatetruecolor($targetWidth, $targetHeight);
        $white = imagecolorallocate($normalized, 255, 255, 255);
        imagefill($normalized, 0, 0, $white);

        imagecopyresampled(
            $normalized,
            $source,
            0,
            0,
            0,
            0,
            $targetWidth,
            $targetHeight,
            $sourceWidth,
            $sourceHeight
        );

        // Write to a temporary file instead of PHP output buffering.
        // This prevents stray output bytes from corrupting the PNG signature.
        $temporaryPath = tempnam(sys_get_temp_dir(), 'private-photo-');
        abort_unless(is_string($temporaryPath), 500, 'Tidak dapat menyiapkan file foto sementara.');

        $encoded = imagepng($normalized, $temporaryPath, 6);
        $png = $encoded ? file_get_contents($temporaryPath) : false;

        @unlink($temporaryPath);
        imagedestroy($source);
        imagedestroy($normalized);

        abort_unless(
            $encoded
                && is_string($png)
                && str_starts_with($png, "\x89PNG\r\n\x1A\n")
                && str_ends_with($png, "\x00\x00\x00\x00IEND\xAE\x42\x60\x82"),
            500,
            'Foto gagal diproses menjadi PNG yang valid.'
        );

        return response($png, 200, [
            'Content-Type' => 'image/png',
            'Content-Length' => (string) strlen($png),
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
