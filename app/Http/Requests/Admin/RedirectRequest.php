<?php

namespace App\Http\Requests\Admin;

use App\Enums\RedirectCode;
use App\Enums\RedirectMatchType;
use App\Models\Redirect;
use App\Rules\SafeLink;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Validator;

class RedirectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Compare sources in their stored, canonical form.
     */
    protected function prepareForValidation(): void
    {
        $source = $this->input('source_path');

        if (is_string($source) && trim($source) !== '') {
            $this->merge(['source_path' => Redirect::normalisePath($source)]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $redirect = $this->route('redirect');

        return [
            'source_path' => [
                'required', 'string', 'max:512', 'not_in:/',
                Rule::unique('redirects', 'source_path')->ignore($redirect instanceof Redirect ? $redirect->id : null),
            ],
            'match_type' => ['required', new Enum(RedirectMatchType::class)],
            'status_code' => ['required', 'integer', new Enum(RedirectCode::class)],
            'target' => [
                Rule::requiredIf(fn (): bool => RedirectCode::tryFrom((int) $this->input('status_code'))?->needsTarget() ?? true),
                'nullable', 'string', 'max:2048', new SafeLink,
            ],
            'notes' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * No loops and no chains: every hop costs crawl budget and a little link
     * equity, so a redirect must land on a final URL.
     *
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $target = $this->input('target');

                if (! is_string($target) || $target === '' || ! str_starts_with($target, '/')) {
                    return;
                }

                $targetPath = Redirect::normalisePath($target);

                if ($targetPath === $this->input('source_path')) {
                    $validator->errors()->add('target', 'Destinația este chiar sursa — ar crea o buclă.');

                    return;
                }

                $redirect = $this->route('redirect');
                $ignoreId = $redirect instanceof Redirect ? $redirect->id : null;

                $chained = Redirect::query()
                    ->where('source_path', $targetPath)
                    ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
                    ->exists();

                if ($chained) {
                    $validator->errors()->add('target', 'Destinația este ea însăși redirecționată. Trimite direct spre adresa finală.');
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'source_path.unique' => 'Există deja o redirecționare pentru această adresă.',
            'source_path.not_in' => 'Prima pagină nu poate fi redirecționată.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function redirectAttributes(): array
    {
        $code = RedirectCode::from((int) $this->input('status_code'));

        return [
            'source_path' => $this->string('source_path')->toString(),
            'match_type' => RedirectMatchType::from((string) $this->input('match_type')),
            'status_code' => $code,
            'target' => $code->needsTarget() ? $this->input('target') : null,
            'notes' => $this->input('notes'),
        ];
    }
}
