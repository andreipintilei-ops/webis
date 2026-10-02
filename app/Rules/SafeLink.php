<?php

namespace App\Rules;

use App\Support\LinkTarget;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SafeLink implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! LinkTarget::isAllowed($value)) {
            $fail('Linkul trebuie să fie o cale din site (/…), o ancoră (#…), o adresă http(s), mailto: sau tel:.');
        }
    }
}
