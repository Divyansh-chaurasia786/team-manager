<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MediaController extends Controller
{
    /**
     * Serve uploaded media files with aggressive HTTP cache-control, byte-range streaming for videos, and conditional GET (304) support.
     */
    public function serveUpload(Request $request, string $path, ?string $filename = null)
    {
        if (function_exists('session_cache_limiter')) {
            @session_cache_limiter('');
        }

        // Support both route pattern (/uploads/{path} where path can contain slashes) and legacy ($type, $filename)
        if ($filename !== null) {
            $type = preg_replace('/[^a-zA-Z0-9_\-]/', '', $path);
            $filename = basename($filename);
            $cleanPath = "{$type}/{$filename}";
        } else {
            // Strip any directory traversal attempts
            $cleanPath = ltrim(str_replace(['\\', '..'], ['/', ''], $path), '/');
        }

        $filePath = public_path("uploads/{$cleanPath}");

        // If not in public/uploads, check storage/app/public/uploads
        if (!file_exists($filePath) || !is_file($filePath)) {
            $storagePath = storage_path("app/public/uploads/{$cleanPath}");
            if (file_exists($storagePath) && is_file($storagePath)) {
                $filePath = $storagePath;
            } else {
                abort(404, 'File not found');
            }
        }

        // Verify that the resolved realpath is within allowed directories
        $realPath = realpath($filePath);
        $publicUploads = realpath(public_path('uploads'));
        $storageUploads = realpath(storage_path('app/public/uploads'));

        $isAllowed = false;
        if ($publicUploads && $realPath && str_starts_with($realPath, $publicUploads)) {
            $isAllowed = true;
        }
        if (!$isAllowed && $storageUploads && $realPath && str_starts_with($realPath, $storageUploads)) {
            $isAllowed = true;
        }

        if (!$isAllowed || !is_file($realPath)) {
            abort(404, 'File not found');
        }

        $mtime = filemtime($realPath);
        $size = filesize($realPath);
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

        $mimeType = mime_content_type($realPath) ?: '';
        $ext = strtolower(pathinfo($realPath, PATHINFO_EXTENSION));
        $extMimes = [
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
            'gif'  => 'image/gif',
            'webp' => 'image/webp',
            'svg'  => 'image/svg+xml',
            'mp4'  => 'video/mp4',
            'mov'  => 'video/quicktime',
            'webm' => 'video/webm',
            'avi'  => 'video/x-msvideo',
            'pdf'  => 'application/pdf',
        ];

        if (isset($extMimes[$ext]) && (empty($mimeType) || $mimeType === 'text/plain' || $mimeType === 'application/octet-stream')) {
            $mimeType = $extMimes[$ext];
        }
        if (empty($mimeType)) {
            $mimeType = 'application/octet-stream';
        }

        // For video files, use BinaryFileResponse to support HTTP 206 Partial Content (seeking/scrubbing)
        if (str_starts_with($mimeType, 'video/')) {
            $response = new \Symfony\Component\HttpFoundation\BinaryFileResponse($realPath);
            $response->setAutoEtag();
            $response->headers->set('Content-Type', $mimeType);
            $response->headers->set('Accept-Ranges', 'bytes');
            $response->headers->set('Cache-Control', 'public, max-age=86400');
            \Symfony\Component\HttpFoundation\BinaryFileResponse::trustXSendfileTypeHeader();
            return $response;
        }

        $response = response()->file($realPath, [
            'Content-Type'  => $mimeType,
            'Cache-Control' => 'public, max-age=31536000, immutable',
            'ETag'          => $etag,
            'Last-Modified' => $lastModified,
            'Expires'       => gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT',
            'Accept-Ranges' => 'bytes',
        ]);

        $response->setSharedMaxAge(31536000);
        $response->setMaxAge(31536000);
        $response->setExpires(new \DateTime('+1 year'));
        $response->headers->set('Cache-Control', 'public, max-age=31536000, immutable');

        return $response;
    }
}
