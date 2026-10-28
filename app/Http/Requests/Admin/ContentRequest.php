<?php

namespace App\Http\Requests\Admin;

use App\Enums\ContentStatus;
use App\Support\AdminTime;
use App\Support\Seo\SeoData;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

/**
 * Rules shared by every publishable content editor: slug, publication state
 * and SEO overrides. Access is enforced by the admin route middleware.
 */
abstract class ContentRequest extends FormRequest
{
    public const string SLUG_PATTERN = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<mixed>>
     */
    protected function slugRules(string $table, ?int $ignoreId): array
    {
        return [
            'slug' => [
                'required', 'string', 'max:190', 'regex:'.self::SLUG_PATTERN,
                // Trashed rows count: their URL may still come back on restore.
                Rule::unique($table, 'slug')->ignore($ignoreId),
            ],
        ];
    }

    /**
     * Draft, scheduled (needs a future date) or published (date optional,
     * never in the future — that is what scheduling is for).
     *
     * @return array<string, list<mixed>>
     */
    protected function publicationRules(): array
    {
        return [
            'status' => ['required', new Enum(ContentStatus::class)],
            'published_at' => [
                'nullable', 'date_format:'.AdminTime::INPUT_FORMAT, 'required_if:status,scheduled',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $moment = is_string($value) ? AdminTime::fromInput($value) : null;

                    if ($moment === null) {
                        return;
                    }

                    if ($this->input('status') === ContentStatus::Scheduled->value && $moment->isPast()) {
                        $fail('Programarea trebuie să fie în viitor.');
                    }

                    if ($this->input('status') === ContentStatus::Published->value && $moment->isFuture()) {
                        $fail('Pentru o dată viitoare alege „Programat”.');
                    }
                },
            ],
        ];
    }

    /**
     * @return array<string, list<mixed>>
     */
    protected function seoRules(): array
    {
        return [
            'seo' => ['nullable', 'array'],
            'seo.title' => ['nullable', 'string', 'max:120'],
            'seo.description' => ['nullable', 'string', 'max:320'],
            'seo.canonical' => ['nullable', 'url:https,http', 'max:2048'],
            'seo.noindex' => ['boolean'],
            'seo.og_image_asset_id' => ['nullable', 'integer', Rule::exists('assets', 'id')],
            'seo.focus_keyword' => ['nullable', 'string', 'max:100'],
        ];
    }

    /**
     * Status and date as the model stores them.
     *
     * @return array{status: ContentStatus, published_at: CarbonImmutable|null}
     */
    public function publication(): array
    {
        $status = ContentStatus::from((string) $this->input('status'));

        return [
            'status' => $status,
            // A draft keeps no date; publishing without one means "now" (model hook).
            'published_at' => $status === ContentStatus::Draft
                ? null
                : AdminTime::fromInput($this->string('published_at')->toString()),
        ];
    }

    public function seo(): SeoData
    {
        $seo = $this->input('seo');

        return SeoData::fromArray(is_array($seo) ? $seo : []);
    }
}
