{{-- App\Mail\LeadReceived, as plain text: not HTML, so nothing to escape. --}}
Cerere nouă de la {!! $lead->name !!}

@foreach (array_filter(['Nume' => $lead->name, 'Organizație' => $lead->company, 'Email' => $lead->email, 'Telefon' => $lead->phone, 'Trimisă de pe' => $lead->source_url]) as $label => $value)
{{ $label }}: {!! $value !!}
@endforeach

{!! $lead->message !!}

Deschide cererea: {{ $adminUrl }}
