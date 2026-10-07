<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Brand\GenerateFavicons;
use App\Http\Controllers\Controller;
use App\Settings\AnalyticsSettings;
use App\Settings\BrandSettings;
use App\Settings\CompanySettings;
use App\Settings\LeadSettings;
use App\Settings\SeoSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Site settings (admin only): one page, one tab per group, each saved on its
 * own so a validation error in one never blocks another.
 */
class SettingsController extends Controller
{
    public const array SOCIAL_PLATFORMS = ['facebook', 'instagram', 'linkedin', 'youtube', 'tiktok'];

    public const array DAYS = ['Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa', 'Su'];

    /**
     * GET /admin/setari
     */
    public function edit(CompanySettings $company, SeoSettings $seo, AnalyticsSettings $analytics, LeadSettings $leads, BrandSettings $brand): Response
    {
        return Inertia::render('admin/settings/Edit', [
            'brand' => [
                'logo_asset_id' => $brand->logo_asset_id,
                'logo_negative_asset_id' => $brand->logo_negative_asset_id,
                'favicon_asset_id' => $brand->favicon_asset_id,
                'icon_asset_id' => $brand->icon_asset_id,
            ],
            'company' => $company->toArray(),
            'seo' => $seo->toArray(),
            'analytics' => $analytics->toArray(),
            'leads' => $leads->toArray(),
            'socialPlatforms' => self::SOCIAL_PLATFORMS,
            'days' => array_map(fn (string $day, string $label): array => ['value' => $day, 'label' => $label],
                self::DAYS, ['Lu', 'Ma', 'Mi', 'Jo', 'Vi', 'Sâ', 'Du']),
        ]);
    }

    /**
     * PUT /admin/setari/identitate — logos and favicon. The favicon files are
     * regenerated whenever this is saved.
     */
    public function updateBrand(Request $request, BrandSettings $settings, GenerateFavicons $generateFavicons): RedirectResponse
    {
        $settings->fill($request->validate([
            'logo_asset_id' => ['nullable', 'integer', Rule::exists('assets', 'id')],
            'logo_negative_asset_id' => ['nullable', 'integer', Rule::exists('assets', 'id')],
            'favicon_asset_id' => ['nullable', 'integer', Rule::exists('assets', 'id')],
            'icon_asset_id' => ['nullable', 'integer', Rule::exists('assets', 'id')],
        ]))->save();

        $generateFavicons($settings);

        return $this->saved();
    }

    /**
     * PUT /admin/setari/companie
     */
    public function updateCompany(Request $request, CompanySettings $settings): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'legal_name' => ['required', 'string', 'max:150'],
            'vat_id' => ['nullable', 'string', 'max:20'],
            'registration_number' => ['nullable', 'string', 'max:30'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'street_address' => ['nullable', 'string', 'max:255'],
            'locality' => ['required', 'string', 'max:100'],
            'region' => ['required', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'country_code' => ['required', 'string', 'size:2'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'opening_hours' => ['array', 'max:7'],
            'opening_hours.*.days' => ['required', 'array', 'min:1'],
            'opening_hours.*.days.*' => [Rule::in(self::DAYS)],
            'opening_hours.*.opens' => ['required', 'date_format:H:i'],
            'opening_hours.*.closes' => ['required', 'date_format:H:i', 'after:opening_hours.*.opens'],
            'social' => ['array'],
            'social.*' => ['nullable', 'url:https', 'max:255'],
            'google_rating' => ['nullable', 'numeric', 'between:1,5'],
            'google_review_count' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'google_reviews_url' => ['nullable', 'url:https', 'max:255'],
        ]);

        $settings->fill([
            ...$data,
            'google_rating' => isset($data['google_rating']) ? round((float) $data['google_rating'], 1) : null,
            'google_review_count' => isset($data['google_review_count']) ? (int) $data['google_review_count'] : null,
            'google_reviews_url' => $data['google_reviews_url'] ?? null,
            'country_code' => mb_strtoupper($data['country_code']),
            'latitude' => isset($data['latitude']) ? (float) $data['latitude'] : null,
            'longitude' => isset($data['longitude']) ? (float) $data['longitude'] : null,
            'opening_hours' => array_values($data['opening_hours'] ?? []),
            // Only known platforms, only filled ones.
            'social' => array_filter(array_intersect_key($data['social'] ?? [], array_flip(self::SOCIAL_PLATFORMS))),
        ])->save();

        return $this->saved();
    }

    /**
     * PUT /admin/setari/seo
     */
    public function updateSeo(Request $request, SeoSettings $settings): RedirectResponse
    {
        $data = $request->validate([
            'title_suffix' => ['nullable', 'string', 'max:40'],
            'default_description' => ['nullable', 'string', 'max:320'],
            'default_og_image_asset_id' => ['nullable', 'integer', Rule::exists('assets', 'id')],
            'logo_asset_id' => ['nullable', 'integer', Rule::exists('assets', 'id')],
        ]);

        $settings->fill([
            ...$data,
            // Empty is allowed (no suffix); the request middleware turns "" into null.
            'title_suffix' => (string) ($data['title_suffix'] ?? ''),
        ])->save();

        return $this->saved();
    }

    /**
     * PUT /admin/setari/analiza
     */
    public function updateAnalytics(Request $request, AnalyticsSettings $settings): RedirectResponse
    {
        $settings->fill($request->validate([
            'ga4_measurement_id' => ['nullable', 'string', 'regex:/^G-[A-Z0-9]{4,}$/'],
            'google_ads_id' => ['nullable', 'string', 'regex:/^AW-\d{6,}$/'],
            'google_site_verification' => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9_-]+$/'],
            'bing_site_verification' => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9_-]+$/'],
        ], [
            'ga4_measurement_id.regex' => 'Format: G-XXXXXXXXXX.',
            'google_ads_id.regex' => 'Format: AW-123456789.',
            'google_site_verification.regex' => 'Doar codul din atributul content al etichetei meta.',
            'bing_site_verification.regex' => 'Doar codul din atributul content al etichetei meta.',
        ]))->save();

        return $this->saved();
    }

    /**
     * PUT /admin/setari/cereri
     */
    public function updateLeads(Request $request, LeadSettings $settings): RedirectResponse
    {
        $data = $request->validate([
            'notification_emails' => ['array', 'max:10'],
            'notification_emails.*' => ['required', 'email', 'max:255', 'distinct'],
            'budget_options' => ['array', 'max:12'],
            'budget_options.*' => ['required', 'string', 'max:60', 'distinct'],
            'send_confirmation' => ['boolean'],
        ]);

        $settings->fill([
            'notification_emails' => array_values($data['notification_emails'] ?? []),
            'budget_options' => array_values($data['budget_options'] ?? []),
            'send_confirmation' => $request->boolean('send_confirmation'),
        ])->save();

        return $this->saved();
    }

    private function saved(): RedirectResponse
    {
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Setările au fost salvate.']);

        return back();
    }
}
