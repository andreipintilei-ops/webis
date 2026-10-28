<?php

namespace App\Support\Consent;

use App\Settings\AnalyticsSettings;
use App\Support\Seo\Indexing;
use Illuminate\Http\Request;

/**
 * Cookie consent: what the visitor is asked, and what it unlocks.
 *
 * Only the cookies the site needs to work are set without asking. The
 * tracking tools set in the admin (AnalyticsSettings, Setări › Analiză)
 * wait for the visitor's yes to their category (TOOLS):
 *
 * - analytics: Google Analytics 4, Hotjar, Microsoft Clarity;
 * - marketing: Google Ads, Meta Pixel, LinkedIn Insight;
 * - Google Tag Manager loads with either, its own tags reading Google's
 *   consent signals.
 *
 * A category is offered only while one of its tools is set; with none,
 * there is nothing to ask and no banner. No tool's script is even
 * downloaded before a yes ("basic" Consent Mode v2): the layout's head
 * sets every Google consent signal to denied, then reads the visitor's
 * choice from the cookie (COOKIE) and loads what it allows
 * (views/partials/consent-head, js/public/cookie-consent.ts).
 *
 * The tools load only on the live site (Indexing::siteAllowed), so local
 * and staging visits never reach the statistics; the banner works
 * everywhere.
 *
 * The choice is asked again after DAYS, or as soon as VERSION goes up —
 * raise it when a new category is added.
 */
final class CookieConsent
{
    public const string COOKIE = 'webis_consent';

    public const int VERSION = 1;

    public const int DAYS = 180;

    /**
     * Each tool: its setting, its name for visitors, its category (null:
     * either).
     *
     * @var array<string, array{setting: string, name: string, category: ?string}>
     */
    public const array TOOLS = [
        'ga4' => ['setting' => 'ga4_measurement_id', 'name' => 'Google Analytics', 'category' => 'analytics'],
        'hotjar' => ['setting' => 'hotjar_site_id', 'name' => 'Hotjar', 'category' => 'analytics'],
        'clarity' => ['setting' => 'clarity_project_id', 'name' => 'Microsoft Clarity', 'category' => 'analytics'],
        'ads' => ['setting' => 'google_ads_id', 'name' => 'Google Ads', 'category' => 'marketing'],
        'meta' => ['setting' => 'meta_pixel_id', 'name' => 'Meta Pixel', 'category' => 'marketing'],
        'linkedin' => ['setting' => 'linkedin_partner_id', 'name' => 'LinkedIn Insight', 'category' => 'marketing'],
        'gtm' => ['setting' => 'gtm_container_id', 'name' => 'Google Tag Manager', 'category' => null],
    ];

    public function __construct(private readonly AnalyticsSettings $analytics) {}

    /** A tool's id, if it is set. */
    public function id(string $tool): ?string
    {
        return $this->analytics->{self::TOOLS[$tool]['setting']} ?: null;
    }

    /**
     * The tools set, by key.
     *
     * @return array<string, string>
     */
    public function ids(): array
    {
        return array_filter(array_map(fn (string $tool): ?string => $this->id($tool), array_combine(array_keys(self::TOOLS), array_keys(self::TOOLS))));
    }

    /**
     * The optional categories on offer, with whether each is.
     *
     * @return array{analytics: bool, marketing: bool}
     */
    public function categories(): array
    {
        $on = fn (string $category): bool => collect($this->ids())
            ->keys()
            ->contains(fn (string $tool): bool => in_array(self::TOOLS[$tool]['category'], [$category, null], true));

        return ['analytics' => $on('analytics'), 'marketing' => $on('marketing')];
    }

    /**
     * The names of a category's tools that are set, for the banner;
     * Tag Manager's only when nothing else names it.
     *
     * @return list<string>
     */
    public function names(string $category): array
    {
        $tools = array_keys($this->ids());
        $names = array_values(array_map(
            fn (string $tool): string => self::TOOLS[$tool]['name'],
            array_filter($tools, fn (string $tool): bool => self::TOOLS[$tool]['category'] === $category),
        ));

        return $names === [] && in_array('gtm', $tools, true) ? ['Google Tag Manager'] : $names;
    }

