<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class MediaService
{
    public const DISK = 'public';

    public function storeImage(UploadedFile $file, string $directory): string
    {
        $path = $file->store($directory, self::DISK);

        if ($path === false) {
            throw new RuntimeException('ذخیره تصویر با خطا مواجه شد.');
        }

        return $path;
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

        foreach (['products/', 'brands/', 'categories/', 'articles/'] as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return Storage::disk(self::DISK)->url($path);
            }
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

        foreach (['products/', 'brands/', 'categories/', 'articles/'] as $prefix) {
            if (str_starts_with($path, $prefix)) {
                Storage::disk(self::DISK)->delete($path);

                return;
            }
        }
    }
}
