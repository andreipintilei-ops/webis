<script setup lang="ts">
import { ImagePlus, X } from '@lucide/vue';
import { ref, toRef } from 'vue';
import MediaPicker from '@/components/admin/media/MediaPicker.vue';
import { Button } from '@/components/ui/button';
import { useAssetPreview } from '@/composables/useAssetPreview';
import type { Asset } from '@/types/media';

/**
 * A form field holding one asset id: shows the image and lets the editor
 * choose, change or remove it.
 */
defineProps<{ id?: string }>();

const model = defineModel<number | null>({ default: null });
const pickerOpen = ref(false);
const { asset, remember } = useAssetPreview(toRef(model));

function onSelect(assets: Asset[]): void {
    const [chosen] = assets;

    if (chosen) {
        remember(chosen);
        model.value = chosen.id;
    }
}
</script>

<template>
    <div :id="id" class="flex items-start gap-3">
        <button
            type="button"
            class="flex size-24 shrink-0 items-center justify-center overflow-hidden rounded-lg border bg-muted/40 hover:bg-muted"
            @click="pickerOpen = true"
        >
            <img
                v-if="model && asset?.thumb_url"
                :src="asset.thumb_url"
                :alt="asset.alt ?? ''"
                class="size-full object-contain"
            />
            <ImagePlus v-else class="size-6 text-muted-foreground" />
        </button>

        <div class="flex min-w-0 flex-col gap-1.5">
            <template v-if="model && asset">
                <span class="truncate text-sm">{{
                    asset.title ?? asset.file_name
                }}</span>
                <span
                    v-if="!asset.alt"
                    class="text-xs text-amber-700 dark:text-amber-400"
                >
                    Imaginea nu are text alternativ.
                </span>
            </template>
            <div class="flex gap-2">
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    @click="pickerOpen = true"
                >
                    {{ model ? 'Schimbă' : 'Alege imaginea' }}
                </Button>
                <Button
                    v-if="model"
                    type="button"
                    variant="ghost"
                    size="sm"
                    @click="model = null"
                >
                    <X /> Elimină
                </Button>
            </div>
        </div>

        <MediaPicker v-model:open="pickerOpen" @select="onSelect" />
    </div>
</template>
