<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ImagePlus, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import TextField from '@/components/admin/fields/TextField.vue';
import { Button } from '@/components/ui/button';
import { destroy, store } from '@/routes/admin/pages/cover';

/**
 * An article's cover: the image, uploaded at once on its own (the page's
 * form goes as JSON, which a file cannot), with a preview; and its alt
 * text, saved with the article (required once there is a cover). A new
 * article is saved first.
 */
const props = defineProps<{
    pageId: number | null;
    url: string | null;
    error?: string;
}>();

const alt = defineModel<string | null>('alt', { default: null });
const input = ref<HTMLInputElement | null>(null);
const uploading = ref(false);
const uploadError = ref<string | null>(null);

function upload(event: Event): void {
    const file = (event.target as HTMLInputElement).files?.[0];

    if (!file || props.pageId === null) {
        return;
    }

    uploadError.value = null;
    router.post(
        store.url(props.pageId),
        { cover: file, cover_alt: alt.value ?? '' },
        {
            forceFormData: true,
            preserveScroll: true,
            preserveState: true,
            onStart: () => (uploading.value = true),
            onFinish: () => {
                uploading.value = false;

                if (input.value) {
                    input.value.value = '';
                }
            },
            onError: (errors) =>
                (uploadError.value = errors.cover ?? errors.cover_alt ?? null),
        },
    );
}

function remove(): void {
    if (props.pageId !== null) {
        router.delete(destroy.url(props.pageId), {
            preserveScroll: true,
            preserveState: true,
        });
    }
}
</script>

<template>
    <div class="grid gap-3">
        <p v-if="pageId === null" class="text-sm text-muted-foreground">
            Salvează articolul ca să-i poți adăuga o copertă.
        </p>

        <template v-else>
            <div class="flex items-start gap-4">
                <button
                    type="button"
                    class="flex aspect-video w-48 shrink-0 items-center justify-center overflow-hidden rounded-lg border bg-[#1f2868] hover:opacity-90"
                    :disabled="uploading"
                    @click="input?.click()"
                >
                    <img
                        v-if="url"
                        :src="url"
                        :alt="alt ?? ''"
                        class="size-full object-cover"
                    />
                    <ImagePlus v-else class="size-6 text-white/70" />
                </button>

                <div class="flex min-w-0 flex-col gap-2">
                    <div class="flex flex-wrap gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            :disabled="uploading"
                            @click="input?.click()"
                        >
                            {{
                                uploading
                                    ? 'Se încarcă…'
                                    : url
                                      ? 'Înlocuiește'
                                      : 'Încarcă o copertă'
                            }}
                        </Button>
                        <Button
                            v-if="url"
                            type="button"
                            variant="ghost"
                            size="sm"
                            :disabled="uploading"
                            @click="remove"
                        >
                            <Trash2 class="size-4" /> Elimină
                        </Button>
                    </div>
                    <p class="text-xs text-muted-foreground">
                        PNG, JPG sau WebP, orizontală, ideal 1536 × 1024. Se
                        folosește mijlocul ei, 16:9.
                    </p>
                    <p v-if="uploadError" class="text-xs text-destructive">
                        {{ uploadError }}
                    </p>
                </div>

                <input
                    ref="input"
                    type="file"
                    accept="image/png,image/jpeg,image/webp"
                    class="hidden"
                    @change="upload"
                />
            </div>

            <TextField
                v-model="alt"
                label="Text alternativ al copertei"
                :max="125"
                :error="error"
                hint="Ce se vede în imagine, pe scurt, în română. Se salvează cu articolul."
            />
        </template>
    </div>
</template>
