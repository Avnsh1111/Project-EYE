<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class DeduplicationService
{
    public function hashFile(string $path): string
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new \RuntimeException("Cannot read file for hashing: {$path}");
        }
        return hash_file('sha256', $path);
    }

    public function isDuplicate(string $hash, int $userId): bool
    {
        return DB::table('media_files')
            ->where('file_hash', $hash)
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->exists();
    }
}
