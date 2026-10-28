<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import ConfirmAction from '@/components/admin/ConfirmAction.vue';
import Pager from '@/components/admin/Pager.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { clearIgnored, destroy, index, update } from '@/routes/admin/not-found';
import { index as redirectsIndex } from '@/routes/admin/redirects';
import type { Paginated } from '@/types/media';

type LogRow = {
    id: number;
    path: string;
    hits: number;
    last_seen_at: string | null;
    first_seen_at: string | null;
    last_referrer: string | null;
    is_ignored: boolean;
    has_redirect: boolean;
};

const props = defineProps<{
    logs: Paginated<LogRow>;
    filters: { q: string; ignored: boolean };
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Erori 404', href: index() }],
    },
});

const search = ref(props.filters.q);
const ignored = ref(props.filters.ignored);

let debounce: ReturnType<typeof setTimeout> | undefined;

watch([search, ignored], () => {
    clearTimeout(debounce);
    debounce = setTimeout(() => {
        router.get(
            index.url(),
            {
                q: search.value || undefined,
                ignored: ignored.value ? 1 : undefined,
            },
            { preserveState: true, replace: true },
        );
    }, 300);
});

function setIgnored(log: LogRow, value: boolean): void {
    router.patch(
        update.url(log.id),
        { is_ignored: value },
        { preserveScroll: true },
    );
}

function remove(log: LogRow): void {
    router.delete(destroy.url(log.id), { preserveScroll: true });
}

function clearIgnoredList(): void {
    router.delete(clearIgnored.url(), { preserveScroll: true });
}
</script>

<template>
    <Head title="Erori 404" />

    <div class="flex flex-col gap-4 p-4">
        <div class="flex items-center justify-between gap-3">
            <h1 class="text-2xl font-bold tracking-tight">Erori 404</h1>
            <ConfirmAction
                v-if="filters.ignored && logs.data.length > 0"
                title="Golești lista ignorată?"
                description="Adresele ignorate se șterg din jurnal. Dacă mai sunt cerute, vor apărea din nou în listă."
                confirm-label="Golește"
                @confirm="clearIgnoredList"
            >
                <Button
                    type="button"
                    variant="outline"
                    class="text-destructive"
                >
                    Golește lista ignorată
                </Button>
            </ConfirmAction>
        </div>

        <p class="text-sm text-muted-foreground">
            Adrese cerute de vizitatori sau roboți care nu există. Pentru cele
            reale, creează o redirecționare; ignoră zgomotul.
        </p>

        <div class="flex flex-wrap items-center gap-3">
            <Input
                v-model="search"
                type="search"
                placeholder="Caută adresă…"
                class="max-w-xs"
            />
            <label class="flex items-center gap-2 text-sm">
                <input v-model="ignored" type="checkbox" class="size-4" />
                Ignorate
            </label>
        </div>

        <div class="overflow-x-auto rounded-xl border">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left">
                    <tr>
                        <th class="px-4 py-2">Cale</th>
                        <th class="px-4 py-2">Accesări</th>
                        <th class="px-4 py-2">Văzută ultima dată</th>
                        <th class="px-4 py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="log in logs.data" :key="log.id" class="border-t">
                        <td class="max-w-md px-4 py-2">
                            <div class="font-mono text-xs break-all">
                                {{ log.path }}
                            </div>
                            <div
                                v-if="log.last_referrer"
                                class="truncate text-xs text-muted-foreground"
                                :title="log.last_referrer"
                            >
                                din {{ log.last_referrer }}
                            </div>
                        </td>
                        <td class="px-4 py-2 tabular-nums">{{ log.hits }}</td>
                        <td class="px-4 py-2 text-muted-foreground">
                            {{ log.last_seen_at }}
                            <div
                                v-if="log.first_seen_at"
                                class="text-xs text-muted-foreground"
                            >
                                prima dată {{ log.first_seen_at }}
                            </div>
                        </td>
                        <td class="px-4 py-2 text-right whitespace-nowrap">
                            <Badge v-if="log.has_redirect" variant="secondary">
                                Redirecționată
                            </Badge>
                            <Button v-else as-child variant="ghost" size="sm">
                                <Link
                                    :href="
                                        redirectsIndex({
                                            query: { source: log.path },
                                        })
                                    "
                                >
                                    Creează redirecționare
                                </Link>
                            </Button>
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                @click="setIgnored(log, !log.is_ignored)"
                            >
                                {{
                                    log.is_ignored ? 'Nu mai ignora' : 'Ignoră'
                                }}
                            </Button>
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                class="text-destructive"
                                title="Dacă adresa mai este cerută, va apărea din nou."
                                @click="remove(log)"
                            >
                                Șterge
                            </Button>
                        </td>
                    </tr>
                    <tr v-if="logs.data.length === 0">
                        <td
                            colspan="4"
                            class="px-4 py-8 text-center text-muted-foreground"
                        >
                            {{
                                filters.ignored
                                    ? 'Nicio adresă ignorată.'
                                    : 'Nicio eroare 404 înregistrată.'
                            }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <Pager
            :prev-url="logs.prev_page_url"
            :next-url="logs.next_page_url"
            :current="logs.current_page"
            :last="logs.last_page"
        />
    </div>
</template>
