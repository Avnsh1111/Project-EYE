<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Models\MediaFile;
use App\Models\StorageQuota;
use App\Models\User;
use App\Services\QuotaService;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function __construct(private QuotaService $quotaService) {}

    public function users(Request $request)
    {
        $users = User::query()
            ->with('storageQuota')
            ->orderBy('id')
            ->paginate(50);

        $users->getCollection()->transform(function (User $user) {
            $usage = $this->quotaService->getUsage($user);

            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'quota' => $usage,
                'created_at' => $user->created_at,
            ];
        });

        return response()->json($users);
    }

    public function stats(Request $request)
    {
        $totalFiles = MediaFile::withoutGlobalScopes([SoftDeletingScope::class, 'user_scope'])
            ->whereNull('trashed_at')
            ->count();

        $totalStorage = MediaFile::withoutGlobalScopes([SoftDeletingScope::class, 'user_scope'])
            ->whereNull('trashed_at')
            ->sum('file_size');

        $perUser = DB::table('media_files')
            ->leftJoin('users', 'users.id', '=', 'media_files.user_id')
            ->whereNull('media_files.trashed_at')
            ->whereNull('media_files.deleted_at')
            ->selectRaw('media_files.user_id, users.name, users.email, COUNT(*) as file_count, SUM(media_files.file_size) as storage_bytes')
            ->groupBy('media_files.user_id', 'users.name', 'users.email')
            ->get();

        return response()->json([
            'total_files' => $totalFiles,
            'total_storage_bytes' => $totalStorage,
            'per_user' => $perUser,
        ]);
    }

    public function updateUserQuota(Request $request, int $id)
    {
        $request->validate([
            'quota_bytes' => 'required|integer|min:0|max:10995116277760',  // 10 TB
        ]);

        $user = User::findOrFail($id);
        $quota = StorageQuota::firstOrCreate(
            ['user_id' => $user->id],
            ['quota_bytes' => 107374182400, 'used_bytes' => 0]
        );

        $quota->update(['quota_bytes' => $request->quota_bytes]);

        return response()->json([
            'user_id' => $user->id,
            'quota_bytes' => $quota->quota_bytes,
            'used_bytes' => $quota->used_bytes,
        ]);
    }
}
