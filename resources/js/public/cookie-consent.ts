/**
 * The cookie banner (components/site/cookie-consent.blade.php).
 *
 * The head (views/partials/consent-head) has already set Google's consent
 * signals to denied and applied a saved choice, through
 * window.webisConsent. This shows the banner while there is no current
 * choice, saves the one made (a first-party cookie, `version` and date
 * included) and hands it to webisConsent.apply(), which loads the
 * tracking tools only for what was allowed.
 *
 * Any [data-cookie-settings] button reopens it with the switches showing
 * the current choice. Taking back something allowed before deletes the
 * tools' cookies and reloads the page, so their scripts stop at once.
 */

type Choice = { analytics: boolean; marketing: boolean };

type ConsentConfig = {
    cookie: string;
    version: number;
    days: number;
    categories: Choice;
    choice?: Choice;
    apply: (choice: Choice) => void;
    lead: () => void;
};

declare global {
    interface Window {
        webisConsent?: ConsentConfig;
    }
}

// The tools' own first-party cookies, for when consent is taken back.
const TRACKING_COOKIES =
    /^(_ga|_ga_.+|_gid|_gcl_.+|_hj.+|_clck|_clsk|_fbp|_fbc|_uetsid|_uetvid)$/;

function save(config: ConsentConfig, choice: Choice): void {
    const value = encodeURIComponent(
        JSON.stringify({
            v: config.version,
            a: choice.analytics ? 1 : 0,
            m: choice.marketing ? 1 : 0,
            t: new Date().toISOString(),
        }),
    );
    const secure = location.protocol === 'https:' ? ';Secure' : '';

    document.cookie = `${config.cookie}=${value};path=/;max-age=${config.days * 86400};SameSite=Lax${secure}`;
}

function forgetTracking(): void {
    // Set on the parent domain too (.webis.ro); try each level.
    const parts = location.hostname.split('.');
    const domains = [''];

    for (let i = 0; i < parts.length - 1; i++) {
        domains.push(`;domain=.${parts.slice(i).join('.')}`);
    }

    document.cookie
        .split('; ')
        .map((pair) => pair.split('=')[0])
        .filter((name) => TRACKING_COOKIES.test(name))
        .forEach((name) =>
            domains.forEach((domain) => {
                document.cookie = `${name}=;path=/;max-age=0${domain}`;
            }),
        );
}

export function initCookieConsent(): void {
    const config = window.webisConsent;
    const banner = document.querySelector<HTMLElement>('[data-cookie-consent]');

    if (!config || !banner) {
        return;
    }

    const choices = banner.querySelector<HTMLElement>('[data-cookie-choices]');
    const switches = [
        ...banner.querySelectorAll<HTMLInputElement>('[data-cookie-category]'),
    ];
    const customise = banner.querySelector<HTMLButtonElement>(
        '[data-cookie-customise]',
    );
    const saveButton = banner.querySelector<HTMLElement>('[data-cookie-save]');
    const reject = banner.querySelector<HTMLElement>('[data-cookie-reject]');
    let opener: HTMLElement | null = null;

    const setCustomising = (on: boolean): void => {
        choices?.toggleAttribute('hidden', !on);
        saveButton?.toggleAttribute('hidden', !on);
        reject?.toggleAttribute('hidden', on);
        customise?.toggleAttribute('hidden', on);
        customise?.setAttribute('aria-expanded', String(on));
    };

    const show = (customising: boolean): void => {
        switches.forEach((input) => {
            input.checked = Boolean(
                config.choice?.[input.dataset.cookieCategory as keyof Choice],
            );
        });
        setCustomising(customising);
        banner.hidden = false;
        banner.classList.remove('is-entering');
        void banner.offsetWidth;
        banner.classList.add('is-entering');
    };

    const choose = (choice: Choice): void => {
        const before = config.choice;
        // Only what is on offer can be allowed.
        const made: Choice = {
            analytics: choice.analytics && config.categories.analytics,
            marketing: choice.marketing && config.categories.marketing,
        };

        save(config, made);
        config.choice = made;
        banner.hidden = true;
        opener?.focus();
        opener = null;

        const takenBack =
            (before?.analytics && !made.analytics) ||
            (before?.marketing && !made.marketing);

        if (takenBack) {
            forgetTracking();
            location.reload();

            return;
        }

        config.apply(made);
    };

    banner
        .querySelector('[data-cookie-accept]')
        ?.addEventListener('click', () =>
            choose({ analytics: true, marketing: true }),
        );
    reject?.addEventListener('click', () =>
        choose({ analytics: false, marketing: false }),
    );
    saveButton?.addEventListener('click', () => {
        const on = (category: keyof Choice): boolean =>
            switches.some(
                (input) =>
                    input.dataset.cookieCategory === category && input.checked,
            );

        choose({ analytics: on('analytics'), marketing: on('marketing') });
    });
    customise?.addEventListener('click', () => {
        setCustomising(true);
        switches[0]?.focus();
    });

    document
        .querySelectorAll<HTMLElement>('[data-cookie-settings]')
        .forEach((button) =>
            button.addEventListener('click', (event) => {
                event.preventDefault();
                opener = button;
                show(true);
                banner.focus();
            }),
        );

    // A moment after the page shows; after the home page's intro has played.
    if (!config.choice) {
        const intro = document.documentElement.classList.contains('intro');

        window.setTimeout(
            () => {
                if (!config.choice && banner.hidden) {
                    show(false);
                }
            },
            intro ? 2600 : 600,
        );
    }
}
