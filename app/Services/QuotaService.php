<?php

namespace App\Services;

use App\Exceptions\QuotaExceededException;
use App\Models\StorageQuota;
use App\Models\User;

class QuotaService
{
    public function getUsage(User $user): array
    {
        $quota = $this->getOrCreateQuota($user);

        return [
            'used'    => $quota->used_bytes,
            'total'   => $quota->quota_bytes,
            'percent' => $quota->quota_bytes > 0
                ? round(($quota->used_bytes / $quota->quota_bytes) * 100, 1)
                : 0.0,
        ];
    }

    public function checkBeforeUpload(User $user, int $bytes): void
    {
        $quota = $this->getOrCreateQuota($user);

        if ($quota->used_bytes + $bytes > $quota->quota_bytes) {
            throw new QuotaExceededException($quota->used_bytes, $quota->quota_bytes);
        }
    }

    public function increment(User $user, int $bytes): void
    {
        $quota = $this->getOrCreateQuota($user);
        $quota->increment('used_bytes', $bytes);
    }

    public function decrement(User $user, int $bytes): void
    {
        $quota = $this->getOrCreateQuota($user);
        StorageQuota::where('id', $quota->id)
            ->update(['used_bytes' => \Illuminate\Support\Facades\DB::raw('GREATEST(0, used_bytes - ' . (int) $bytes . ')')]);
    }

    private function getOrCreateQuota(User $user): StorageQuota
    {
        return StorageQuota::firstOrCreate(
            ['user_id' => $user->id],
            ['quota_bytes' => 107374182400, 'used_bytes' => 0] // 100 GB default
        );
    }
}
