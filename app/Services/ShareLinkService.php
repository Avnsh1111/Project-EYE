<?php

namespace App\Services;

use App\Exceptions\ShareLinkException;
use App\Models\ShareLink;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ShareLinkService
{
    public function create(array $params): ShareLink
    {
        $passwordHash = null;
        if (!empty($params['password'])) {
            $passwordHash = Hash::make($params['password']);
        }

        try {
            $shareLink = ShareLink::create([
                'user_id'       => $params['user_id'],
                'media_file_id' => $params['media_file_id'],
                'token'         => Str::random(64),
                'password_hash' => $passwordHash,
                'expires_at'    => $params['expires_at'] ?? null,
                'max_views'     => $params['max_views'] ?? null,
                'view_count'    => 0,
                'is_active'     => true,
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            // Postgres unique violation SQLSTATE 23505 / generic duplicate entry
            if (str_contains($e->getMessage(), '23505') || str_contains($e->getMessage(), 'Duplicate entry') || str_contains($e->getMessage(), 'UNIQUE constraint')) {
                throw new \App\Exceptions\ShareLinkException('Token collision occurred. Please retry.');
            }
            throw $e;
        }

        return $shareLink;
    }

    public function validate(string $token, ?string $password): ShareLink
    {
        $link = ShareLink::where('token', $token)->first();

        if (!$link || !$link->is_active) {
            throw new ShareLinkException('Share link not found or inactive.');
        }

        if ($link->expires_at && $link->expires_at->isPast()) {
            throw new ShareLinkException('Share link has expired.');
        }

        if ($link->password_hash && !Hash::check($password ?? '', $link->password_hash)) {
            throw new ShareLinkException('Invalid password.');
        }

        if ($link->max_views !== null) {
            $updated = ShareLink::where('id', $link->id)
                ->where('is_active', true)
                ->whereRaw('view_count < max_views')
                ->increment('view_count');
            if (!$updated) {
                throw new ShareLinkException('Share link view limit reached.');
            }
        } else {
            $incremented = ShareLink::where('id', $link->id)
                ->where('is_active', true)
                ->increment('view_count');
            if (!$incremented) {
                throw new ShareLinkException('Share link not found or inactive.');
            }
        }
        $link->refresh();

        return $link;
    }

    public function revoke(string $token, int $userId): void
    {
        $affected = ShareLink::where('token', $token)
            ->where('user_id', $userId)
            ->update(['is_active' => false]);

        if ($affected === 0) {
            throw new ShareLinkException('Share link not found or does not belong to this user.');
        }
    }
}
