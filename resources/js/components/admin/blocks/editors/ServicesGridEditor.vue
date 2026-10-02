<script setup lang="ts">
import ChoiceList from '@/components/admin/fields/ChoiceList.vue';
import SelectField from '@/components/admin/fields/SelectField.vue';
import TextField from '@/components/admin/fields/TextField.vue';
import { scoped } from '@/components/admin/blocks/errors';
import type { Choices } from '@/types/content';

/** Mirrors App\Blocks\ServicesGridBlock. */
type ServicesGridData = {
    heading: string | null;
    intro: string | null;
    source: 'services' | 'industries' | 'manual';
    page_ids: number[];
};

defineProps<{
    errors: Record<string, string>;
    choices: Choices;
}>();

const data = defineModel<ServicesGridData>('data', { required: true });
</script>

<template>
    <div class="grid gap-4">
        <TextField
            v-model="data.heading"
            label="Titlu"
            :max="160"
            :error="errors.heading"
        />
        <TextField
            v-model="data.intro"
            label="Introducere"
            :max="400"
            multiline
            :error="errors.intro"
        />
        <SelectField
            v-model="data.source"
            label="Ce pagini se afișează"
            :options="[
                { value: 'services', label: 'Toate paginile de servicii' },
                { value: 'industries', label: 'Toate paginile de industrii' },
                { value: 'manual', label: 'Alese manual' },
            ]"
            :error="errors.source"
        />
        <ChoiceList
            v-if="data.source === 'manual'"
            v-model="data.page_ids"
            label="Pagini"
            :choices="choices.servicePages"
            :max="24"
            :error="
                errors.page_ids ?? Object.values(scoped(errors, 'page_ids'))[0]
            "
        />
    </div>
</template>
