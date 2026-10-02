<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import CtaField from '@/components/admin/fields/CtaField.vue';
import SelectField from '@/components/admin/fields/SelectField.vue';
import TextField from '@/components/admin/fields/TextField.vue';
import AssetField from '@/components/admin/media/AssetField.vue';
import RichTextEditor from '@/components/admin/rich-text/RichTextEditor.vue';
import { Label } from '@/components/ui/label';
import { scoped } from '@/components/admin/blocks/errors';
import type { Choices, Cta, TiptapDoc } from '@/types/content';

/** Mirrors App\Blocks\ImageTextBlock. */
type ImageTextData = {
    heading: string | null;
    content: TiptapDoc;
    image_asset_id: number | null;
    image_position: 'left' | 'right';
    cta: Cta;
};

defineProps<{
    errors: Record<string, string>;
    choices: Choices;
}>();

const data = defineModel<ImageTextData>('data', { required: true });
</script>

<template>
    <div class="grid gap-4">
        <TextField
            v-model="data.heading"
            label="Titlu"
            :max="160"
            :error="errors.heading"
        />
        <div class="grid gap-1.5">
            <Label>Conținut</Label>
            <RichTextEditor
                v-model="data.content"
                :pages="choices.pages"
                compact
            />
            <InputError :message="errors.content" />
        </div>
        <div class="grid gap-1.5">
            <Label>
                Imagine
                <span class="text-destructive">*</span>
            </Label>
            <AssetField v-model="data.image_asset_id" />
            <InputError :message="errors.image_asset_id" />
        </div>
        <SelectField
            v-model="data.image_position"
            label="Poziția imaginii"
            :options="[
                { value: 'left', label: 'În stânga' },
                { value: 'right', label: 'În dreapta' },
            ]"
            :error="errors.image_position"
        />
        <CtaField
            v-model="data.cta"
            label="Buton"
            :errors="scoped(errors, 'cta')"
        />
    </div>
</template>
