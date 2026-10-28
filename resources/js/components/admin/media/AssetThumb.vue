<script setup lang="ts">
import { ImageOff, TriangleAlert } from '@lucide/vue';
import type { Asset } from '@/types/media';

defineProps<{
    asset: Asset;
    selected?: boolean;
}>();
</script>

<template>
    <div
        class="group relative aspect-square overflow-hidden rounded-lg border bg-[repeating-conic-gradient(var(--color-muted)_0_25%,transparent_0_50%)] bg-size-[16px_16px]"
        :class="selected ? 'ring-2 ring-primary ring-offset-2' : ''"
    >
        <img
            v-if="asset.thumb_url"
            :src="asset.thumb_url"
            :alt="asset.alt ?? ''"
            loading="lazy"
            class="size-full object-contain"
        />
        <ImageOff
            v-else
            class="absolute inset-0 m-auto size-6 text-muted-foreground"
        />

        <span
            v-if="!asset.alt"
            class="absolute top-1.5 left-1.5 flex items-center gap-1 rounded bg-amber-100 px-1.5 py-0.5 text-[10px] font-medium text-amber-900"
            title="Lipsește textul alternativ"
        >
            <TriangleAlert class="size-3" /> fără alt
        </span>

        <span
            class="absolute inset-x-0 bottom-0 truncate bg-background/85 px-2 py-1 text-xs"
        >
            {{ asset.title ?? asset.file_name }}
        </span>
    </div>
</template>
