<script setup lang="ts" generic="T">
import { ChevronDown, ChevronUp, Plus, Trash2 } from '@lucide/vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';

/**
 * A repeatable group of fields (FAQ items, steps, tiers…). The default slot
 * renders one item: `#default="{ item, index }"`; mutate `item` directly.
 */
const props = withDefaults(
    defineProps<{
        label: string;
        newItem: () => T;
        addLabel?: string;
        max?: number;
        error?: string;
    }>(),
    { addLabel: 'Adaugă', max: undefined, error: undefined },
);

const model = defineModel<T[]>({ default: () => [] });

function add(): void {
    model.value = [...model.value, props.newItem()];
}

function remove(index: number): void {
    model.value = model.value.filter((_, i) => i !== index);
}

function move(index: number, by: -1 | 1): void {
    const target = index + by;

    if (target < 0 || target >= model.value.length) {
        return;
    }

    const items = [...model.value];
    [items[index], items[target]] = [items[target], items[index]];
    model.value = items;
}
</script>

<template>
    <div class="grid gap-2">
        <Label>{{ label }}</Label>
        <InputError :message="error" />
        <ol class="flex flex-col gap-3">
            <li
                v-for="(item, index) in model"
                :key="index"
                class="flex gap-2 rounded-lg border p-3"
            >
                <div class="min-w-0 flex-1">
                    <slot :item="item" :index="index" />
                </div>
                <div class="flex flex-col gap-1">
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
                        aria-label="Șterge"
                        @click="remove(index)"
                    >
                        <Trash2 />
                    </Button>
                </div>
            </li>
        </ol>
        <Button
            v-if="!max || model.length < max"
            type="button"
            variant="outline"
            size="sm"
            class="self-start"
            @click="add"
        >
            <Plus /> {{ addLabel }}
        </Button>
    </div>
</template>
