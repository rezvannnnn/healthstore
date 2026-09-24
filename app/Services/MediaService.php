<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class MediaService
{
    public const DISK = 'public';

    public function storeImage(UploadedFile $file, string $directory): string
    {
        return $file->store($directory, self::DISK);
    }

    public function url(?string $path): ?string
    {
        if ($path === null || trim($path) === '') {
            return null;
        }

        $path = trim($path);

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        if (str_starts_with($path, '/')) {
            return url(ltrim($path, '/'));
        }

        if (str_starts_with($path, 'storage/')) {
            return url($path);
        }

        if (Storage::disk(self::DISK)->exists($path)) {
            return Storage::disk(self::DISK)->url($path);
        }

        return url($path);
    }

    public function deleteIfStored(?string $path): void
    {
        if ($path === null || trim($path) === '') {
            return;
        }

        $path = trim($path);

        if (
            str_starts_with($path, 'http://') ||
            str_starts_with($path, 'https://') ||
            str_starts_with($path, '/')
        ) {
            return;
        }

        if (Storage::disk(self::DISK)->exists($path)) {
            Storage::disk(self::DISK)->delete($path);
        }
    }
}