    /** Whether there is anything to ask: the banner, the settings link. */
    public function needed(): bool
    {
        return in_array(true, $this->categories(), true);
    }

    /**
     * What the head script needs: the cookie, and the tools it may load
     * (none off the live site).
     *
     * @return array{cookie: string, version: int, days: int, categories: array{analytics: bool, marketing: bool}, tags: array<string, string>|null}
     */
    public function client(Request $request): array
    {
        $tags = $this->ids();

        if (isset($tags['ads']) && $this->analytics->google_ads_lead_label) {
            $tags['adsLead'] = $tags['ads'].'/'.$this->analytics->google_ads_lead_label;
        }

        return [
            'cookie' => self::COOKIE,
            'version' => self::VERSION,
            'days' => self::DAYS,
            'categories' => $this->categories(),
            // An object even when empty, for the script.
            'tags' => Indexing::siteAllowed($request) ? (object) $tags : null,
        ];
    }

    /**
     * The cookie policy's tables: every cookie the site may set, by
     * category. A tool's rows are listed only while it is set.
     *
     * @return array<string, array{title: string, intro: string, rows: list<array{name: string, provider: string, purpose: string, duration: string}>}>
     */
    public function table(): array
    {
        $sessionMinutes = (int) config('session.lifetime');
        $ids = $this->ids();

        $groups = [
            'necessary' => [
                'title' => 'Necesare',
                'intro' => 'Fără ele site-ul nu funcționează: păstrează sesiunea, protejează formularul de contact și țin minte alegerea ta despre cookie-uri. Nu cer acord și nu pot fi oprite din setări.',
                'rows' => [
                    ['name' => (string) config('session.cookie'), 'provider' => 'webis.ro', 'purpose' => 'Sesiunea vizitei: leagă cererile tale de același vizitator, de exemplu la trimiterea formularului.', 'duration' => $sessionMinutes.' de minute'],
                    ['name' => 'XSRF-TOKEN', 'provider' => 'webis.ro', 'purpose' => 'Securitate: oprește trimiterea formularelor în numele tău de pe alte site-uri.', 'duration' => $sessionMinutes.' de minute'],
                    ['name' => self::COOKIE, 'provider' => 'webis.ro', 'purpose' => 'Ține minte ce ai ales despre cookie-uri, ca să nu te întrebăm la fiecare pagină.', 'duration' => self::DAYS.' de zile'],
                ],
            ],
        ];

        $gtm = isset($ids['gtm'])
            ? [['name' => '—', 'provider' => 'Google Tag Manager', 'purpose' => 'Nu pune cookie-uri; încarcă instrumentele configurate în el, doar cu acordul tău.', 'duration' => '—']]
            : [];

        $analytics = [
            ...(isset($ids['ga4']) ? [
                ['name' => '_ga', 'provider' => 'Google Analytics', 'purpose' => 'Deosebește vizitatorii între ele, cu un identificator aleator.', 'duration' => '2 ani'],
                ['name' => '_ga_'.str($ids['ga4'])->after('G-'), 'provider' => 'Google Analytics', 'purpose' => 'Păstrează starea sesiunii pentru măsurare.', 'duration' => '2 ani'],
            ] : []),
            ...(isset($ids['hotjar']) ? [
                ['name' => '_hjSessionUser_'.$ids['hotjar'], 'provider' => 'Hotjar', 'purpose' => 'Recunoaște vizitatorul la vizitele următoare, cu un identificator aleator.', 'duration' => '1 an'],
                ['name' => '_hjSession_'.$ids['hotjar'], 'provider' => 'Hotjar', 'purpose' => 'Ține împreună datele aceleiași sesiuni (hărți de click și de derulare).', 'duration' => '30 de minute'],
            ] : []),
            ...(isset($ids['clarity']) ? [
                ['name' => '_clck', 'provider' => 'Microsoft Clarity', 'purpose' => 'Recunoaște vizitatorul la vizitele următoare, cu un identificator aleator.', 'duration' => '1 an'],
                ['name' => '_clsk', 'provider' => 'Microsoft Clarity', 'purpose' => 'Leagă paginile văzute într-o sesiune (înregistrări ale sesiunii).', 'duration' => '1 zi'],
                ['name' => 'CLID, MUID', 'provider' => 'Microsoft (clarity.ms, bing.com)', 'purpose' => 'Identifică browserul între site-urile care folosesc Clarity.', 'duration' => 'până la 1 an'],
            ] : []),
        ];

        $marketing = [
            ...(isset($ids['ads']) ? [
                ['name' => '_gcl_au', 'provider' => 'Google Ads', 'purpose' => 'Leagă o vizită venită dintr-o reclamă de o cerere trimisă (conversie).', 'duration' => '90 de zile'],
                ['name' => 'IDE, NID, ar_debug', 'provider' => 'Google (doubleclick.net, google.com)', 'purpose' => 'Măsurarea reclamelor și reclame personalizate, pe domeniile Google.', 'duration' => 'până la 13 luni'],
            ] : []),
            ...(isset($ids['meta']) ? [
                ['name' => '_fbp', 'provider' => 'Meta (Facebook, Instagram)', 'purpose' => 'Leagă vizitele de reclamele Meta, pentru măsurare și public personalizat.', 'duration' => '90 de zile'],
                ['name' => 'fr', 'provider' => 'Meta (facebook.com)', 'purpose' => 'Afișarea și măsurarea reclamelor, pe domeniul Facebook.', 'duration' => '90 de zile'],
            ] : []),
            ...(isset($ids['linkedin']) ? [
                ['name' => 'li_sugr, bcookie, lidc, UserMatchHistory', 'provider' => 'LinkedIn (linkedin.com)', 'purpose' => 'Măsurarea reclamelor LinkedIn și public personalizat.', 'duration' => 'până la 1 an'],
            ] : []),
        ];

        // Tag Manager under Analiză, and under Marketing too while nothing else is there.
        if ($analytics !== [] || $gtm !== []) {
            $groups['analytics'] = [
                'title' => 'Analiză',
                'intro' => 'Ne arată, în mare, cum e folosit site-ul: ce pagini sunt citite, de unde vin vizitatorii, unde se opresc. Nu le folosim ca să te identificăm. Se pun doar cu acordul tău.',
                'rows' => [...$gtm, ...$analytics],
            ];
        }

        if ($marketing !== [] || $gtm !== []) {
            $groups['marketing'] = [
                'title' => 'Marketing',
                'intro' => 'Ne ajută să aflăm dacă reclamele noastre aduc cereri și să le arătăm celor interesați. Se pun doar cu acordul tău.',
                'rows' => $marketing ?: $gtm,
            ];
        }

        return $groups;
    }

