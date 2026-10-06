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

];
