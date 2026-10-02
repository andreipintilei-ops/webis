<?php

namespace App\Blocks;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

/**
 * Every block type the page builder offers, and the operations on a whole
 * `blocks` array: validation rules, validation, normalising and asset ids.
 *
 * A stored block is `{id, type, v, data}`: a stable id (for drag-and-drop and
 * anchors), the type key, the data-shape version and the type's own data.
 *
 * @phpstan-type StoredBlock array{id: string, type: string, v: int, data: array<string, mixed>}
 */
final class BlockRegistry
{
    /** Upper bound on blocks per page — far above any real layout. */
    public const int MAX_BLOCKS = 60;

    /**
     * @var array<string, Block>
     */
    private array $blocks = [];

    /**
     * @param  iterable<Block>|null  $blocks  defaults to the v1 set
     */
    public function __construct(?iterable $blocks = null)
    {
        foreach ($blocks ?? self::defaultBlocks() as $block) {
            $this->blocks[$block::type()] = $block;
        }
    }

    /**
     * @return list<Block>
     */
    public static function defaultBlocks(): array
    {
        return [
            new HeroBlock,
            new StatementBlock,
            new RichTextBlock,
            new ServicesGridBlock,
            new FeaturesBlock,
            new PortfolioShowcaseBlock,
            new ClientLogosBlock,
            new NotableClientsBlock,
            new TestimonialsBlock,
            new StatsBlock,
            new ProcessStepsBlock,
            new PricingTiersBlock,
            new FaqBlock,
            new ImageTextBlock,
            new GalleryBlock,
            new VideoBlock,
            new BlogTeaserBlock,
            new CtaBannerBlock,
            new QuoteFormBlock,
        ];
    }

    public function has(string $type): bool
    {
        return isset($this->blocks[$type]);
    }

    public function get(string $type): Block
    {
        return $this->blocks[$type] ?? throw new InvalidArgumentException("Unknown block type [{$type}].");
    }

    /**
     * @return array<string, Block>
     */
    public function all(): array
    {
        return $this->blocks;
    }

    /**
     * A new block of $type with its starting data, ready for the editor.
     *
     * @return StoredBlock
     */
    public function make(string $type): array
    {
        $block = $this->get($type);

        return ['id' => self::newId(), 'type' => $type, 'v' => $block::version(), 'data' => $block->defaults()];
    }

    /**
     * Rules for a submitted `blocks` array. Each block's data rules depend on
     * its type, so the rules are built from the submission itself.
     *
     * @param  array<mixed>  $blocks
     * @return array<string, mixed>
     */
    public function rules(array $blocks, string $key = 'blocks'): array
    {
        $rules = [
            $key => ['array', 'max:'.self::MAX_BLOCKS],
            "{$key}.*" => ['array:id,type,v,data'],
            "{$key}.*.id" => ['required', 'string', 'max:40', 'distinct'],
            "{$key}.*.type" => ['required', 'string', Rule::in(array_keys($this->blocks))],
            "{$key}.*.v" => ['nullable', 'integer', 'min:1'],
            "{$key}.*.data" => ['present', 'array'],
        ];

        foreach ($blocks as $index => $block) {
            $type = is_array($block) ? ($block['type'] ?? null) : null;

            if (is_string($type) && $this->has($type)) {
                $rules += $this->get($type)->rules("{$key}.{$index}.data.");
            }
        }

        return $rules;
    }

    /**
     * Validate a submitted `blocks` array and return it normalised.
     *
     * @param  array<mixed>  $blocks
     * @return list<StoredBlock>
     *
     * @throws ValidationException
     */
    public function validate(array $blocks, string $key = 'blocks'): array
    {
        Validator::make([$key => $blocks], $this->rules($blocks, $key))->validate();

        return $this->prepare($blocks);
    }

    /**
     * Normalise blocks into their stored shape: ids assigned, current version,
     * data trimmed to the type's fields and rich text rendered. Blocks of an
     * unknown type (e.g. a retired one) are kept untouched rather than lost;
     * the renderer skips them.
     *
     * @param  array<mixed>  $blocks
     * @return list<StoredBlock>
     */
    public function prepare(array $blocks): array
    {
        $prepared = [];

        foreach ($blocks as $block) {
            if (! is_array($block) || ! is_string($block['type'] ?? null)) {
                continue;
            }

            $type = $block['type'];
            $id = is_string($block['id'] ?? null) && $block['id'] !== '' ? $block['id'] : self::newId();
            $data = is_array($block['data'] ?? null) ? $block['data'] : [];

            if (! $this->has($type)) {
                $v = $block['v'] ?? 1;
                $prepared[] = ['id' => $id, 'type' => $type, 'v' => is_int($v) ? $v : 1, 'data' => $data];

                continue;
            }

            $definition = $this->get($type);

            $prepared[] = [
                'id' => $id,
                'type' => $type,
                'v' => $definition::version(),
                'data' => $definition->prepare($data),
            ];
        }

        return $prepared;
    }

    /**
     * Every asset referenced anywhere in the blocks.
     *
     * @param  array<mixed>  $blocks
     * @return list<int>
     */
    public function assetIds(array $blocks): array
    {
        $ids = [];

        foreach ($blocks as $block) {
            $type = is_array($block) ? ($block['type'] ?? null) : null;

            if (is_string($type) && $this->has($type) && is_array($block['data'] ?? null)) {
                array_push($ids, ...$this->get($type)->assetIds($block['data']));
            }
        }

        return array_values(array_unique($ids));
    }

    private static function newId(): string
    {
        return Str::lower((string) Str::ulid());
    }
}
