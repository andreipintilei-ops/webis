import { ref, watch } from 'vue';
import type { Ref } from 'vue';
import { api } from '@/lib/adminApi';
import { show } from '@/routes/admin/media';
import type { Asset } from '@/types/media';

/** Assets already fetched in this page session, keyed by id. */
const cache = new Map<number, Asset>();

/**
 * The asset behind an id held in a form, for showing its thumbnail. Content
 * stores only asset ids, so editors look the rest up here.
 */
export function useAssetPreview(id: Ref<number | null | undefined>) {
    const asset = ref<Asset | null>(null);

    async function resolve(value: number | null | undefined): Promise<void> {
        if (!value) {
            asset.value = null;

            return;
        }

        const cached = cache.get(value);

        if (cached) {
            asset.value = cached;

            return;
        }

        try {
            const response = await api<{ asset: Asset }>(
                'get',
                show.url(value),
            );
            cache.set(value, response.asset);

            // Ignore a late answer for an id that has since changed.
            if (id.value === value) {
                asset.value = response.asset;
            }
        } catch {
            asset.value = null;
        }
    }

    watch(id, resolve, { immediate: true });

    return {
        asset,
        remember: (value: Asset) => cache.set(value.id, value),
    };
}
