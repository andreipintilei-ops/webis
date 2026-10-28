<?php

namespace App\View\Components;

use App\Models\Asset;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

/**
 * Rendered rich text (the `{field}_html` that Block::prepare() stores next
 * to the Tiptap JSON), as the public site shows it: <x-rich-text :html="…" />.
 *
 * The HTML comes from App\Support\RichText\RichText, so it is already
 * sanitised. Its images are placeholders — `<figure class="rt-image"
 * data-asset-id="N"><figcaption>…</figcaption></figure>`
 * (Nodes/AssetImage.php) — because their WebP versions are made on the
 * queue, after the body is saved. Here each becomes the real image through
 * <x-picture>, its caption kept. Every asset is loaded in one query; a
 * placeholder whose asset (or file) has gone is dropped, not left empty.
 */
class RichText extends Component
{
    /** One placeholder, exactly as Nodes/AssetImage.php writes it. */
    private const string FIGURE = '~<figure class="rt-image" data-asset-id="(\d+)">(?:<figcaption>(.*?)</figcaption>)?</figure>~s';

    public function __construct(public ?string $html = null) {}

    public function render(): View
    {
        return view('components.rich-text', ['parts' => $this->parts()]);
    }

    /**
     * The HTML cut at its images: `['html' => string]` for the text between
     * them, `['asset' => Asset, 'caption' => string]` for each image (the
     * caption already escaped, as the renderer wrote it).
     *
     * @return list<array{html: string}|array{asset: Asset, caption: string}>
     */
    private function parts(): array
    {
        $html = (string) $this->html;

        if (! preg_match_all(self::FIGURE, $html, $figures, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            return $html === '' ? [] : [['html' => $html]];
        }

        $assets = $this->assets(array_map(fn (array $figure): int => (int) $figure[1][0], $figures));

        $parts = [];
        $offset = 0;

        foreach ($figures as $figure) {
            [$match, $start] = $figure[0];

            if ($start > $offset) {
                $parts[] = ['html' => substr($html, $offset, $start - $offset)];
            }

            $asset = $assets->get((int) $figure[1][0]);

            if ($asset?->file() !== null) {
                $parts[] = ['asset' => $asset, 'caption' => $figure[2][0] ?? ''];
            }

            $offset = $start + strlen($match);
        }

        if ($offset < strlen($html)) {
            $parts[] = ['html' => substr($html, $offset)];
        }

        return $parts;
    }

    /**
     * @param  list<int>  $ids
     * @return Collection<int, Asset>
     */
    private function assets(array $ids): Collection
    {
        $ids = array_values(array_unique(array_filter($ids)));

        if ($ids === []) {
            return new Collection;
        }

        return Asset::query()->with('media')->whereKey($ids)->get()->keyBy('id');
    }
}
