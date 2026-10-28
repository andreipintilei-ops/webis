<script setup lang="ts">
import { scoped } from '@/components/admin/blocks/errors';
import CtaField from '@/components/admin/fields/CtaField.vue';
import ItemList from '@/components/admin/fields/ItemList.vue';
import SelectField from '@/components/admin/fields/SelectField.vue';
import TextField from '@/components/admin/fields/TextField.vue';
import type { Choices, Cta } from '@/types/content';

type FeatureItem = {
    icon: string | null;
    title: string;
    text: string | null;
    /** When it fits ("Când…"), set apart under the text. */
    note?: string | null;
    /** A link at the foot of the card. */
    link?: Cta;
};

/** Mirrors App\Blocks\FeaturesBlock. */
type FeaturesData = {
    heading: string | null;
    intro: string | null;
    layout?: 'columns' | 'cards' | null;
    columns: number;
    items: FeatureItem[];
};

defineProps<{
    errors: Record<string, string>;
    choices: Choices;
}>();

const data = defineModel<FeaturesData>('data', { required: true });

const newItem = (): FeatureItem => ({
    icon: null,
    title: '',
    text: null,
    note: null,
    link: null,
});
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
            :model-value="data.layout ?? 'columns'"
            label="Aspect"
            :options="[
                { value: 'columns', label: 'Coloane, între linii' },
                { value: 'cards', label: 'Carduri ilustrate (serviciile)' },
            ]"
            hint="„Carduri ilustrate” afișează cele trei servicii, cu ilustrații."
            :error="errors.layout"
            @update:model-value="
                data.layout = $event === 'cards' ? 'cards' : 'columns'
            "
        />
        <SelectField
            v-model="data.columns"
            label="Coloane"
            :options="[
                { value: '2', label: '2 coloane' },
                { value: '3', label: '3 coloane' },
                { value: '4', label: '4 coloane' },
            ]"
            numeric
            :error="errors.columns"
        />
        <ItemList
            v-model="data.items"
            label="Carduri"
            :new-item="newItem"
            add-label="Adaugă un card"
            :max="12"
            :error="errors.items"
        >
            <template #default="{ item, index }">
                <div class="grid gap-3">
                    <TextField
                        v-model="item.title"
                        label="Titlu"
                        :max="100"
                        :error="errors[`items.${index}.title`]"
                        required
                    />
                    <TextField
                        v-model="item.text"
                        label="Text"
                        :max="400"
                        multiline
                        :error="errors[`items.${index}.text`]"
                    />
                    <TextField
                        :model-value="item.note ?? null"
                        label="Notă"
                        :max="200"
                        multiline
                        hint="Opțional: când se potrivește, ex. „Când procesul vostru nu seamănă cu al nimănui.”"
                        :error="errors[`items.${index}.note`]"
                        @update:model-value="
                            item.note = $event === null ? null : String($event)
                        "
                    />
                    <CtaField
                        :model-value="item.link ?? null"
                        label="Link"
                        :errors="scoped(errors, `items.${index}.link`)"
                        @update:model-value="item.link = $event"
                    />
                    <TextField
                        v-model="item.icon"
                        label="Iconiță"
                        :max="50"
                        hint="Nume de iconiță Lucide, ex. rocket, shield-check (încă nu apare pe site)"
                        :error="errors[`items.${index}.icon`]"
                    />
                </div>
            </template>
        </ItemList>
    </div>
</template>
