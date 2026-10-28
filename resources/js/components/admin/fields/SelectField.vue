<script setup lang="ts">
import { useId } from 'vue';
import InputError from '@/components/InputError.vue';
import { Label } from '@/components/ui/label';
import type { Option } from '@/types/content';

/**
 * A labelled native select. Option values are strings; `numeric` stores the
 * chosen value as a number (e.g. column counts, ids), and `nullable` adds an
 * empty choice that stores null.
 */
const props = withDefaults(
    defineProps<{
        label: string;
        options: Option[];
        error?: string;
        hint?: string;
        numeric?: boolean;
        nullable?: boolean;
        emptyLabel?: string;
    }>(),
    {
        error: undefined,
        hint: undefined,
        emptyLabel: '—',
    },
);

const model = defineModel<string | number | null>({ default: null });
const id = useId();

function onChange(event: Event): void {
    const value = (event.target as HTMLSelectElement).value;

    if (value === '') {
        model.value = null;
    } else {
        model.value = props.numeric ? Number(value) : value;
    }
}
</script>

<template>
    <div class="grid gap-1.5">
        <Label :for="id">{{ label }}</Label>
        <select
            :id="id"
            :value="model === null ? '' : String(model)"
            class="h-9 rounded-md border border-input bg-background px-3 py-1 text-sm shadow-xs outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50"
            :aria-invalid="!!error"
            @change="onChange"
        >
            <option v-if="nullable" value="">{{ emptyLabel }}</option>
            <option
                v-for="option in options"
                :key="option.value"
                :value="option.value"
            >
                {{ option.label }}
            </option>
        </select>
        <p v-if="hint" class="text-xs text-muted-foreground">{{ hint }}</p>
        <InputError :message="error" />
    </div>
</template>
