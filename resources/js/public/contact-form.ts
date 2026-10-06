/**
 * The contact form (components/site/contact.blade.php), sent in place: no
 * reload — which would replay the hero intro and lose the scroll — errors
 * shown by their fields, a thank-you where the form was. Without JavaScript
 * the form simply posts and comes back (ContactController).
 */
export function initContactForm(): void {
    const form = document.querySelector<HTMLFormElement>('[data-contact-form]');
    const sent = document.querySelector<HTMLElement>('[data-contact-sent]');

    if (!form || !sent) {
        return;
    }

    const submit = form.querySelector<HTMLButtonElement>(
        '[data-contact-submit]',
    );

    const showErrors = (errors: Record<string, string[]>): void => {
        for (const slot of form.querySelectorAll<HTMLElement>(
            '[data-error-for]',
        )) {
            const field = slot.dataset.errorFor ?? '';
            const message = errors[field]?.[0] ?? '';
            const input = form.elements.namedItem(field);

            slot.textContent = message;

            if (input instanceof HTMLElement) {
                if (message === '') {
                    input.removeAttribute('aria-invalid');
                } else {
                    input.setAttribute('aria-invalid', 'true');
                }
            }
        }

        // Into the first field that needs fixing.
        const first = Object.keys(errors)[0];
        const input = first ? form.elements.namedItem(first) : null;

        if (input instanceof HTMLElement) {
            input.focus();
        }
    };

    form.addEventListener('submit', (event) => {
        event.preventDefault();

        if (submit) {
            submit.disabled = true;
        }

        void fetch(form.action, {
            method: 'POST',
            headers: { Accept: 'application/json' },
            body: new FormData(form),
        })
            .then(async (response) => {
                if (response.status === 201) {
                    form.hidden = true;
                    form.classList.add('hidden');
                    sent.classList.remove('hidden');
                    sent.focus?.();

                    return;
                }

                if (response.status === 422) {
                    const body = (await response.json()) as {
                        errors?: Record<string, string[]>;
                    };
                    showErrors(body.errors ?? {});

                    return;
                }

                showErrors({
                    form: [
                        response.status === 429
                            ? 'Prea multe trimiteri. Încercați din nou peste un minut.'
                            : 'Nu am putut trimite mesajul. Încercați din nou sau sunați-ne.',
                    ],
                });
            })
            .catch(() =>
                showErrors({
                    form: [
                        'Nu am putut trimite mesajul. Verificați conexiunea și încercați din nou.',
                    ],
                }),
            )
            .finally(() => {
                if (submit) {
                    submit.disabled = false;
                }
            });
    });
}
