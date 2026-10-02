<script setup lang="ts">
import { ChevronDown, ChevronUp, X } from '@lucide/vue';
import { computed, ref, useId } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import type { Choice } from '@/types/content';

/**
 * Hand-pick records (pages, projects, clients…) in a chosen order. Stores a
 * list of ids; `choices` comes from the server with the edit page.
 */
const props = withDefaults(
    defineProps<{
        label: string;
        choices: Choice[];
        error?: string;
        hint?: string;
        max?: number;
    }>(),
    { error: undefined, hint: undefined, max: undefined },
);

const model = defineModel<number[]>({ default: () => [] });
const id = useId();
const pending = ref('');

const byId = computed(() => new Map(props.choices.map((c) => [c.id, c])));
const available = computed(() =>
    props.choices.filter((choice) => !model.value.includes(choice.id)),
);

function add(): void {
    const value = Number(pending.value);

    if (value && !model.value.includes(value)) {
        model.value = [...model.value, value];
    }

    pending.value = '';
}

function remove(value: number): void {
    model.value = model.value.filter((existing) => existing !== value);
}

function move(index: number, by: -1 | 1): void {
    const target = index + by;

    if (target < 0 || target >= model.value.length) {
        return;
    }

    const ids = [...model.value];
    [ids[index], ids[target]] = [ids[target], ids[index]];
    model.value = ids;
}
</script>

<template>
    <div class="grid gap-1.5">
        <Label :for="id">{{ label }}</Label>

        <ol v-if="model.length" class="flex flex-col gap-1">
            <li
                v-for="(value, index) in model"
                :key="value"
                class="flex items-center gap-1 rounded-md border px-2 py-1 text-sm"
            >
                <span class="flex-1 truncate">
                    {{ byId.get(value)?.label ?? `#${value} (șters)` }}
                </span>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon-sm"
                    :disabled="index === 0"
                    aria-label="Mută mai sus"
                    @click="move(index, -1)"
                >
                    <ChevronUp />
                </Button>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon-sm"
                    :disabled="index === model.length - 1"
                    aria-label="Mută mai jos"
                    @click="move(index, 1)"
                >
                    <ChevronDown />
                </Button>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon-sm"
                    aria-label="Elimină"
                    @click="remove(value)"
                >
                    <X />
                </Button>
            </li>
        </ol>

        <select
            v-if="!max || model.length < max"
            :id="id"
            v-model="pending"
            class="h-9 rounded-md border border-input bg-background px-3 py-1 text-sm shadow-xs"
            @change="add"
        >
            <option value="">Adaugă…</option>
            <option
                v-for="choice in available"
                :key="choice.id"
                :value="choice.id"
            >
                {{ choice.label }}
            </option>
        </select>
        <p v-if="hint" class="text-xs text-muted-foreground">{{ hint }}</p>
        <InputError :message="error" />
    </div>
</template>
