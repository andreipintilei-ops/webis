<?php

use App\Support\PhoneLink;

it('links a phone number in its international form', function (?string $phone, ?string $href) {
    expect(PhoneLink::href($phone))->toBe($href);
})->with([
    'national' => ['0770 700 607', 'tel:+40770700607'],
    'already international' => ['+40 770 700 607', 'tel:+40770700607'],
    'with 00' => ['0040 770 700 607', 'tel:+40770700607'],
    'none' => [null, null],
    'empty' => ['', null],
]);
