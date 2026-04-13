<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Models\DeviceSyncState;
use App\Models\MediaFile;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Http\Request;

class SyncController extends Controller
{
    /**
     * GET /api/v2/sync/delta?since=ISO8601
     * Returns media files created/updated after the given timestamp for the authenticated user.
     */
    public function delta(Request $request)
    {
        $request->validate([
            'since' => 'required|date',
        ]);

        $since = \Carbon\Carbon::parse($request->input('since'));

        $limit = min((int) $request->input('limit', 200), 500);
        $media = MediaFile::withoutGlobalScope(SoftDeletingScope::class)
            ->where('user_id', $request->user()->id)
            ->where(function ($query) use ($since) {
                $query->where('created_at', '>', $since)
                      ->orWhere('updated_at', '>', $since);
            })
            ->orderBy('updated_at')
            ->limit($limit + 1)
            ->get(['id', 'original_filename', 'media_type', 'file_size', 'processing_status', 'created_at', 'updated_at', 'trashed_at']);

        $hasMore = $media->count() > $limit;
        if ($hasMore) {
            $media = $media->take($limit);
        }

        return response()->json([
            'since' => $since->toIso8601String(),
            'items' => $media->values(),
            'count' => $media->count(),
            'has_more' => $hasMore,
        ]);
    }

    /**
     * POST /api/v2/sync/state
     * Upsert the device sync state for device_id + user.
     */
    public function upsertState(Request $request)
    {
        $request->validate([
            'device_id' => 'required|string|max:255',
            'last_sync_at' => 'nullable|date',
        ]);

        $state = DeviceSyncState::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'device_id' => $request->device_id,
            ],
            [
                'last_sync_at' => $request->last_sync_at ?? now(),
            ]
        );

        return response()->json([
            'device_id' => $state->device_id,
            'last_sync_at' => $state->last_sync_at,
        ], $state->wasRecentlyCreated ? 201 : 200);
    }
}
