<?php

/*
| Romanian validation messages for the admin. Every error is shown next to
| its own field, so messages name the problem, not the field — nested keys like
| `blocks.3.data.items.0.title` would read badly in a sentence anyway.
*/

return [
    'accepted' => 'Trebuie acceptat.',
    'active_url' => 'Adresa nu este validă.',
    'after' => 'Data trebuie să fie după :date.',
    'after_or_equal' => 'Data trebuie să fie cel puțin :date.',
    'alpha_dash' => 'Folosește doar litere, cifre, cratime și underscore.',
    'array' => 'Valoare invalidă.',
    'before' => 'Data trebuie să fie înainte de :date.',
    'between' => [
        'array' => 'Între :min și :max elemente.',
        'file' => 'Între :min și :max KB.',
        'numeric' => 'Între :min și :max.',
        'string' => 'Între :min și :max caractere.',
    ],
    'boolean' => 'Valoare invalidă.',
    'confirmed' => 'Confirmarea nu se potrivește.',
    'current_password' => 'Parola este greșită.',
    'date' => 'Dată invalidă.',
    'date_format' => 'Dată invalidă.',
    'different' => 'Trebuie să fie diferit de :other.',
    'digits' => 'Trebuie să aibă :digits cifre.',
    'digits_between' => 'Între :min și :max cifre.',
    'distinct' => 'Valoare duplicată.',
    'email' => 'Adresa de email nu este validă.',
    'ends_with' => 'Trebuie să se termine cu: :values.',
    'exists' => 'Elementul ales nu mai există.',
    'file' => 'Trebuie să fie un fișier.',
    'filled' => 'Completează acest câmp.',
    'gt' => [
        'numeric' => 'Trebuie să fie mai mare decât :value.',
    ],
    'image' => 'Trebuie să fie o imagine.',
    'in' => 'Valoare invalidă.',
    'integer' => 'Trebuie să fie un număr întreg.',
    'ip' => 'Adresă IP invalidă.',
    'json' => 'JSON invalid.',
    'lowercase' => 'Folosește doar litere mici.',
    'max' => [
        'array' => 'Maximum :max elemente.',
        'file' => 'Fișierul depășește :max KB.',
        'numeric' => 'Maximum :max.',
        'string' => 'Maximum :max caractere.',
    ],
    'mimes' => 'Tip de fișier neacceptat (:values).',
    'mimetypes' => 'Tip de fișier neacceptat.',
    'min' => [
        'array' => 'Adaugă cel puțin :min.',
        'file' => 'Cel puțin :min KB.',
        'numeric' => 'Minimum :min.',
        'string' => 'Cel puțin :min caractere.',
    ],
    'not_in' => 'Valoarea aleasă nu este permisă.',
    'not_regex' => 'Format invalid.',
    'numeric' => 'Trebuie să fie un număr.',
    'present' => 'Câmpul lipsește.',
    'prohibited' => 'Câmpul nu este permis.',
    'regex' => 'Format invalid.',
    'required' => 'Completează acest câmp.',
    'required_if' => 'Completează acest câmp.',
    'required_unless' => 'Completează acest câmp.',
    'required_with' => 'Completează acest câmp.',
    'required_with_all' => 'Completează acest câmp.',
    'required_without' => 'Completează acest câmp.',
    'same' => 'Trebuie să fie la fel ca :other.',
    'size' => [
        'array' => 'Exact :size elemente.',
        'string' => 'Exact :size caractere.',
    ],
    'starts_with' => 'Trebuie să înceapă cu: :values.',
    'string' => 'Trebuie să fie text.',
    'timezone' => 'Fus orar invalid.',
    'unique' => 'Valoarea este deja folosită.',
    'uploaded' => 'Încărcarea a eșuat.',
    'url' => 'Adresa nu este validă.',
    'uuid' => 'Identificator invalid.',

    'password' => [
        'letters' => 'Parola trebuie să conțină cel puțin o literă.',
        'mixed' => 'Parola trebuie să conțină litere mari și mici.',
        'numbers' => 'Parola trebuie să conțină cel puțin o cifră.',
        'symbols' => 'Parola trebuie să conțină cel puțin un simbol.',
        'uncompromised' => 'Parola a apărut într-o scurgere de date. Alege alta.',
    ],

    'custom' => [],

    'attributes' => [],
];
