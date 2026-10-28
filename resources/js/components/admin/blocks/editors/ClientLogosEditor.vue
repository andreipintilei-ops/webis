<script setup lang="ts">
import ChoiceList from '@/components/admin/fields/ChoiceList.vue';
import SelectField from '@/components/admin/fields/SelectField.vue';
import TextField from '@/components/admin/fields/TextField.vue';
import { scoped } from '@/components/admin/blocks/errors';
import type { Choices } from '@/types/content';

/** Mirrors App\Blocks\ClientLogosBlock. */
type ClientLogosData = {
    heading: string | null;
    source: 'all' | 'manual';
    client_ids: number[];
};

defineProps<{
    errors: Record<string, string>;
    choices: Choices;
}>();

const data = defineModel<ClientLogosData>('data', { required: true });
</script>

<template>
    <div class="grid gap-4">
        <TextField
            v-model="data.heading"
            label="Titlu"
            :max="160"
            :error="errors.heading"
        />
        <SelectField
            v-model="data.source"
            label="Ce clienți se afișează"
            :options="[
                { value: 'all', label: 'Toți' },
                { value: 'manual', label: 'Aleși manual' },
            ]"
            :error="errors.source"
        />
        <ChoiceList
            v-if="data.source === 'manual'"
            v-model="data.client_ids"
            label="Clienți"
            :choices="choices.clients"
            :max="40"
            :error="
                errors.client_ids ??
                Object.values(scoped(errors, 'client_ids'))[0]
            "
        />
    </div>
</template>
