{{-- App\Mail\LeadReceived: a new request, to the team. --}}
@php
    $rows = array_filter([
        'Nume' => $lead->name,
        'Organizație' => $lead->company,
        'Email' => $lead->email,
        'Telefon' => $lead->phone,
        'Trimisă de pe' => $lead->source_url,
    ]);
@endphp

<x-mail.layout>
    <p style="margin:0 0 24px;font-size:22px;font-weight:700;letter-spacing:-0.02em;">Cerere nouă de la {{ $lead->name }}</p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 24px;font-size:15px;">
        @foreach ($rows as $label => $value)
            <tr>
                <td style="padding:6px 16px 6px 0;color:#6b6f8a;white-space:nowrap;vertical-align:top;">{{ $label }}</td>
                <td style="padding:6px 0;word-break:break-word;">
                    @if ($label === 'Email')
                        <a href="mailto:{{ $value }}" style="color:#5b45e6;">{{ $value }}</a>
                    @elseif ($label === 'Telefon')
                        <a href="{{ \App\Support\PhoneLink::href($value) }}" style="color:#5b45e6;">{{ $value }}</a>
                    @else
                        {{ $value }}
                    @endif
                </td>
            </tr>
        @endforeach
    </table>

    <div style="margin:0 0 28px;padding:16px 20px;background:#f3f1fb;border-radius:12px;white-space:pre-wrap;word-break:break-word;">{{ $lead->message }}</div>

    <a href="{{ $adminUrl }}" style="display:inline-block;padding:12px 24px;background:#5b45e6;border-radius:999px;color:#ffffff;font-weight:600;text-decoration:none;">Deschide cererea</a>

    @if ($lead->email)
        <p style="margin:24px 0 0;font-size:14px;color:#6b6f8a;">Un răspuns la acest email îi ajunge direct lui {{ $lead->name }}.</p>
    @endif
</x-mail.layout>
