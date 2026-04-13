<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Models\MediaFile;
use Illuminate\Http\Request;

class MediaController extends Controller
{
    public function index(Request $request)
    {
        $media = MediaFile::whereNull('trashed_at')
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json($media);
    }

    public function show(Request $request, int $id)
    {
        $media = MediaFile::whereNull('trashed_at')->findOrFail($id);
        return response()->json($media);
    }

    public function destroy(Request $request, int $id)
    {
        $media = MediaFile::where('user_id', $request->user()->id)->findOrFail($id);
        $media->delete();
        return response()->json(['message' => 'Media deleted']);
    }
}
