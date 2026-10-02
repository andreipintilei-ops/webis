<script setup lang="ts">
import { ImagePlus } from '@lucide/vue';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import ItemList from '@/components/admin/fields/ItemList.vue';
import SelectField from '@/components/admin/fields/SelectField.vue';
import TextField from '@/components/admin/fields/TextField.vue';
import AssetField from '@/components/admin/media/AssetField.vue';
import MediaPicker from '@/components/admin/media/MediaPicker.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import type { Choices } from '@/types/content';
import type { Asset } from '@/types/media';

type GalleryImage = {
    asset_id: number | null;
    caption: string | null;
};

/** Mirrors App\Blocks\GalleryBlock. */
type GalleryData = {
    heading: string | null;
    columns: number;
    images: GalleryImage[];
};

const MAX_IMAGES = 30;

defineProps<{
    errors: Record<string, string>;
    choices: Choices;
}>();

const data = defineModel<GalleryData>('data', { required: true });

const pickerOpen = ref(false);

const newImage = (): GalleryImage => ({ asset_id: null, caption: null });

function addAssets(assets: Asset[]): void {
    const room = MAX_IMAGES - data.value.images.length;

    data.value.images = [
        ...data.value.images,
        ...assets
            .slice(0, Math.max(room, 0))
            .map((asset) => ({ asset_id: asset.id, caption: null })),
    ];
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
            v-model="data.images"
            label="Imagini"
            :new-item="newImage"
            add-label="Adaugă o imagine"
            :max="MAX_IMAGES"
            :error="errors.images"
        >
            <template #default="{ item, index }">
                <div class="grid gap-3">
                    <div class="grid gap-1.5">
                        <Label>
                            Imagine
                            <span class="text-destructive">*</span>
                        </Label>
                        <AssetField v-model="item.asset_id" />
                        <InputError
                            :message="errors[`images.${index}.asset_id`]"
                        />
                    </div>
                    <TextField
                        v-model="item.caption"
                        label="Legendă"
                        :max="200"
                        :error="errors[`images.${index}.caption`]"
                    />
                </div>
            </template>
        </ItemList>
        <Button
            v-if="data.images.length < MAX_IMAGES"
            type="button"
            variant="outline"
            size="sm"
            class="self-start justify-self-start"
            @click="pickerOpen = true"
        >
            <ImagePlus /> Adaugă mai multe imagini
        </Button>
        <MediaPicker
            v-model:open="pickerOpen"
            multiple
            title="Alege imaginile galeriei"
            @select="addAssets"
        />
    </div>
</template>
