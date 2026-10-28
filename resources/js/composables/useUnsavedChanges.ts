import { router } from '@inertiajs/vue3';
import { onBeforeUnmount, onMounted } from 'vue';

/**
 * Warn before leaving a form with unsaved changes — on in-app navigation and
 * on closing or reloading the tab. Form submissions themselves pass through.
 */
export function useUnsavedChanges(isDirty: () => boolean): void {
    let removeListener: (() => void) | undefined;

    function onBeforeUnload(event: BeforeUnloadEvent): void {
        if (isDirty()) {
            event.preventDefault();
        }
    }

    onMounted(() => {
        window.addEventListener('beforeunload', onBeforeUnload);

        removeListener = router.on('before', (event) => {
            const visit = event.detail.visit;

            if (visit.method !== 'get' || !isDirty()) {
                return;
            }

            if (
                !window.confirm(
                    'Ai modificări nesalvate. Părăsești pagina fără să salvezi?',
                )
            ) {
                event.preventDefault();
            }
        });
    });

    onBeforeUnmount(() => {
        window.removeEventListener('beforeunload', onBeforeUnload);
        removeListener?.();
    });
}
