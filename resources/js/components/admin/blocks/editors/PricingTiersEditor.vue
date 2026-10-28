<script setup lang="ts">
import CtaField from '@/components/admin/fields/CtaField.vue';
import ItemList from '@/components/admin/fields/ItemList.vue';
import SwitchField from '@/components/admin/fields/SwitchField.vue';
import TextField from '@/components/admin/fields/TextField.vue';
import { scoped } from '@/components/admin/blocks/errors';
import type { Choices, Cta } from '@/types/content';

type PricingTier = {
    name: string;
    price: string;
    period: string | null;
    description: string | null;
    features: string[];
    highlighted: boolean;
    cta: Cta;
};

/** Mirrors App\Blocks\PricingTiersBlock. */
type PricingTiersData = {
    heading: string | null;
    intro: string | null;
    tiers: PricingTier[];
    note: string | null;
};

defineProps<{
    errors: Record<string, string>;
    choices: Choices;
}>();

const data = defineModel<PricingTiersData>('data', { required: true });

const newTier = (): PricingTier => ({
    name: '',
    price: '',
    period: null,
    description: null,
    features: [],
    highlighted: false,
    cta: null,
});

// Features are edited as one textarea, one per line. While typing, lines are
// kept as-is (trimming on every keystroke would eat spaces and new lines);
// blank lines and stray spaces are tidied when the field loses focus.
function featuresText(tier: PricingTier): string {
    return (tier.features ?? []).join('\n');
}

function setFeatures(tier: PricingTier, value: string | number | null): void {
    const text = String(value ?? '');
    tier.features = text === '' ? [] : text.split('\n');
}

function tidyFeatures(tier: PricingTier): void {
    tier.features = (tier.features ?? [])
        .map((line) => line.trim())
        .filter((line) => line !== '');
}

function featuresError(
    errors: Record<string, string>,
    index: number,
): string | undefined {
    const prefix = `tiers.${index}.features`;

    return errors[prefix] ?? Object.values(scoped(errors, prefix))[0];
}
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
            v-model="data.tiers"
            label="Pachete"
            :new-item="newTier"
            add-label="Adaugă un pachet"
            :max="4"
            :error="errors.tiers"
        >
            <template #default="{ item, index }">
                <div class="grid gap-3">
                    <TextField
                        v-model="item.name"
                        label="Nume"
                        :max="60"
                        :error="errors[`tiers.${index}.name`]"
                        required
                    />
                    <div class="grid gap-3 sm:grid-cols-2">
                        <TextField
                            v-model="item.price"
                            label="Preț"
                            :max="40"
                            placeholder="ex. de la 1.500 €"
                            :error="errors[`tiers.${index}.price`]"
                            required
                        />
                        <TextField
                            v-model="item.period"
                            label="Perioadă"
                            :max="40"
                            placeholder="ex. / lună"
                            :error="errors[`tiers.${index}.period`]"
                        />
                    </div>
                    <TextField
                        v-model="item.description"
                        label="Descriere"
                        :max="300"
                        multiline
                        :error="errors[`tiers.${index}.description`]"
                    />
                    <div @focusout="tidyFeatures(item)">
                        <TextField
                            :model-value="featuresText(item)"
                            label="Ce include"
                            multiline
                            :rows="5"
                            hint="Câte o caracteristică pe rând — cel mult 20, fiecare de până la 120 de caractere."
                            :error="featuresError(errors, index)"
                            @update:model-value="setFeatures(item, $event)"
                        />
                    </div>
                    <SwitchField
                        v-model="item.highlighted"
                        label="Pachet evidențiat"
                        hint="Scos în evidență ca recomandarea principală."
                    />
                    <CtaField
                        v-model="item.cta"
                        label="Buton"
                        :errors="scoped(errors, `tiers.${index}.cta`)"
                    />
                </div>
            </template>
        </ItemList>
        <TextField
            v-model="data.note"
            label="Notă"
            :max="300"
            placeholder="ex. Prețurile nu includ TVA."
            :error="errors.note"
        />
    </div>
</template>
