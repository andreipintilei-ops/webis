<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextField from '@/components/admin/fields/TextField.vue';
import AssetField from '@/components/admin/media/AssetField.vue';
import { Label } from '@/components/ui/label';
import type { Choices } from '@/types/content';

/** Mirrors App\Blocks\VideoBlock. */
type VideoData = {
    title: string;
    url: string;
    /** Derived from `url` on save — never edited here. */
    youtube_id: string | null;
    poster_asset_id: number | null;
    caption: string | null;
};

defineProps<{
    errors: Record<string, string>;
    choices: Choices;
}>();

const data = defineModel<VideoData>('data', { required: true });
</script>

<template>
    <div class="grid gap-4">
        <TextField
            v-model="data.title"
            label="Titlu"
            :max="160"
            hint="Numele accesibil al butonului de redare și numele videoclipului pentru Google."
            :error="errors.title"
            required
        />
        <TextField
            v-model="data.url"
            label="Link"
            :max="255"
            placeholder="https://www.youtube.com/watch?v=…"
            hint="Link YouTube — watch, youtu.be sau shorts"
            :error="errors.url"
            required
        />
        <div class="grid gap-1.5">
            <Label>Imagine de previzualizare</Label>
            <AssetField v-model="data.poster_asset_id" />
            <p class="text-xs text-muted-foreground">
                Opțională — altfel se folosește miniatura de pe YouTube.
            </p>
            <InputError :message="errors.poster_asset_id" />
        </div>
        <TextField
            v-model="data.caption"
            label="Descriere"
            :max="300"
            multiline
            :error="errors.caption"
        />
    </div>
</template>
