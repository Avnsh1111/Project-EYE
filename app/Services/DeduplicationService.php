<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class DeduplicationService
{
    public function hashFile(string $path): string
    {
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
