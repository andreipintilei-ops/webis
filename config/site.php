<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Home page: the sections below the hero
    |--------------------------------------------------------------------------
    |
    | "dark" or "light": the tone of the home page's sections after the hero
    | (Ce dezvoltăm, Proiecte, Produsele noastre, Cum lucrăm). Styles:
    | resources/css/site/lower-tone.css. Contact keeps its own navy either way.
    |
    */

    'lower_tone' => env('SITE_LOWER_TONE', 'dark') === 'light' ? 'light' : 'dark',

    /*
    |--------------------------------------------------------------------------
    | The canonical site
    |--------------------------------------------------------------------------
    |
    | The scheme and host every canonical tag and sitemap URL names, on any
    | environment: the address Google has indexed (www, https). Not APP_URL,
    | so a staging or local copy still points at the real pages.
    |
    */

    'canonical_url' => rtrim((string) env('SITE_CANONICAL_URL', 'https://www.webis.ro'), '/'),

];
