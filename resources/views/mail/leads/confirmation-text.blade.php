{{-- App\Mail\LeadConfirmation, as plain text: not HTML, so nothing to escape. --}}
Bună, {!! \Illuminate\Support\Str::before(trim($lead->name), ' ') ?: $lead->name !!}!

Îți mulțumim că ne-ai scris. Am primit mesajul tău și îți răspundem cât de curând.

Ce ne-ai trimis:

{!! $lead->message !!}

@if ($company->phone || $company->email)
Dacă vrei să ne spui ceva între timp: {!! collect([$company->phone, $company->email])->filter()->implode(' · ') !!}

@endif
Echipa {!! $company->name !!}