    /**
     * The companies the optional tools send data to, for the policy.
     *
     * @return list<array{name: string, tools: string, url: string}>
     */
    public function providers(): array
    {
        $ids = $this->ids();
        $google = collect(['ga4', 'ads', 'gtm'])->filter(fn (string $tool): bool => isset($ids[$tool]))->map(fn (string $tool): string => self::TOOLS[$tool]['name'])->join(', ');

        return array_values(array_filter([
            $google !== '' ? ['name' => 'Google Ireland Limited', 'tools' => $google, 'url' => 'https://policies.google.com/privacy'] : null,
            isset($ids['meta']) ? ['name' => 'Meta Platforms Ireland Limited', 'tools' => 'Meta Pixel', 'url' => 'https://www.facebook.com/privacy/policy/'] : null,
            isset($ids['linkedin']) ? ['name' => 'LinkedIn Ireland Unlimited Company', 'tools' => 'LinkedIn Insight', 'url' => 'https://www.linkedin.com/legal/privacy-policy'] : null,
            isset($ids['hotjar']) ? ['name' => 'Hotjar Ltd. (Malta)', 'tools' => 'Hotjar', 'url' => 'https://www.hotjar.com/legal/policies/privacy/'] : null,
            isset($ids['clarity']) ? ['name' => 'Microsoft Ireland Operations Limited', 'tools' => 'Microsoft Clarity', 'url' => 'https://privacy.microsoft.com/privacystatement'] : null,
        ]));
    }
}
