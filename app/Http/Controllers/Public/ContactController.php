<?php

namespace App\Http\Controllers\Public;

use App\Enums\LeadStatus;
use App\Http\Controllers\Controller;
use App\Mail\LeadConfirmation;
use App\Mail\LeadReceived;
use App\Models\Lead;
use App\Settings\LeadSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

/**
 * The contact form (components/site/contact.blade.php): stores the request as
 * a lead for the admin inbox (Cereri), and emails it to the addresses in
 * Setări › Cereri — the visitor too, a confirmation, when that is on.
 *
 * Answers JSON to the enhanced form (js/public/contact-form.ts) and redirects
 * back to the form without JavaScript. The `website` field is a honeypot,
 * hidden from people: a filled one is stored as spam (recoverable from the
 * inbox) and answered as if accepted.
 */
final class ContactController extends Controller
{
    public function store(Request $request, LeadSettings $leads): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'organization' => ['nullable', 'string', 'max:160'],
            // At least one way to answer.
            'email' => ['nullable', 'required_without:phone', 'email:rfc', 'max:190'],
            'phone' => ['nullable', 'required_without:email', 'string', 'max:40'],
            'message' => ['required', 'string', 'max:5000'],
            'website' => ['nullable', 'string', 'max:255'],
        ]);

        $lead = Lead::create([
            'name' => $data['name'],
            'company' => $data['organization'] ?? null,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'message' => $data['message'],
            'status' => filled($data['website'] ?? null) ? LeadStatus::Spam : LeadStatus::New,
            // Submitting is the consent; the form links the privacy policy.
            'consent_at' => now(),
            'source_url' => $request->headers->get('referer'),
            'ip_address' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 512),
        ]);

        // Spam is kept quiet: no one is told of it, nor answered.
        if ($lead->status !== LeadStatus::Spam) {
            if ($leads->notification_emails !== []) {
                Mail::to($leads->notification_emails)->queue(new LeadReceived($lead));
            }

            if ($leads->send_confirmation && $lead->email) {
                Mail::to($lead->email)->queue(new LeadConfirmation($lead));
            }
        }

        if ($request->expectsJson()) {
            return response()->json(['sent' => true], 201);
        }

        return back()->with('contact-sent', true)->withFragment('contact');
    }
}
