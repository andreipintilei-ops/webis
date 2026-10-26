{{--
    Cookie consent in the <head>, before anything else
    (Support\Consent\CookieConsent). Google's consent signals (Consent Mode
    v2) all start denied; the visitor's saved choice, if any, then grants
    what it allows, and only then is each tool's script fetched — nothing
    loads before a yes:

    - analytics: Google Analytics 4 (gtag.js), Hotjar, Microsoft Clarity;
    - marketing: Google Ads (gtag.js), Meta Pixel, LinkedIn Insight;
    - Google Tag Manager with either, its tags reading the signals.

    js/public/cookie-consent.ts calls window.webisConsent.apply() when the
    choice is made or changed; js/public/contact-form.ts calls lead().
--}}
@php($consent = app(\App\Support\Consent\CookieConsent::class))
@if ($consent->needed())
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag() { dataLayer.push(arguments); }
        gtag('consent', 'default', {
            ad_storage: 'denied',
            ad_user_data: 'denied',
            ad_personalization: 'denied',
            analytics_storage: 'denied',
            functionality_storage: 'granted',
            security_storage: 'granted',
            wait_for_update: 500,
        });
        gtag('set', 'ads_data_redaction', true);

        (function (c) {
            var loaded = {};
            var choice = { analytics: false, marketing: false };

            function script(src) {
                var s = document.createElement('script');
                s.async = true;
                s.src = src;
                document.head.appendChild(s);
            }

            // Each tool's loader, run once, when its category is allowed.
            var tools = {
                gtag: function (id) {
                    gtag('js', new Date());
                    script('https://www.googletagmanager.com/gtag/js?id=' + id);
                },
                ga4: function (id) { gtag('config', id); },
                ads: function (id) { gtag('config', id); },
                gtm: function (id) {
                    dataLayer.push({ 'gtm.start': Date.now(), event: 'gtm.js' });
                    script('https://www.googletagmanager.com/gtm.js?id=' + id);
                },
                hotjar: function (id) {
                    window.hj = window.hj || function () { (hj.q = hj.q || []).push(arguments); };
                    window._hjSettings = { hjid: Number(id), hjsv: 6 };
                    script('https://static.hotjar.com/c/hotjar-' + id + '.js?sv=6');
                },
                clarity: function (id) {
                    window.clarity = window.clarity || function () { (clarity.q = clarity.q || []).push(arguments); };
                    script('https://www.clarity.ms/tag/' + id);
                },
                meta: function (id) {
                    var n = window.fbq = function () {
                        n.callMethod ? n.callMethod.apply(n, arguments) : n.queue.push(arguments);
                    };
                    window._fbq = n;
                    n.push = n;
                    n.loaded = true;
                    n.version = '2.0';
                    n.queue = [];
                    script('https://connect.facebook.net/en_US/fbevents.js');
                    fbq('init', id);
                    fbq('track', 'PageView');
                },
                linkedin: function (id) {
                    window._linkedin_partner_id = id;
                    window._linkedin_data_partner_ids = [id];
                    window.lintrk = window.lintrk || function (a, b) { (lintrk.q = lintrk.q || []).push([a, b]); };
                    script('https://snap.licdn.com/li.lms-analytics/insight.min.js');
                },
            };

            function load(tool, id) {
                if (id && ! loaded[tool]) {
                    loaded[tool] = true;
                    tools[tool](id);
                }
            }

            // made: {analytics: bool, marketing: bool}.
            c.apply = function (made) {
                var ads = made.marketing ? 'granted' : 'denied';

                choice = made;
                gtag('consent', 'update', {
                    analytics_storage: made.analytics ? 'granted' : 'denied',
                    ad_storage: ads,
                    ad_user_data: ads,
                    ad_personalization: ads,
                });

                var t = c.tags;

                if (! t) {
                    return;
                }

                if ((made.analytics && t.ga4) || (made.marketing && t.ads)) {
                    load('gtag', made.analytics && t.ga4 ? t.ga4 : t.ads);
                }

                if (made.analytics) {
                    load('ga4', t.ga4);
                    load('hotjar', t.hotjar);
                    load('clarity', t.clarity);
                }

                if (made.marketing) {
                    load('ads', t.ads);
                    load('meta', t.meta);
                    load('linkedin', t.linkedin);
                }

                if (made.analytics || made.marketing) {
                    load('gtm', t.gtm);
                }
            };

            // A lead sent (the contact form): each allowed tool's conversion.
            c.lead = function () {
                dataLayer.push({ event: 'generate_lead' });

                if (! c.tags) {
                    return;
                }

                if (choice.analytics && loaded.ga4) gtag('event', 'generate_lead');
                if (choice.marketing && loaded.ads && c.tags.adsLead) gtag('event', 'conversion', { send_to: c.tags.adsLead });
                if (choice.marketing && loaded.meta) fbq('track', 'Lead');
            };

            try {
                var saved = document.cookie.match(new RegExp('(?:^|; )' + c.cookie + '=([^;]*)'));
                var value = saved && JSON.parse(decodeURIComponent(saved[1]));

                if (value && value.v === c.version) {
                    c.choice = { analytics: !! value.a, marketing: !! value.m };
                    c.apply(c.choice);
                }
            } catch (e) {}
        })(window.webisConsent = @json($consent->client(request())));
    </script>
@endif
