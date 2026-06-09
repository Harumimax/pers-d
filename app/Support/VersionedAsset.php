<?php

namespace App\Support;

final class VersionedAsset
{
    public static function url(string $path): string
    {
        $normalizedPath = ltrim($path, '/');
        $absolutePath = public_path($normalizedPath);

        if (! is_file($absolutePath)) {
            return asset($normalizedPath);
        }

        return asset($normalizedPath).'?v='.filemtime($absolutePath);
    }
}
