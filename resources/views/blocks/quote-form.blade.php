{{--
    Quote-form block (App\Blocks\QuoteFormBlock): the site's one contact form
    (components/site/contact) — not a second form. It posts to the
    `cerere-oferta` route (ContactController: a lead in the admin's Cereri),
    works without JavaScript and is sent in place by js/public/
    contact-form.ts.

    The block sets the form's title and intro, and whether the phone, email
    and address stand beside it (`show_contact_details`). On the hero's
    indigo here, rather than the home page's navy; css/site/
    block-quote-form.css.

    `service_page_id` (preselecting a service) is not used yet: the form
    and the lead have no field for it.
--}}
@php
    $heading = trim((string) ($data['heading'] ?? ''));
    $intro = trim((string) ($data['intro'] ?? ''));
@endphp

<x-site.contact
    class="block-quote-form"
    :title="$heading !== '' ? $heading : null"
    :intro="$intro !== '' ? $intro : null"
    :details="(bool) ($data['show_contact_details'] ?? true)"
    data-block="{{ $block['id'] }}"
/>
