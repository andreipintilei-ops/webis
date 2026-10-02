<script setup lang="ts">
import CtaField from '@/components/admin/fields/CtaField.vue';
import SelectField from '@/components/admin/fields/SelectField.vue';
import TextField from '@/components/admin/fields/TextField.vue';
import { scoped } from '@/components/admin/blocks/errors';
import type { Choices, Cta } from '@/types/content';

/** Mirrors App\Blocks\CtaBannerBlock. */
type CtaBannerData = {
    heading: string;
    text: string | null;
    primary_cta: Cta;
    secondary_cta: Cta;
    variant: 'default' | 'accent';
};

defineProps<{
    errors: Record<string, string>;
    choices: Choices;
}>();

const data = defineModel<CtaBannerData>('data', { required: true });
</script>

<template>
    <div class="grid gap-4">
        <TextField
            v-model="data.heading"
            label="Titlu"
            :max="160"
            :error="errors.heading"
            required
        />
        <TextField
            v-model="data.text"
            label="Text"
            :max="300"
            multiline
            :error="errors.text"
        />
        <CtaField
            v-model="data.primary_cta"
            label="Buton principal"
            required
            :errors="scoped(errors, 'primary_cta')"
        />
        <CtaField
            v-model="data.secondary_cta"
            label="Buton secundar"
            :errors="scoped(errors, 'secondary_cta')"
        />
        <SelectField
            v-model="data.variant"
            label="Stil"
            :options="[
                { value: 'default', label: 'Standard' },
                { value: 'accent', label: 'Evidențiat' },
            ]"
            :error="errors.variant"
        />
    </div>
</template>
