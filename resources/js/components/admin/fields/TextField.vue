<script setup lang="ts">
import { computed, useId } from 'vue';
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

/**
 * A labelled text input (or textarea with `multiline`) with its error, an
 * optional hint and a live character count when `max` is set.
 */
const props = withDefaults(
    defineProps<{
        label: string;
        error?: string;
        hint?: string;
        max?: number;
        multiline?: boolean;
        rows?: number;
        placeholder?: string;
        type?: string;
        required?: boolean;
    }>(),
    {
        error: undefined,
        hint: undefined,
        max: undefined,
        rows: 3,
        placeholder: undefined,
        type: 'text',
    },
);

const model = defineModel<string | number | null>({ default: null });
const id = useId();

const length = computed(() => String(model.value ?? '').length);

// v-model on the inputs wants a string; an empty input stores null so optional
// fields stay null instead of "".
const text = computed({
    get: () => (model.value === null ? '' : String(model.value)),
    set: (value: string) => {
        if (props.type === 'number') {
            model.value = value === '' ? null : Number(value);
        } else {
            model.value = value === '' && !props.required ? null : value;
        }
    },
});
</script>

<template>
    <div class="grid gap-1.5">
        <div class="flex items-baseline justify-between gap-2">
            <Label :for="id">
                {{ label }}
                <span v-if="required" class="text-destructive">*</span>
            </Label>
            <span
                v-if="max"
                class="text-xs tabular-nums"
                :class="
                    length > max ? 'text-destructive' : 'text-muted-foreground'
                "
            >
                {{ length }}/{{ max }}
            </span>
        </div>
        <Textarea
            v-if="multiline"
            :id="id"
            v-model="text"
            :rows="rows"
            :placeholder="placeholder"
            :aria-invalid="!!error"
        />
        <Input
            v-else
            :id="id"
            v-model="text"
            :type="type"
            :placeholder="placeholder"
            :aria-invalid="!!error"
        />
        <p v-if="hint" class="text-xs text-muted-foreground">{{ hint }}</p>
        <InputError :message="error" />
    </div>
</template>
