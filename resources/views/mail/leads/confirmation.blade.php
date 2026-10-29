{{-- App\Mail\LeadConfirmation: to the visitor, their request has arrived. --}}
@php
    $first = \Illuminate\Support\Str::before(trim($lead->name), ' ') ?: $lead->name;
@endphp

<x-mail.layout :company="$company">
    <p style="margin:0 0 16px;font-size:22px;font-weight:700;letter-spacing:-0.02em;">Bună, {{ $first }}!</p>

    <p style="margin:0 0 16px;">Îți mulțumim că ne-ai scris. Am primit mesajul tău și îți răspundem cât de curând.</p>

    <p style="margin:0 0 8px;font-size:14px;color:#6b6f8a;">Ce ne-ai trimis:</p>
    <div style="margin:0 0 24px;padding:16px 20px;background:#f3f1fb;border-radius:12px;white-space:pre-wrap;word-break:break-word;">{{ $lead->message }}</div>

    @if ($company->phone || $company->email)
        <p style="margin:0 0 4px;">Dacă vrei să ne spui ceva între timp:</p>
        <p style="margin:0 0 16px;">
            @if ($company->phone)
                <a href="{{ \App\Support\PhoneLink::href($company->phone) }}" style="color:#5b45e6;">{{ $company->phone }}</a>
            @endif
            @if ($company->phone && $company->email)
                &nbsp;·&nbsp;
            @endif
            @if ($company->email)
                <a href="mailto:{{ $company->email }}" style="color:#5b45e6;">{{ $company->email }}</a>
            @endif
        </p>
    @endif

    <p style="margin:0;">Echipa {{ $company->name }}</p>
</x-mail.layout>
