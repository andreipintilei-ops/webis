<script setup lang="ts">
import ItemList from '@/components/admin/fields/ItemList.vue';
import TextField from '@/components/admin/fields/TextField.vue';
import type { Choices } from '@/types/content';

type ProcessStep = {
    title: string;
    text: string | null;
};

/** Mirrors App\Blocks\ProcessStepsBlock. */
type ProcessStepsData = {
    heading: string | null;
    intro: string | null;
    steps: ProcessStep[];
};

defineProps<{
    errors: Record<string, string>;
    choices: Choices;
}>();

const data = defineModel<ProcessStepsData>('data', { required: true });

const newStep = (): ProcessStep => ({ title: '', text: null });
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
        <ItemList
            v-model="data.steps"
            label="Etape"
            :new-item="newStep"
            add-label="Adaugă o etapă"
            :max="10"
            :error="errors.steps"
        >
            <template #default="{ item, index }">
                <div class="grid gap-3">
                    <TextField
                        v-model="item.title"
                        label="Titlu"
                        :max="100"
                        :error="errors[`steps.${index}.title`]"
                        required
                    />
                    <TextField
                        v-model="item.text"
                        label="Text"
                        :max="500"
                        multiline
                        :error="errors[`steps.${index}.text`]"
                    />
                </div>
            </template>
        </ItemList>
    </div>
</template>
