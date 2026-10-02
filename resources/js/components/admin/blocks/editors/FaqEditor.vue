<script setup lang="ts">
import ItemList from '@/components/admin/fields/ItemList.vue';
import TextField from '@/components/admin/fields/TextField.vue';
import type { Choices } from '@/types/content';

type FaqItem = {
    question: string;
    answer: string;
};

/** Mirrors App\Blocks\FaqBlock. */
type FaqData = {
    heading: string | null;
    items: FaqItem[];
};

defineProps<{
    errors: Record<string, string>;
    choices: Choices;
}>();

const data = defineModel<FaqData>('data', { required: true });

const newItem = (): FaqItem => ({ question: '', answer: '' });
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
            label="Întrebări"
            :new-item="newItem"
            add-label="Adaugă o întrebare"
            :max="30"
            :error="errors.items"
        >
            <template #default="{ item, index }">
                <div class="grid gap-3">
                    <TextField
                        v-model="item.question"
                        label="Întrebare"
                        :max="200"
                        :error="errors[`items.${index}.question`]"
                        required
                    />
                    <TextField
                        v-model="item.answer"
                        label="Răspuns"
                        :max="2000"
                        multiline
                        :rows="4"
                        hint="Răspunsurile apar și în datele structurate FAQ pentru Google — scrie text simplu, fără formatare."
                        :error="errors[`items.${index}.answer`]"
                        required
                    />
                </div>
            </template>
        </ItemList>
    </div>
</template>
