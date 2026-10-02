<script setup lang="ts">
import ChoiceList from '@/components/admin/fields/ChoiceList.vue';
import SelectField from '@/components/admin/fields/SelectField.vue';
import TextField from '@/components/admin/fields/TextField.vue';
import { scoped } from '@/components/admin/blocks/errors';
import type { Choices, Option } from '@/types/content';

/** Mirrors App\Blocks\TestimonialsBlock. */
type TestimonialsData = {
    heading: string | null;
    source: 'all' | 'manual';
    testimonial_ids: number[];
    limit: number;
};

defineProps<{
    errors: Record<string, string>;
    choices: Choices;
}>();

const data = defineModel<TestimonialsData>('data', { required: true });

const limitOptions: Option[] = Array.from({ length: 12 }, (_, i) => ({
    value: String(i + 1),
    label: String(i + 1),
}));
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
            label="Ce testimoniale se afișează"
            :options="[
                { value: 'all', label: 'Toate' },
                { value: 'manual', label: 'Alese manual' },
            ]"
            :error="errors.source"
        />
        <ChoiceList
            v-if="data.source === 'manual'"
            v-model="data.testimonial_ids"
            label="Testimoniale"
            :choices="choices.testimonials"
            :max="12"
            :error="
                errors.testimonial_ids ??
                Object.values(scoped(errors, 'testimonial_ids'))[0]
            "
        />
        <SelectField
            v-model="data.limit"
            label="Număr maxim de testimoniale"
            :options="limitOptions"
            numeric
            :error="errors.limit"
        />
    </div>
</template>
