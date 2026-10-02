<?php

namespace App\Blocks;

use App\Support\YouTube;
use Closure;

/**
 * A YouTube video as a lite embed: a poster and a play button, with the
 * player's ~800 KB of JavaScript loaded only on click.
 */
class VideoBlock extends Block
{
    public static function type(): string
    {
        return 'video';
    }

    public static function label(): string
    {
        return 'Video (YouTube)';
    }

    public function defaults(): array
    {
        return [
            'title' => '',
            'url' => '',
            // Derived from `url` on save.
            'youtube_id' => null,
            // Optional custom poster; YouTube's own thumbnail otherwise.
            'poster_asset_id' => null,
            'caption' => null,
        ];
    }

    public function rules(string $p): array
    {
        return [
            // Accessible name of the play button, and the VideoObject name.
            "{$p}title" => $this->text(true, 160),
            "{$p}url" => ['required', 'string', 'max:255', function (string $attribute, mixed $value, Closure $fail): void {
                if (! is_string($value) || YouTube::idFromUrl($value) === null) {
                    $fail('Introdu un link YouTube valid.');
                }
            }],
            "{$p}poster_asset_id" => $this->asset(),
            "{$p}caption" => $this->text(false, 300),
        ];
    }

    public function prepare(array $data): array
    {
        $data = parent::prepare($data);
        $data['youtube_id'] = is_string($data['url']) ? YouTube::idFromUrl($data['url']) : null;

        return $data;
    }

    protected function assetFields(): array
    {
        return ['poster_asset_id'];
    }
}
