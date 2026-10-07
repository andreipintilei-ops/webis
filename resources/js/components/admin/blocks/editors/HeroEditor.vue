<script setup lang="ts">
import CtaField from '@/components/admin/fields/CtaField.vue';
import SelectField from '@/components/admin/fields/SelectField.vue';
import SwitchField from '@/components/admin/fields/SwitchField.vue';
import TextField from '@/components/admin/fields/TextField.vue';
import AssetField from '@/components/admin/media/AssetField.vue';
import { Label } from '@/components/ui/label';
import { scoped } from '@/components/admin/blocks/errors';
import type { Choices, Cta } from '@/types/content';

/** Mirrors App\Blocks\HeroBlock. */
type HeroData = {
    eyebrow: string | null;
    heading: string;
    subheading: string | null;
    image_asset_id: number | null;
    layout: 'split' | 'centered';
    /** Centred layout only; "gradient" takes the image's place. */
    background?: 'none' | 'gradient' | 'gradient-violet';
    /** Centred layout only. */
    align?: 'center' | 'left';
    /** Centred layout only: the client-logo marquee along the bottom. */
    logos?: boolean;
    primary_cta: Cta;
    secondary_cta: Cta;
};

defineProps<{
    errors: Record<string, string>;
    choices: Choices;
}>();

const data = defineModel<HeroData>('data', { required: true });
</script>

<template>
    <div class="grid gap-4">
        <TextField
            v-model="data.heading"
            label="Titlu (h1)"
            :max="160"
            multiline
            :rows="2"
            :error="errors.heading"
            hint="Singurul h1 al paginii — include expresia principală. Enter începe un rând nou."
            required
        />
        <TextField
            v-model="data.eyebrow"
            label="Supratitlu"
            :max="80"
            :error="errors.eyebrow"
        />
        <TextField
            v-model="data.subheading"
            label="Subtitlu"
            :max="400"
            multiline
            :error="errors.subheading"
        />
        <div class="grid gap-1.5">
            <Label>Imagine</Label>
            <AssetField v-model="data.image_asset_id" />
            <p class="text-xs text-muted-foreground">
                Se încarcă prioritar (LCP) — folosește o imagine optimizată.
            </p>
        </div>
        <SelectField
            v-model="data.layout"
            label="Aranjare"
            :options="[
                { value: 'split', label: 'Text și imagine alăturate' },
                { value: 'centered', label: 'Centrat' },
            ]"
            :error="errors.layout"
        />
        <SelectField
            v-if="data.layout === 'centered'"
            :model-value="data.align ?? 'center'"
            label="Aliniere text"
            :options="[
                { value: 'center', label: 'Centru' },
                { value: 'left', label: 'Stânga' },
            ]"
            :error="errors.align"
            @update:model-value="
                data.align = $event === 'left' ? 'left' : 'center'
            "
        />
        <SelectField
            v-if="data.layout === 'centered'"
            :model-value="data.background ?? 'none'"
            label="Fundal"
            :options="[
                { value: 'none', label: 'Alb / imagine' },
                { value: 'gradient', label: 'Gradient animat' },
                {
                    value: 'gradient-violet',
                    label: 'Gradient animat (violet)',
                },
            ]"
            hint="Gradientul animat înlocuiește imaginea; textul devine alb."
            :error="errors.background"
            @update:model-value="
                data.background =
                    $event === 'gradient' || $event === 'gradient-violet'
                        ? $event
                        : 'none'
            "
        />
        <SwitchField
            v-if="data.layout === 'centered'"
            :model-value="data.logos ?? false"
            label="Logo-uri clienți în partea de jos"
            hint="Bandă animată cu clienții marcați „Afișează în banda de logo-uri” (Clienți)."
            @update:model-value="data.logos = $event"
        />
        <CtaField
            v-model="data.primary_cta"
            label="Buton principal"
            :errors="scoped(errors, 'primary_cta')"
        />
        <CtaField
            v-model="data.secondary_cta"
            label="Buton secundar"
            :errors="scoped(errors, 'secondary_cta')"
        />
    </div>
</template>
