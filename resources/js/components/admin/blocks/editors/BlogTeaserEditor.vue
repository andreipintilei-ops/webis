<script setup lang="ts">
import { computed } from 'vue';
import ChoiceList from '@/components/admin/fields/ChoiceList.vue';
import SelectField from '@/components/admin/fields/SelectField.vue';
import TextField from '@/components/admin/fields/TextField.vue';
import { scoped } from '@/components/admin/blocks/errors';
import type { Choices, Option } from '@/types/content';

/** Mirrors App\Blocks\BlogTeaserBlock. */
type BlogTeaserData = {
    heading: string | null;
    source: 'latest' | 'category' | 'manual';
    category_id: number | null;
    post_ids: number[];
    limit: number;
};

const props = defineProps<{
    errors: Record<string, string>;
    choices: Choices;
}>();

const data = defineModel<BlogTeaserData>('data', { required: true });

const categoryOptions = computed<Option[]>(() =>
    props.choices.postCategories.map((c) => ({
        value: String(c.id),
        label: c.label,
    })),
);

const limitOptions: Option[] = Array.from({ length: 6 }, (_, i) => ({
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
            label="Ce articole se afișează"
            :options="[
                { value: 'latest', label: 'Cele mai noi' },
                { value: 'category', label: 'Dintr-o categorie' },
                { value: 'manual', label: 'Alese manual' },
            ]"
            :error="errors.source"
        />
        <SelectField
            v-if="data.source === 'category'"
            v-model="data.category_id"
            label="Categorie"
            :options="categoryOptions"
            numeric
            nullable
            empty-label="— Alege o categorie —"
            :error="errors.category_id"
        />
        <ChoiceList
            v-if="data.source === 'manual'"
            v-model="data.post_ids"
            label="Articole"
            :choices="choices.posts"
            :max="6"
            :error="
                errors.post_ids ?? Object.values(scoped(errors, 'post_ids'))[0]
            "
        />
        <SelectField
            v-model="data.limit"
            label="Număr maxim de articole"
            :options="limitOptions"
            numeric
            :error="errors.limit"
        />
    </div>
</template>
