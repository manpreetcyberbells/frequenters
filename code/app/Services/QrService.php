<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Log;

class QrService
{
    /**
     * Generate QR SVG file on disk (only if not already exists).
     * Returns the storage-relative path.
     */
    public function generateAndStore(array $payload, string $folder): string
    {
        $encryptedPayload = Crypt::encryptString(json_encode($payload));
        $safeFilename     = hash('sha256', $encryptedPayload);
        $path             = $folder . '/' . $safeFilename . '.svg';

        Log::info('QR Path:', ['path' => $path]);

        if (!Storage::disk('public')->exists($path)) {
            Storage::disk('public')->put(
                $path,
                QrCode::format('svg')->size(300)->generate($encryptedPayload)
            );
        }

        Log::info('Full URL:', ['url' => asset('storage/' . $path)]);

        return $path; // Caller does: asset('storage/' . $path)
    }

    /**
     * Generate QR as base64 string (for API responses).
     */
    public function generateBase64(array $payload): string
    {
        $encryptedPayload = Crypt::encryptString(json_encode($payload));
        return base64_encode(QrCode::size(300)->generate($encryptedPayload));
    }

    /**
     * Encrypt payload only (when you need the string for storage/response).
     */
    public function encryptPayload(array $payload): string
    {
        return Crypt::encryptString(json_encode($payload));
    }
}
