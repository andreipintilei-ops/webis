<?php

namespace App\Http\Requests\Admin;

use App\Models\Post;
use Illuminate\Validation\Rule;

class PostRequest extends ContentRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $post = $this->route('post');

        return [
            'title' => ['required', 'string', 'max:200'],
            ...$this->slugRules('posts', $post instanceof Post ? $post->id : null),
            'excerpt' => ['nullable', 'string', 'max:500'],
            'body' => ['required', 'array'],
            'body.type' => ['required', 'in:doc'],
            'body.content' => ['nullable', 'array'],
            'cover_asset_id' => ['nullable', 'integer', Rule::exists('assets', 'id')],
            'post_category_id' => ['nullable', 'integer', Rule::exists('post_categories', 'id')],
            'author_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'cta_page_id' => ['nullable', 'integer', Rule::exists('pages', 'id')],
            'is_featured' => ['boolean'],
            ...$this->publicationRules(),
            ...$this->seoRules(),
        ];
    }

    public function messages(): array
    {
        return [
            'slug.unique' => 'Adresa este folosită de alt articol (poate din coș).',
            'slug.regex' => 'Doar litere mici, cifre și cratime, ex. ghid-seo-local.',
        ];
    }

    /**
     * The body is sanitised and rendered by the model on save.
     *
     * @return array<string, mixed>
     */
    public function postAttributes(): array
    {
        return [
            'title' => $this->string('title')->trim()->toString(),
            'slug' => $this->string('slug')->toString(),
            'excerpt' => $this->input('excerpt'),
            'body' => (array) $this->input('body'),
            'cover_asset_id' => $this->input('cover_asset_id'),
            'post_category_id' => $this->input('post_category_id'),
            'author_id' => $this->input('author_id'),
            'cta_page_id' => $this->input('cta_page_id'),
            'is_featured' => $this->boolean('is_featured'),
            'seo' => $this->seo(),
            ...$this->publication(),
        ];
    }
}
