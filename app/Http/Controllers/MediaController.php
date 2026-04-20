<?php

namespace App\Http\Controllers;

use App\Models\MediaFile;
use App\Models\VideoFile;
use App\Models\AudioFile;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MediaController extends Controller
{
    /**
     * Stream video or audio file with range support (for seeking).
     *
     * @param int $id
     * @param Request $request
     * @return StreamedResponse|Response
     */
    public function stream(int $id, Request $request)
    {
        // Find the media file (video or audio)
        $media = MediaFile::findOrFail($id);

        // Verify it's a streamable type
        if (!in_array($media->media_type, ['video', 'audio'])) {
            abort(404, 'Media type not streamable');
        }

        // Get the full file path
        $path = Storage::path($media->file_path);

        if (!file_exists($path)) {
            abort(404, 'File not found');
        }

        $size = filesize($path);
        $mimeType = $media->mime_type ?? mime_content_type($path);

        // Handle range requests for video/audio seeking
        $range = $request->header('Range');

        if ($range) {
            // Try suffix-range: bytes=-N (last N bytes) per RFC 7233
            if (preg_match('/^bytes=-(\d+)$/', $range, $suffixMatch)) {
                $start = max(0, $size - intval($suffixMatch[1]));
                $end   = $size - 1;
            }
            // Try standard range: bytes=N-M or bytes=N-
            elseif (preg_match('/^bytes=(\d+)-(\d*)$/', $range, $matches)) {
                $start = intval($matches[1]);
                $end   = $matches[2] !== '' ? intval($matches[2]) : $size - 1;
            }
            else {
                abort(response('Range Not Satisfiable', 416, [
                    'Content-Range' => "bytes */{$size}",
                ]));
            }

            // Clamp end to last byte and reject unsatisfiable start
            $end = min($end, $size - 1);
            if ($start >= $size || $start > $end) {
                abort(response('Range Not Satisfiable', 416, [
                    'Content-Range' => "bytes */{$size}",
                ]));
            }

            $length = $end - $start + 1;

            return response()->stream(function () use ($path, $start, $length) {
                $file = fopen($path, 'rb');
                fseek($file, $start);

                $chunkSize = 1024 * 1024; // 1MB chunks
                while (!feof($file) && $length > 0) {
                    $read = min($chunkSize, $length);
                    echo fread($file, $read);
                    flush();
                    $length -= $read;
                }

                fclose($file);
            }, 206, [
                'Content-Type' => $mimeType,
                'Content-Length' => $length,
                'Content-Range' => "bytes $start-$end/$size",
                'Accept-Ranges' => 'bytes',
                'Cache-Control' => 'public, max-age=31536000',
                'X-Content-Type-Options' => 'nosniff',
            ]);
        }

        // No range request - stream entire file
        return response()->stream(function () use ($path) {
            $file = fopen($path, 'rb');

            while (!feof($file)) {
                echo fread($file, 1024 * 1024); // 1MB chunks
                flush();
            }

            fclose($file);
        }, 200, [
            'Content-Type' => $mimeType,
            'Content-Length' => $size,
            'Accept-Ranges' => 'bytes',
            'Cache-Control' => 'public, max-age=31536000',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * Get thumbnail for video.
     *
     * @param int $id
     * @return Response
     */
    public function thumbnail(int $id)
    {
        $video = VideoFile::findOrFail($id);

        if (!$video->thumbnail_path || !Storage::exists($video->thumbnail_path)) {
            abort(404, 'Thumbnail not found');
        }

        $path = Storage::path($video->thumbnail_path);
        $mimeType = mime_content_type($path);

        return response()->file($path, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'public, max-age=31536000',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * Download media file (video, audio, or archive).
     *
     * @param int $id
     * @return Response
     */
    public function download(int $id)
    {
        $media = MediaFile::findOrFail($id);

        if (!Storage::exists($media->file_path)) {
            abort(404, 'File not found');
        }

        $path = Storage::path($media->file_path);
        $mimeType = $media->mime_type ?? mime_content_type($path);

        return response()->download($path, $media->original_filename, [
            'Content-Type' => $mimeType,
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
