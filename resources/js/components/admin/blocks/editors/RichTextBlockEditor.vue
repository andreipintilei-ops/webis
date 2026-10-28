<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import RichTextEditor from '@/components/admin/rich-text/RichTextEditor.vue';
import { Label } from '@/components/ui/label';
import type { Choices, TiptapDoc } from '@/types/content';

/** Mirrors App\Blocks\RichTextBlock. */
type RichTextData = {
    content: TiptapDoc;
};

defineProps<{
    errors: Record<string, string>;
    choices: Choices;
}>();

const data = defineModel<RichTextData>('data', { required: true });
</script>

<template>
    <div class="grid gap-4">
        <div class="grid gap-1.5">
            <Label>Conținut</Label>
            <RichTextEditor v-model="data.content" :pages="choices.pages" />
            <InputError :message="errors.content" />
        </div>
    </div>
</template>
