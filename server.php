<?php

/**
 * Laravel - A PHP Framework For Web Artisans
 * Custom Server Router with High-Performance HTTP Static Caching
 */

$publicPath = __DIR__ . '/public';

$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? ''
);

// If the requested URI is a static file that exists in public/
$filePath = $publicPath . $uri;

if ($uri !== '/' && file_exists($filePath) && is_file($filePath)) {
    // Check if it's an uploaded file or static asset (image, font, css, js)
    $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    $cacheableExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'ico', 'woff', 'woff2', 'ttf', 'css', 'js', 'mp4', 'mov', 'pdf'];

    if (str_starts_with($uri, '/uploads/') || in_array($ext, $cacheableExts)) {
        $mtime = filemtime($filePath);
        $size = filesize($filePath);
        $etag = '"' . md5($mtime . $size) . '"';
        $lastModified = gmdate('D, d M Y H:i:s', $mtime) . ' GMT';

        header('Cache-Control: public, max-age=31536000, immutable');
        header('ETag: ' . $etag);
        header('Last-Modified: ' . $lastModified);
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT');

        // Conditional GET
        $ifNoneMatch = $_SERVER['HTTP_IF_NONE_MATCH'] ?? null;
        $ifModifiedSince = $_SERVER['HTTP_IF_MODIFIED_SINCE'] ?? null;

        if ($ifNoneMatch === $etag || ($ifModifiedSince && strtotime($ifModifiedSince) >= $mtime)) {
            http_response_code(304);
            exit;
        }

        $mimeTypes = [
            'jpg'   => 'image/jpeg',
            'jpeg'  => 'image/jpeg',
            'png'   => 'image/png',
            'gif'   => 'image/gif',
            'webp'  => 'image/webp',
            'svg'   => 'image/svg+xml',
            'ico'   => 'image/x-icon',
            'css'   => 'text/css',
            'js'    => 'application/javascript',
            'woff'  => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf'   => 'font/ttf',
            'mp4'   => 'video/mp4',
            'pdf'   => 'application/pdf',
        ];

        $contentType = $mimeTypes[$ext] ?? (mime_content_type($filePath) ?: 'application/octet-stream');
        header('Content-Type: ' . $contentType);
        header('Content-Length: ' . $size);
        readfile($filePath);
        exit;
    }

    // Default static file serving for other files
    return false;
}

require_once $publicPath . '/index.php';
