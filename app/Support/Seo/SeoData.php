<?php

namespace App\Support\Seo;

use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Database\Eloquent\ComparesCastableAttributes;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use JsonSerializable;

/**
 * Per-item SEO overrides, stored in the `seo` JSON column of pages, projects,
 * posts and the two category tables. Every field is optional: an empty value
 * means "fall back to the generated default" (SeoMeta::for(), Phase 4).
 *
 * The cast never yields null — a missing column reads as an empty SeoData, so
 * templates and the admin can always do `$page->seo->title`.
 *
 * @implements Arrayable<string, string|int|bool|null>
 */
final readonly class SeoData implements Arrayable, Castable, JsonSerializable
{
    public function __construct(
        public ?string $title = null,
        public ?string $description = null,
        public ?string $canonical = null,
        public bool $noindex = false,
        public ?int $ogImageAssetId = null,
        public ?string $focusKeyword = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data  snake_case keys, as stored and as posted by the admin
     */
    public static function fromArray(array $data): self
    {
        $string = static fn (string $key): ?string => isset($data[$key]) && is_scalar($data[$key]) && trim((string) $data[$key]) !== ''
            ? trim((string) $data[$key])
            : null;

        return new self(
            title: $string('title'),
            description: $string('description'),
            canonical: $string('canonical'),
            noindex: (bool) ($data['noindex'] ?? false),
            ogImageAssetId: isset($data['og_image_asset_id']) && is_numeric($data['og_image_asset_id'])
                ? (int) $data['og_image_asset_id']
                : null,
            focusKeyword: $string('focus_keyword'),
        );
    }

    /**
     * @return array{title: ?string, description: ?string, canonical: ?string, noindex: bool, og_image_asset_id: ?int, focus_keyword: ?string}
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'canonical' => $this->canonical,
            'noindex' => $this->noindex,
            'og_image_asset_id' => $this->ogImageAssetId,
            'focus_keyword' => $this->focusKeyword,
        ];
    }

    /**
     * @return array{title: ?string, description: ?string, canonical: ?string, noindex: bool, og_image_asset_id: ?int, focus_keyword: ?string}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function isEmpty(): bool
    {
        return $this == new self;
    }

    /**
     * Decode a raw column value; anything unreadable counts as no overrides.
     */
    public static function fromJson(mixed $value): self
    {
        $decoded = is_string($value) ? json_decode($value, true) : null;

        return self::fromArray(is_array($decoded) ? $decoded : []);
    }

    /**
     * @param  array<mixed>  $arguments
     * @return CastsAttributes<SeoData, SeoData|array<string, mixed>|null>
     */
    public static function castUsing(array $arguments): CastsAttributes
    {
        return new class implements CastsAttributes, ComparesCastableAttributes
        {
            /**
             * @param  array<string, mixed>  $attributes
             */
            public function get(Model $model, string $key, mixed $value, array $attributes): SeoData
            {
                return SeoData::fromJson($value);
            }

            /**
             * @param  array<string, mixed>  $attributes
             * @return array<string, string|null>
             */
            public function set(Model $model, string $key, mixed $value, array $attributes): array
            {
                $seo = match (true) {
                    $value instanceof SeoData => $value,
                    is_array($value) => SeoData::fromArray($value),
                    $value === null => new SeoData,
                    default => throw new InvalidArgumentException('The seo attribute must be a SeoData instance, an array or null.'),
                };

                return [$key => $seo->isEmpty()
                    ? null
                    : (string) json_encode($seo->toArray(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)];
            }

            /**
             * Compare by meaning, not by string. MySQL returns JSON columns
             * normalised (spacing, key order), so the re-encoded value never
             * string-matches what was read — every save would look like an SEO
             * edit and record a revision.
             */
            public function compare(Model $model, string $key, mixed $firstValue, mixed $secondValue): bool
            {
                return SeoData::fromJson($firstValue) == SeoData::fromJson($secondValue);
            }
        };
    }
}
