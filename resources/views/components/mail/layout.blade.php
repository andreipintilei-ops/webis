{{-- The site's emails (App\Mail): one white card on a light ground, the
     hero's indigo for the name at the top and the button. Tables and inline
     styles, as mail clients need them. `$slot`: the card's content. --}}
@props(['company' => app(\App\Settings\CompanySettings::class)])
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>{{ $company->name }}</title>
</head>
<body style="margin:0;padding:0;background:#f3f1fb;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#141833;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f3f1fb;">
        <tr>
            <td align="center" style="padding:32px 16px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;">
                    <tr>
                        <td style="padding:0 8px 16px;font-size:20px;font-weight:700;letter-spacing:-0.02em;color:#1f2868;">{{ $company->name }}</td>
                    </tr>
                    <tr>
                        <td style="background:#ffffff;border-radius:16px;padding:32px;font-size:16px;line-height:1.6;">
                            {{ $slot }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
