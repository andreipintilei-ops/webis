<script setup lang="ts">
import { ImageUp } from '@lucide/vue';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { useMediaUpload } from '@/composables/useMediaUpload';
import type { Asset } from '@/types/media';

const emit = defineEmits<{ uploaded: [asset: Asset] }>();

const { items, add, clearFinished } = useMediaUpload((asset) =>
    emit('uploaded', asset),
);

const input = ref<HTMLInputElement | null>(null);
const dragging = ref(false);

const accept =
    'image/jpeg,image/png,image/webp,image/gif,image/avif,image/svg+xml';

function onDrop(event: DragEvent): void {
    dragging.value = false;

    if (event.dataTransfer?.files.length) {
        add(event.dataTransfer.files);
    }
}

function onPick(event: Event): void {
    const target = event.target as HTMLInputElement;

    if (target.files?.length) {
        add(target.files);
    }

    target.value = '';
}
</script>

<template>
    <div class="flex flex-col gap-3">
        <div
            class="flex flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed p-6 text-center transition-colors"
            :class="
                dragging
                    ? 'border-primary bg-primary/5'
                    : 'border-muted-foreground/25'
            "
            @dragover.prevent="dragging = true"
            @dragleave.prevent="dragging = false"
            @drop.prevent="onDrop"
        >
            <ImageUp class="size-8 text-muted-foreground" />
            <p class="text-sm text-muted-foreground">
                Trage imaginile aici sau
            </p>
            <Button
                type="button"
                variant="outline"
                size="sm"
                @click="input?.click()"
            >
                Alege fișiere
            </Button>
            <p class="text-xs text-muted-foreground">
                JPG, PNG, WebP, GIF, AVIF sau SVG, maximum 15 MB.
            </p>
            <input
                ref="input"
                type="file"
                multiple
                :accept="accept"
                class="hidden"
                @change="onPick"
            />
        </div>

        <ul v-if="items.length" class="flex flex-col gap-2 text-sm">
            <li
                v-for="item in items"
                :key="item.key"
                class="flex flex-col gap-1 rounded-md border px-3 py-2"
            >
                <div class="flex items-center justify-between gap-2">
                    <span class="truncate">{{ item.name }}</span>
                    <span
                        class="shrink-0 text-xs"
                        :class="{
                            'text-destructive': item.status === 'failed',
                            'text-muted-foreground': item.status !== 'failed',
                        }"
                    >
                        {{
                            item.status === 'done'
                                ? 'Încărcat'
                                : item.status === 'failed'
                                  ? 'Eșuat'
                                  : item.status === 'queued'
                                    ? 'În așteptare'
                                    : `${item.progress}%`
                        }}
                    </span>
                </div>
                <div
                    v-if="item.status === 'uploading'"
                    class="h-1 overflow-hidden rounded bg-muted"
                >
                    <div
                        class="h-full bg-primary transition-all"
                        :style="{ width: `${item.progress}%` }"
                    />
                </div>
                <p v-if="item.error" class="text-xs text-destructive">
                    {{ item.error }}
                </p>
            </li>
            <li>
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    @click="clearFinished"
                >
                    Golește lista
                </Button>
            </li>
        </ul>
    </div>
</template>
