<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import ItemList from '@/components/admin/fields/ItemList.vue';
import SelectField from '@/components/admin/fields/SelectField.vue';
import TextField from '@/components/admin/fields/TextField.vue';
import RichTextEditor from '@/components/admin/rich-text/RichTextEditor.vue';
import { Label } from '@/components/ui/label';
import type { Choices, TiptapDoc } from '@/types/content';

type StatItem = {
    value: string;
    label: string;
    note: string | null;
};

/** Mirrors App\Blocks\StatsBlock. */
type StatsData = {
    heading: string | null;
    items: StatItem[];
    body: TiptapDoc;
    tone: 'dark' | 'light';
};

defineProps<{
    errors: Record<string, string>;
    choices: Choices;
}>();

const data = defineModel<StatsData>('data', { required: true });

// Blocks saved before the passage and the tone existed have neither yet.
data.value.body ??= { type: 'doc', content: [] };
data.value.tone ??= 'dark';

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
        <div class="grid gap-1.5">
            <Label>Text sub cifre</Label>
            <RichTextEditor
                v-model="data.body"
                :pages="choices.pages"
                compact
            />
            <InputError :message="errors.body" />
        </div>
        <SelectField
            v-model="data.tone"
            label="Fundal"
            :options="[
                { value: 'dark', label: 'Indigo' },
                { value: 'light', label: 'Deschis' },
            ]"
            :error="errors.tone"
        />
    </div>
</template>
