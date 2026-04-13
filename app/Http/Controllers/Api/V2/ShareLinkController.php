<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Services\ShareLinkService;
use App\Exceptions\ShareLinkException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ShareLinkController extends Controller
{
    public function __construct(private ShareLinkService $shareLinkService) {}

    public function store(Request $request)
    {
        $request->validate([
            'media_file_id' => [
                'required', 'integer',
                Rule::exists('media_files', 'id')->where('user_id', $request->user()->id),
            ],
            'expires_at' => 'nullable|date|after:now',
            'password' => 'nullable|string|min:4',
            'max_views' => 'nullable|integer|min:1',
        ]);

        $link = $this->shareLinkService->create([
            'user_id' => $request->user()->id,
            'media_file_id' => $request->media_file_id,
            'expires_at' => $request->expires_at,
            'password' => $request->password,
            'max_views' => $request->max_views,
        ]);

        return response()->json($link, 201);
    }

    public function destroy(Request $request, string $token)
    {
        try {
            $this->shareLinkService->revoke($token, $request->user()->id);
            return response()->json(['message' => 'Share link revoked']);
        } catch (ShareLinkException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }

    public function show(Request $request, string $token)
    {
        try {
            $link = $this->shareLinkService->validate($token, $request->query('password'));
            return response()->json(['media_file_id' => $link->media_file_id]);
        } catch (ShareLinkException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }
    }
}
