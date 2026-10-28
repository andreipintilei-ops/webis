<script setup lang="ts">
import { NodeViewWrapper, nodeViewProps } from '@tiptap/vue-3';
import { ImageOff } from '@lucide/vue';
import { computed } from 'vue';
import { useAssetPreview } from '@/composables/useAssetPreview';

/** How an assetImage node looks inside the editor: the image and its caption. */
const props = defineProps(nodeViewProps);

const assetId = computed(() => {
    const value = props.node.attrs.assetId;

    return typeof value === 'number' ? value : null;
});

const { asset } = useAssetPreview(assetId);

const caption = computed({
    get: () => (props.node.attrs.caption as string | null) ?? '',
    set: (value: string) =>
        props.updateAttributes({ caption: value === '' ? null : value }),
});
</script>

<template>
    <NodeViewWrapper
        as="figure"
        class="my-4 flex flex-col gap-2 rounded-lg border p-2"
        :class="selected ? 'ring-2 ring-primary' : ''"
        data-drag-handle
    >
        <img
            v-if="asset?.thumb_url"
            :src="asset.url ?? asset.thumb_url"
            :alt="asset.alt ?? ''"
            class="mx-auto max-h-80 object-contain"
        />
        <div
            v-else
            class="flex h-24 items-center justify-center text-muted-foreground"
        >
            <ImageOff class="size-6" />
        </div>
        <input
            v-model="caption"
            type="text"
            placeholder="Legendă (opțional)"
            class="w-full rounded border-0 bg-transparent px-1 text-center text-sm text-muted-foreground outline-none focus:bg-muted/50"
            @keydown.stop
        />
    </NodeViewWrapper>
</template>
