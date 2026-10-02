<?php

namespace App\Support;

final class YouTube
{
    /**
     * The 11-character video id from any common YouTube URL form (watch,
     * youtu.be, embed, shorts, live, nocookie) or a bare id; null otherwise.
     */
    public static function idFromUrl(string $url): ?string
    {
        $url = trim($url);

        if (preg_match('/^[A-Za-z0-9_-]{11}$/', $url) === 1) {
            return $url;
        }

        $pattern = '~^(?:https?://)?(?:www\.|m\.)?(?:youtube(?:-nocookie)?\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/|v/)|youtu\.be/)([A-Za-z0-9_-]{11})(?:[?&#/].*)?$~i';

        return preg_match($pattern, $url, $matches) === 1 ? $matches[1] : null;
    }
}
