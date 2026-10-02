<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { History } from '@lucide/vue';
import ConfirmAction from '@/components/admin/ConfirmAction.vue';
import { Button } from '@/components/ui/button';
import { restore } from '@/routes/admin/revisions';
import type { RevisionSummary } from '@/types/content';

/**
 * Earlier saved versions. The newest is the current content, so only older
 * ones offer "restore".
 */
defineProps<{ revisions: RevisionSummary[] }>();

function restoreRevision(id: number): void {
    router.post(restore.url(id), {}, { preserveScroll: false });
}
</script>

<template>
    <details class="rounded-xl border bg-card p-4">
        <summary
            class="flex cursor-pointer items-center gap-2 text-sm font-medium"
        >
            <History class="size-4" /> Revizii ({{ revisions.length }})
        </summary>
        <ol class="mt-3 flex flex-col gap-2 text-sm">
            <li
                v-for="(revision, index) in revisions"
                :key="revision.id"
                class="flex items-center justify-between gap-2"
            >
                <span>
                    {{ revision.created_at }}
                    <span class="text-muted-foreground">
                        · {{ revision.user ?? 'sistem' }}
                    </span>
                </span>
                <span v-if="index === 0" class="text-xs text-muted-foreground">
                    actuală
                </span>
                <ConfirmAction
                    v-else
                    title="Restaurezi această versiune?"
                    description="Conținutul actual e înlocuit cu cel din versiunea aleasă. Modificările nesalvate se pierd; versiunea actuală rămâne în listă."
                    confirm-label="Restaurează"
                    @confirm="restoreRevision(revision.id)"
                >
                    <Button type="button" variant="ghost" size="sm">
                        Restaurează
                    </Button>
                </ConfirmAction>
            </li>
        </ol>
    </details>
</template>
