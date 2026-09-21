<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MediaController extends Controller
{
    /**
     * Serve uploaded media files with aggressive HTTP cache-control and conditional GET (304) support.
     */
    public function serveUpload(Request $request, string $type, string $filename)
    {
        if (function_exists('session_cache_limiter')) {
            @session_cache_limiter('');
        }

        // Sanitize path to prevent directory traversal
        $filename = basename($filename);
        $type = preg_replace('/[^a-zA-Z0-9_\-]/', '', $type);

        $filePath = public_path("uploads/{$type}/{$filename}");

        if (!file_exists($filePath) || !is_file($filePath)) {
            abort(404, 'File not found');
        }

        $mtime = filemtime($filePath);
        $size = filesize($filePath);
        $etag = '"' . md5($mtime . $size) . '"';
        $lastModified = gmdate('D, d M Y H:i:s', $mtime) . ' GMT';

        // Check conditional headers (ETag / If-None-Match, If-Modified-Since)
        $ifNoneMatch = $request->headers->get('If-None-Match');
        $ifModifiedSince = $request->headers->get('If-Modified-Since');

        if ($ifNoneMatch === $etag || ($ifModifiedSince && strtotime($ifModifiedSince) >= $mtime)) {
            return response('', 304, [
                'Cache-Control' => 'public, max-age=31536000, immutable',
                'ETag'          => $etag,
                'Last-Modified' => $lastModified,
            ]);
        }

        $mimeType = mime_content_type($filePath) ?: 'application/octet-stream';

        $response = response()->file($filePath, [
            'Content-Type'  => $mimeType,
            'Cache-Control' => 'public, max-age=31536000, immutable',
            'ETag'          => $etag,
            'Last-Modified' => $lastModified,
            'Expires'       => gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT',
        ]);

        $response->setSharedMaxAge(31536000);
        $response->setMaxAge(31536000);
        $response->setExpires(new \DateTime('+1 year'));
        $response->headers->set('Cache-Control', 'public, max-age=31536000, immutable');

        return $response;
    }
}
