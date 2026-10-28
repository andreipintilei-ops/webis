<script setup lang="ts">
import { scoped } from '@/components/admin/blocks/errors';
import CtaField from '@/components/admin/fields/CtaField.vue';
import SelectField from '@/components/admin/fields/SelectField.vue';
import TextField from '@/components/admin/fields/TextField.vue';
import type { Choices, Cta } from '@/types/content';

/** Mirrors App\Blocks\StatementBlock. */
type StatementData = {
    eyebrow: string | null;
    statement: string;
    text: string | null;
    link: Cta;
    background?: 'none' | 'hero';
};

defineProps<{
    errors: Record<string, string>;
    choices: Choices;
}>();

const data = defineModel<StatementData>('data', { required: true });
</script>

<template>
    <div class="grid gap-4">
        <TextField
            v-model="data.eyebrow"
            label="Etichetă"
            :max="60"
            hint="Scurtă, în stânga, ex. „Despre noi”."
            :error="errors.eyebrow"
        />
        <TextField
            v-model="data.statement"
            label="Declarație"
            :max="300"
            multiline
            :rows="3"
            hint="Textul mare, 2–3 rânduri. Enter începe un rând nou (pe desktop)."
            :error="errors.statement"
            required
        />
        <TextField
            v-model="data.text"
            label="Paragraf"
            :max="800"
            multiline
            :rows="4"
            :error="errors.text"
        />
        <CtaField
            v-model="data.link"
            label="Link"
            :errors="scoped(errors, 'link')"
        />
        <SelectField
            :model-value="data.background ?? 'none'"
            label="Fundal"
            :options="[
                { value: 'none', label: 'Alb' },
                { value: 'hero', label: 'Fundalul din hero' },
            ]"
            hint="„Fundalul din hero”: text alb peste fundalul hero-ului, dacă blocul urmează imediat după un hero întunecat; altfel rămâne alb."
            :error="errors.background"
            @update:model-value="
                data.background = $event === 'hero' ? 'hero' : 'none'
            "
        />
    </div>
</template>
