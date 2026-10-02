<script setup lang="ts">
import { ExternalLink } from '@lucide/vue';
import { computed, useId } from 'vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import type { ContentStatus, Option } from '@/types/content';

/**
 * Status, publication date and the save button. Times are Romanian local
 * time; the server converts to UTC.
 */
const props = withDefaults(
    defineProps<{
        statuses: Option[];
        isLive: boolean;
        url: string | null;
        processing: boolean;
        isDirty: boolean;
        errors?: Record<string, string>;
    }>(),
    { errors: () => ({}) },
);

const status = defineModel<ContentStatus>('status', { required: true });
const publishedAt = defineModel<string | null>('publishedAt', {
    required: true,
});

const id = useId();

const dateLabel = computed(() =>
    status.value === 'scheduled' ? 'Se publică la' : 'Data publicării',
);

const saveLabel = computed(() => {
    if (status.value === 'published' && !props.isLive) {
        return 'Publică';
    }

    if (status.value === 'scheduled') {
        return 'Programează';
    }

    return 'Salvează';
});
</script>

<template>
    <div class="flex flex-col gap-4 rounded-xl border bg-card p-4">
        <div class="flex items-center justify-between">
            <h2 class="text-sm font-medium">Publicare</h2>
            <Badge :variant="isLive ? 'default' : 'secondary'">
                {{ isLive ? 'Pe site' : 'Nepublicat' }}
            </Badge>
        </div>

        <fieldset class="grid gap-2">
            <legend class="sr-only">Stare</legend>
            <label
                v-for="option in statuses"
                :key="option.value"
                class="flex items-center gap-2 text-sm"
            >
                <input
                    v-model="status"
                    type="radio"
                    :value="option.value"
                    class="size-4"
                />
                {{ option.label }}
            </label>
            <InputError :message="errors.status" />
        </fieldset>

        <div v-if="status !== 'draft'" class="grid gap-1.5">
            <Label :for="id">{{ dateLabel }}</Label>
            <input
                :id="id"
                v-model="publishedAt"
                type="datetime-local"
                class="h-9 rounded-md border border-input bg-background px-3 text-sm shadow-xs"
            />
            <p
                v-if="status === 'published'"
                class="text-xs text-muted-foreground"
            >
                Gol = acum.
            </p>
            <InputError :message="errors.published_at" />
        </div>

        <div class="flex items-center gap-2">
            <Button type="submit" :disabled="processing" class="flex-1">
                {{ saveLabel }}
            </Button>
            <Button
                v-if="url && isLive"
                as="a"
                :href="url"
                target="_blank"
                rel="noopener"
                variant="outline"
                size="icon"
                aria-label="Vezi pe site"
                title="Vezi pe site"
            >
                <ExternalLink />
            </Button>
        </div>
        <p v-if="isDirty" class="text-xs text-amber-700 dark:text-amber-400">
            Ai modificări nesalvate.
        </p>
    </div>
</template>
