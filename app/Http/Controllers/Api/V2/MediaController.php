<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Models\MediaFile;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Http\Request;

class MediaController extends Controller
{
    public function index(Request $request)
    {
        $media = MediaFile::withoutGlobalScope(SoftDeletingScope::class)
            ->where('user_id', $request->user()->id)
            ->whereNull('trashed_at')
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json($media);
    }

    public function show(Request $request, int $id)
    {
        $media = MediaFile::withoutGlobalScope(SoftDeletingScope::class)
            ->where('user_id', $request->user()->id)
            ->whereNull('trashed_at')
            ->findOrFail($id);
        return response()->json($media);
    }

    public function destroy(Request $request, int $id)
    {
        $media = MediaFile::withoutGlobalScope(SoftDeletingScope::class)
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);
        $media->update(['trashed_at' => now()]);
        return response()->json(['message' => 'Media moved to trash']);
    }
}
