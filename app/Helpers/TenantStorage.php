<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Storage;

/**
 * TenantStorage — resolves upload paths for both SaaS (per-tenant) and
 * single-tenant (legacy public/ paths) modes.
 *
 * Usage:
 *   $path = TenantStorage::upload($file, 'images/product');
 *   $url  = TenantStorage::url('images/product', $filename);
 */
class TenantStorage
{
    /** Whether the app is running in SaaS (stancl/tenancy) mode. */
    public static function isSaaS(): bool
    {
        return (bool) config('database.connections.saleprosaas_landlord');
    }

    /**
     * Store an uploaded file and return the stored filename.
     *
     * In SaaS mode  → Storage::disk('public')->putFileAs($subdir, $file, $name)
     *                  which maps to  storage/app/public/tenant_{id}/$subdir/$name
     * In legacy mode → public_path($subdir) directly (existing behaviour)
     */
    public static function upload(\Illuminate\Http\UploadedFile $file, string $subdir, ?string $name = null): string
    {
        $name = $name ?? (time() . '.' . $file->getClientOriginalExtension());

        if (static::isSaaS()) {
            Storage::disk('public')->putFileAs($subdir, $file, $name);
        } else {
            $dest = public_path($subdir);
            if (!is_dir($dest)) mkdir($dest, 0755, true);
            $file->move($dest, $name);
        }

        return $name;
    }

    /**
     * Delete a file by name from a subdirectory.
     */
    public static function delete(string $subdir, string $filename): void
    {
        if (static::isSaaS()) {
            Storage::disk('public')->delete($subdir . '/' . $filename);
        } else {
            $path = public_path($subdir . '/' . $filename);
            if (file_exists($path)) @unlink($path);
        }
    }

    /**
     * Get the public URL for a stored file.
     */
    public static function url(string $subdir, string $filename): string
    {
        if (static::isSaaS()) {
            return Storage::disk('public')->url($subdir . '/' . $filename);
        }

        return url($subdir . '/' . $filename);
    }

    /**
     * Get the absolute filesystem path for a stored file (for reading).
     */
    public static function path(string $subdir, string $filename): string
    {
        if (static::isSaaS()) {
            return Storage::disk('public')->path($subdir . '/' . $filename);
        }

        return public_path($subdir . '/' . $filename);
    }

    /**
     * Check if a file exists.
     */
    public static function exists(string $subdir, string $filename): bool
    {
        if (static::isSaaS()) {
            return Storage::disk('public')->exists($subdir . '/' . $filename);
        }

        return file_exists(public_path($subdir . '/' . $filename));
    }
}
