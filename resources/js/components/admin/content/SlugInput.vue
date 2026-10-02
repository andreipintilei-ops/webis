<script setup lang="ts">
import { TriangleAlert } from '@lucide/vue';
import { computed, ref, useId, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import { Label } from '@/components/ui/label';
import { slugify } from '@/lib/slugify';

/**
 * The URL segment. While creating, it follows the title until edited by hand.
 * On a live item, changing it warns that the old URL will redirect (301).
 */
const props = withDefaults(
    defineProps<{
        /** Shown before the slug, e.g. "/clienti/". */
        prefix: string;
        title: string;
        /** The slug as saved; empty for a new item. */
        original: string;
        isLive?: boolean;
        error?: string;
    }>(),
    { error: undefined },
);

const model = defineModel<string>({ required: true });
const id = useId();
const touched = ref(props.original !== '');

watch(
    () => props.title,
    (title) => {
        if (!touched.value) {
            model.value = slugify(title);
        }
    },
);

const changed = computed(
    () => props.original !== '' && model.value !== props.original,
);

function onInput(event: Event): void {
    touched.value = true;
    model.value = (event.target as HTMLInputElement).value;
}

function tidy(): void {
    model.value = slugify(model.value);
}
</script>

<template>
    <div class="grid gap-1.5">
        <Label :for="id">Adresă (URL)</Label>
        <div
            class="flex h-9 items-center overflow-hidden rounded-md border border-input bg-background text-sm shadow-xs focus-within:ring-[3px] focus-within:ring-ring/50"
            :aria-invalid="!!error"
        >
            <span
                class="shrink-0 border-r bg-muted px-2 py-2 text-muted-foreground"
                >{{ prefix }}</span
            >
            <input
                :id="id"
                :value="model"
                class="h-full min-w-0 flex-1 bg-transparent px-2 outline-none"
                spellcheck="false"
                autocomplete="off"
                @input="onInput"
                @blur="tidy"
            />
        </div>
        <p
            v-if="changed && isLive"
            class="flex items-start gap-1.5 text-xs text-amber-700 dark:text-amber-400"
        >
            <TriangleAlert class="mt-px size-3.5 shrink-0" />
            Adresa veche {{ prefix }}{{ original }} va redirecționa (301)
            automat spre cea nouă.
        </p>
        <InputError :message="error" />
    </div>
</template>
