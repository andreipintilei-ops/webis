<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Download } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import Pager from '@/components/admin/Pager.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { exportMethod as exportRoute, index, show } from '@/routes/admin/leads';
import type { Paginated } from '@/types/media';

type LeadRow = {
    id: number;
    name: string;
    email: string | null;
    phone: string | null;
    company: string | null;
    service: string | null;
    budget: string | null;
    message: string | null;
    status: string;
    status_label: string;
    assignee: string | null;
    created_at: string | null;
};

type StatusCount = { value: string; label: string; count: number };

const props = defineProps<{
    leads: Paginated<LeadRow>;
    filters: { q: string; status: string; mine: boolean };
    statuses: StatusCount[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Cereri de ofertă', href: index() }],
    },
});

const search = ref(props.filters.q);
const status = ref(props.filters.status);
const mine = ref(props.filters.mine);

const query = computed(() => ({
    q: search.value || undefined,
    status: status.value || undefined,
    mine: mine.value ? 1 : undefined,
}));

function reload(): void {
    router.get(index.url(), query.value, {
        preserveState: true,
        replace: true,
    });
}

let debounce: ReturnType<typeof setTimeout> | undefined;

// Typing waits for a pause; pills and the checkbox apply at once.
watch(search, () => {
    clearTimeout(debounce);
    debounce = setTimeout(reload, 300);
});

watch([status, mine], () => {
    clearTimeout(debounce);
    reload();
});

// "All" leaves spam out, so its count leaves it out too.
const allCount = computed(() =>
    props.statuses
        .filter((s) => s.value !== 'spam')
        .reduce((sum, s) => sum + s.count, 0),
);

const exportUrl = computed(() => exportRoute.url({ query: query.value }));

function pillClass(active: boolean): string {
    return active
        ? 'border-primary bg-primary text-primary-foreground'
        : 'bg-background text-muted-foreground hover:bg-muted hover:text-foreground';
}

function badgeVariant(value: string): 'default' | 'destructive' | 'secondary' {
    if (value === 'new') {
        return 'default';
    }

    return value === 'spam' ? 'destructive' : 'secondary';
}

function open(row: LeadRow): void {
    router.visit(show.url(row.id));
}
</script>

<template>
    <Head title="Cereri de ofertă" />

    <div class="flex flex-col gap-4 p-4">
        <div class="flex items-center justify-between gap-3">
            <h1 class="text-2xl font-bold tracking-tight">Cereri de ofertă</h1>
            <Button as-child variant="outline">
                <a :href="exportUrl"><Download /> Exportă CSV</a>
            </Button>
        </div>

        <div class="flex flex-wrap gap-2">
            <button
                type="button"
                class="rounded-full border px-3 py-1 text-sm transition-colors"
                :class="pillClass(status === '')"
                @click="status = ''"
            >
                Toate (fără spam)
                <span class="tabular-nums opacity-70">{{ allCount }}</span>
            </button>
            <button
                v-for="s in statuses"
                :key="s.value"
                type="button"
                class="rounded-full border px-3 py-1 text-sm transition-colors"
                :class="pillClass(status === s.value)"
                @click="status = s.value"
            >
                {{ s.label }}
                <span class="tabular-nums opacity-70">{{ s.count }}</span>
            </button>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <Input
                v-model="search"
                type="search"
                placeholder="Caută nume, email, telefon sau firmă…"
                class="max-w-sm"
            />
            <label class="flex items-center gap-2 text-sm">
                <input v-model="mine" type="checkbox" class="size-4" />
                Doar ale mele
            </label>
        </div>

        <div class="overflow-x-auto rounded-xl border">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left">
                    <tr>
                        <th class="px-4 py-2">Data</th>
                        <th class="px-4 py-2">Nume</th>
                        <th class="px-4 py-2">Contact</th>
                        <th class="px-4 py-2">Serviciu</th>
                        <th class="px-4 py-2">Stare</th>
                        <th class="px-4 py-2">Responsabil</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="row in leads.data"
                        :key="row.id"
                        class="cursor-pointer border-t align-top hover:bg-muted/30"
                        @click="open(row)"
                    >
                        <td
                            class="px-4 py-2 whitespace-nowrap text-muted-foreground"
                        >
                            {{ row.created_at }}
                        </td>
                        <td class="px-4 py-2">
                            <Link
                                :href="show(row.id)"
                                class="font-medium hover:underline"
                                @click.stop
                                >{{ row.name }}</Link
                            >
                            <div
                                v-if="row.company"
                                class="text-xs text-muted-foreground"
                            >
                                {{ row.company }}
                            </div>
                            <div
                                v-if="row.message"
                                class="line-clamp-1 max-w-xs text-xs text-muted-foreground"
                            >
                                {{ row.message }}
                            </div>
                        </td>
                        <td class="px-4 py-2">
                            <div v-if="row.email">{{ row.email }}</div>
                            <div
                                v-if="row.phone"
                                class="text-xs text-muted-foreground"
                            >
                                {{ row.phone }}
                            </div>
                        </td>
                        <td class="px-4 py-2">
                            <div>{{ row.service ?? '—' }}</div>
                            <div
                                v-if="row.budget"
                                class="text-xs text-muted-foreground"
                            >
                                {{ row.budget }}
                            </div>
                        </td>
                        <td class="px-4 py-2">
                            <Badge :variant="badgeVariant(row.status)">{{
                                row.status_label
                            }}</Badge>
                        </td>
                        <td class="px-4 py-2 text-muted-foreground">
                            {{ row.assignee ?? '—' }}
                        </td>
                    </tr>
                    <tr v-if="leads.data.length === 0">
                        <td
                            colspan="6"
                            class="px-4 py-8 text-center text-muted-foreground"
                        >
                            Nicio cerere găsită.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <Pager
            :prev-url="leads.prev_page_url"
            :next-url="leads.next_page_url"
            :current="leads.current_page"
            :last="leads.last_page"
        />
    </div>
</template>
