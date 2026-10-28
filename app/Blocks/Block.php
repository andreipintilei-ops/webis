<?php

namespace App\Blocks;

use App\Rules\SafeLink;
use App\Support\RichText\RichText;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

/**
 * One page-builder block type. Pages and projects store their layout as an
 * ordered array of `{id, type, v, data}`; this class owns everything about the
 * `data` of one type: its starting values, validation, the asset ids it holds,
 * what is derived on save, and the Blade view that renders it (Phase 3).
 *
 * Each type needs a matching `*Editor.vue` in the admin (Phase 2).
 */
abstract class Block
{
    /**
     * Stored as `type`. Never rename one: saved pages refer to it.
     */
    abstract public static function type(): string;

    /**
     * Romanian name shown in the admin's "add block" menu.
     */
    abstract public static function label(): string;

    /**
     * Stored as `v`. Bump it when the data shape changes, and teach prepare()
     * to upgrade older data.
     */
    public static function version(): int
    {
        return 1;
    }

    /**
     * Starting data for a new block. It also defines the block's fields: on
     * save, keys not listed here are dropped and missing ones are filled in.
     *
     * @return array<string, mixed>
     */
    abstract public function defaults(): array;

    /**
     * Validation rules for `data`. Every key starts with $p (e.g.
     * "blocks.3.data.") so the admin can show errors next to the right field,
     * and so conditional rules can name their sibling fields.
     *
     * @return array<string, mixed>
     */
    abstract public function rules(string $p): array;

    /**
     * Dot paths (`*` allowed) of fields holding asset ids.
     *
     * @return list<string>
     */
    protected function assetFields(): array
    {
        return [];
    }

    /**
     * Fields holding a Tiptap document. prepare() sanitizes each one and stores
     * its HTML next to it as `{field}_html`.
     *
     * @return list<string>
     */
    protected function richTextFields(): array
    {
        return [];
    }

    public function view(): string
    {
        return 'blocks.'.str_replace('_', '-', static::type());
    }

    /**
     * Normalise validated data into its stored shape. Idempotent: it runs on
     * every save of the owning model.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function prepare(array $data): array
    {
        $defaults = $this->defaults();
        $data = array_merge($defaults, array_intersect_key($data, $defaults));

        $richText = app(RichText::class);

        foreach ($this->richTextFields() as $field) {
            $doc = $richText->sanitize(is_array($data[$field] ?? null) ? $data[$field] : []);
            $data[$field] = $doc;
            $data["{$field}_html"] = $richText->render($doc);
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<int>
     */
    public function assetIds(array $data): array
    {
        $ids = [];

        foreach ($this->assetFields() as $path) {
            foreach (Arr::wrap(data_get($data, $path)) as $id) {
                if (is_numeric($id)) {
                    $ids[] = (int) $id;
                }
            }
        }

        $richText = app(RichText::class);

        foreach ($this->richTextFields() as $field) {
            if (is_array($data[$field] ?? null)) {
                array_push($ids, ...$richText->assetIds($data[$field]));
            }
        }

        return array_values(array_unique($ids));
    }

    // ---- Rule helpers -----------------------------------------------------

    /**
     * @return list<string>
     */
    protected function text(bool $required, int $max): array
    {
        return [$required ? 'required' : 'nullable', 'string', "max:{$max}"];
    }

    /**
     * @return list<mixed>
     */
    protected function asset(bool $required = false): array
    {
        return [$required ? 'required' : 'nullable', 'integer', Rule::exists('assets', 'id')];
    }

    /**
     * A Tiptap document field.
     *
     * @return array<string, list<string>>
     */
    protected function richText(string $p, string $key, bool $required = true): array
    {
        return [
            "{$p}{$key}" => [$required ? 'required' : 'nullable', 'array'],
            "{$p}{$key}.type" => ["required_with:{$p}{$key}", 'in:doc'],
            "{$p}{$key}.content" => ['nullable', 'array'],
        ];
    }

    /**
     * A button: `{label, url}`. Optional buttons may be null, but not half-filled.
     *
     * @return array<string, list<mixed>>
     */
    protected function cta(string $p, string $key, bool $required = false): array
    {
        return [
            "{$p}{$key}" => [$required ? 'required' : 'nullable', 'array:label,url'],
            "{$p}{$key}.label" => [$required ? 'required' : "required_with:{$p}{$key}.url", 'nullable', 'string', 'max:60'],
            "{$p}{$key}.url" => [$required ? 'required' : "required_with:{$p}{$key}.label", 'nullable', 'string', 'max:2048', new SafeLink],
        ];
    }

    /**
     * A hand-picked list of records, required when `source` is `manual`.
     *
     * @return array<string, list<mixed>>
     */
    protected function picked(string $p, string $key, string $table, int $max = 24): array
    {
        return [
            "{$p}{$key}" => ["required_if:{$p}source,manual", 'array', "max:{$max}"],
            "{$p}{$key}.*" => ['integer', 'distinct', Rule::exists($table, 'id')],
        ];
    }

    /**
     * @param  list<string>  $options
     * @return list<mixed>
     */
    protected function oneOf(array $options): array
    {
        return ['required', Rule::in($options)];
    }
}
