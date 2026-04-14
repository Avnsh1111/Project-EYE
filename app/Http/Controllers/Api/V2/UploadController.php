<?php

namespace App\Http\Controllers\Api\V2;

use App\Exceptions\QuotaExceededException;
use App\Http\Controllers\Controller;
use App\Services\QuotaService;
use App\Services\ResumableUploadService;
use Illuminate\Http\Request;

class UploadController extends Controller
{
    public function __construct(
        private ResumableUploadService $uploads,
        private QuotaService $quotas,
    ) {}

    public function init(Request $request)
    {
        $data = $request->validate([
            'filename' => 'required|string|max:255',
            'total_bytes' => 'required|integer|min:1|max:10737418240', // 10 GB cap
        ]);

        $user = $request->user();

        // Quota check before creating an upload session
        try {
            $this->quotas->checkBeforeUpload($user, $data['total_bytes']);
        } catch (QuotaExceededException $e) {
            return response()->json([
                'message' => 'Storage quota exceeded.',
                'used'    => $e->used,
                'total'   => $e->total,
            ], 413);
        }

        return response()->json(
            $this->uploads->initUpload($user, $data['filename'], $data['total_bytes']),
            201
        );
    }

    public function chunk(Request $request, string $uploadId)
    {
        $data = $request->validate([
            'offset' => 'required|integer|min:0',
            'chunk'  => 'required|file',
        ]);

        $contents = file_get_contents($data['chunk']->getRealPath());

        return response()->json(
            $this->uploads->appendChunk($uploadId, (int) $data['offset'], $contents)
        );
    }

    public function finalise(Request $request, string $uploadId)
    {
        $mediaFile = $this->uploads->finalise($uploadId);

        // Increment quota after successful finalise
        $this->quotas->increment($request->user(), (int) $mediaFile->file_size);

        return response()->json($mediaFile, 201);
    }
}
