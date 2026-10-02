<script setup lang="ts">
import { computed } from 'vue';
import TextField from '@/components/admin/fields/TextField.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import type { Cta } from '@/types/content';

/**
 * A button: `{label, url}`, or null when optional and unused. `errors` are
 * scoped to this field (keys `label`, `url`).
 */
const props = withDefaults(
    defineProps<{
        label: string;
        required?: boolean;
        errors?: Record<string, string>;
    }>(),
    { errors: () => ({}) },
);

const model = defineModel<Cta>({ default: null });

const buttonLabel = computed({
    get: () => model.value?.label ?? null,
    set: (value) =>
        (model.value = {
            label: String(value ?? ''),
            url: model.value?.url ?? '',
        }),
});

const url = computed({
    get: () => model.value?.url ?? null,
    set: (value) =>
        (model.value = {
            label: model.value?.label ?? '',
            url: String(value ?? ''),
        }),
});

function add(): void {
    model.value = { label: '', url: '' };
}

function remove(): void {
    model.value = props.required ? { label: '', url: '' } : null;
}
</script>

<template>
    <div class="grid gap-2">
        <div class="flex items-center justify-between">
            <Label>{{ label }}</Label>
            <Button
                v-if="model && !required"
                type="button"
                variant="ghost"
                size="sm"
                @click="remove"
            >
                Elimină butonul
            </Button>
        </div>
        <div v-if="model" class="grid gap-3 sm:grid-cols-2">
            <TextField
                v-model="buttonLabel"
                label="Text buton"
                :max="60"
                :error="errors.label"
                required
            />
            <TextField
                v-model="url"
                label="Link"
                placeholder="/contact, https://…, tel:…"
                :error="errors.url"
                required
            />
        </div>
        <Button
            v-else
            type="button"
            variant="outline"
            size="sm"
            class="self-start"
            @click="add"
        >
            Adaugă buton
        </Button>
    </div>
</template>
