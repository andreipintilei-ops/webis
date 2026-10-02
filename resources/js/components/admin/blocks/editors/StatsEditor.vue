<script setup lang="ts">
import ItemList from '@/components/admin/fields/ItemList.vue';
import TextField from '@/components/admin/fields/TextField.vue';
import type { Choices } from '@/types/content';

type StatItem = {
    value: string;
    label: string;
    note: string | null;
};

/** Mirrors App\Blocks\StatsBlock. */
type StatsData = {
    heading: string | null;
    items: StatItem[];
};

defineProps<{
    errors: Record<string, string>;
    choices: Choices;
}>();

const data = defineModel<StatsData>('data', { required: true });

const newItem = (): StatItem => ({ value: '', label: '', note: null });
</script>

<template>
    <div class="grid gap-4">
        <TextField
            v-model="data.heading"
            label="Titlu"
            :max="160"
            :error="errors.heading"
        />
        <ItemList
            v-model="data.items"
            label="Cifre"
            :new-item="newItem"
            add-label="Adaugă o cifră"
            :max="6"
            :error="errors.items"
        >
            <template #default="{ item, index }">
                <div class="grid gap-3">
                    <TextField
                        v-model="item.value"
                        label="Valoare"
                        :max="20"
                        placeholder="ex. 250+"
                        :error="errors[`items.${index}.value`]"
                        required
                    />
                    <TextField
                        v-model="item.label"
                        label="Etichetă"
                        :max="80"
                        :error="errors[`items.${index}.label`]"
                        required
                    />
                    <TextField
                        v-model="item.note"
                        label="Notă"
                        :max="160"
                        :error="errors[`items.${index}.note`]"
                    />
                </div>
            </template>
        </ItemList>
    </div>
</template>
