<script setup lang="ts">
import { computed } from 'vue';
import SelectField from '@/components/admin/fields/SelectField.vue';
import SwitchField from '@/components/admin/fields/SwitchField.vue';
import TextField from '@/components/admin/fields/TextField.vue';
import type { Choices, Option } from '@/types/content';

/** Mirrors App\Blocks\QuoteFormBlock. */
type QuoteFormData = {
    heading: string | null;
    intro: string | null;
    service_page_id: number | null;
    show_contact_details: boolean;
};

const props = defineProps<{
    errors: Record<string, string>;
    choices: Choices;
}>();

const data = defineModel<QuoteFormData>('data', { required: true });

const serviceOptions = computed<Option[]>(() =>
    props.choices.servicePages.map((c) => ({
        value: String(c.id),
        label: c.label,
    })),
);
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
            v-model="data.service_page_id"
            label="Serviciu preselectat"
            :options="serviceOptions"
            numeric
            nullable
            empty-label="— Niciunul —"
            hint="Serviciul ales implicit în formular."
            :error="errors.service_page_id"
        />
        <SwitchField
            v-model="data.show_contact_details"
            label="Arată datele de contact"
            hint="Afișate alături de formular."
        />
    </div>
</template>
