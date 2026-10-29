<?php

use App\Enums\PageType;
use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Page::factory()->ofType(PageType::Home)->create(['slug' => 'acasa']);
});

it('tells browsers to keep to HTTPS for a year, for this host only', function () {
    $this->get('https://www.webis.ro/')
        ->assertHeader('Strict-Transport-Security', 'max-age=31536000');
});

it('sends it on files and errors too, not only pages', function () {
    $this->get('https://www.webis.ro/pagina-care-nu-exista/')
        ->assertNotFound()
        ->assertHeader('Strict-Transport-Security', 'max-age=31536000');
});

it('says nothing over plain HTTP', function () {
    $this->get('http://www.webis.ro/')->assertHeaderMissing('Strict-Transport-Security');
});
