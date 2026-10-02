<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { CalendarClock, FilePen, Inbox, TriangleAlert } from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import { dashboard } from '@/routes/admin';
import * as leads from '@/routes/admin/leads';
import * as notFound from '@/routes/admin/not-found';
import * as redirects from '@/routes/admin/redirects';

type LeadRow = {
    id: number;
    name: string;
    service: string | null;
    status: string;
    status_label: string;
    created_at: string | null;
};

type ScheduledRow = {
    type: string;
    title: string;
    url: string | null;
    published_at: string | null;
};

defineProps<{
    stats: {
        newLeads: number;
        leadsThisMonth: number;
        drafts: number;
        published: { pages: number; projects: number; posts: number };
    };
    recentLeads: LeadRow[];
    scheduled: ScheduledRow[];
    topNotFound: { id: number; path: string; hits: number }[] | null;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Panou', href: dashboard() }],
    },
});
</script>

<template>
    <Head title="Panou" />

    <div class="flex flex-col gap-6 p-4">
        <h1 class="text-2xl font-bold tracking-tight">Panou</h1>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <Link
                :href="leads.index({ query: { status: 'new' } })"
                class="rounded-xl border bg-card p-4 hover:bg-muted/40"
            >
                <div
                    class="flex items-center gap-2 text-sm text-muted-foreground"
                >
                    <Inbox class="size-4" /> Cereri noi
                </div>
                <div class="mt-2 text-3xl font-semibold tabular-nums">
                    {{ stats.newLeads }}
                </div>
                <div class="text-xs text-muted-foreground">
                    {{ stats.leadsThisMonth }} luna aceasta
                </div>
            </Link>
            <div class="rounded-xl border bg-card p-4">
                <div
                    class="flex items-center gap-2 text-sm text-muted-foreground"
                >
                    <FilePen class="size-4" /> Ciorne
                </div>
                <div class="mt-2 text-3xl font-semibold tabular-nums">
                    {{ stats.drafts }}
                </div>
                <div class="text-xs text-muted-foreground">
                    pagini, proiecte și articole nepublicate
                </div>
            </div>
            <div class="rounded-xl border bg-card p-4 lg:col-span-2">
                <div class="text-sm text-muted-foreground">Publicate</div>
                <dl class="mt-2 grid grid-cols-3 gap-2">
                    <div>
                        <dt class="text-xs text-muted-foreground">Pagini</dt>
                        <dd class="text-2xl font-semibold tabular-nums">
                            {{ stats.published.pages }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs text-muted-foreground">Proiecte</dt>
                        <dd class="text-2xl font-semibold tabular-nums">
                            {{ stats.published.projects }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs text-muted-foreground">Articole</dt>
                        <dd class="text-2xl font-semibold tabular-nums">
                            {{ stats.published.posts }}
                        </dd>
                    </div>
                </dl>
            </div>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <section class="rounded-xl border bg-card p-4">
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="font-medium">Cereri recente</h2>
                    <Link
                        :href="leads.index()"
                        class="text-sm text-primary hover:underline"
                        >Toate</Link
                    >
                </div>
                <ul v-if="recentLeads.length" class="divide-y text-sm">
                    <li
                        v-for="lead in recentLeads"
                        :key="lead.id"
                        class="flex items-center justify-between gap-3 py-2"
                    >
                        <Link
                            :href="leads.show(lead.id)"
                            class="min-w-0 hover:underline"
                        >
                            <span class="font-medium">{{ lead.name }}</span>
                            <span
                                v-if="lead.service"
                                class="text-muted-foreground"
                            >
                                · {{ lead.service }}</span
                            >
                        </Link>
                        <div class="flex shrink-0 items-center gap-2">
                            <span class="text-xs text-muted-foreground">{{
                                lead.created_at
                            }}</span>
                            <Badge
                                :variant="
                                    lead.status === 'new'
                                        ? 'default'
                                        : 'secondary'
                                "
                                >{{ lead.status_label }}</Badge
                            >
                        </div>
                    </li>
                </ul>
                <p v-else class="text-sm text-muted-foreground">
                    Nicio cerere încă.
                </p>
            </section>

            <section class="rounded-xl border bg-card p-4">
                <h2 class="mb-3 flex items-center gap-2 font-medium">
                    <CalendarClock class="size-4" /> Programate
                </h2>
                <ul v-if="scheduled.length" class="divide-y text-sm">
                    <li
                        v-for="item in scheduled"
                        :key="`${item.type}-${item.title}`"
                        class="flex items-center justify-between gap-3 py-2"
                    >
                        <div class="min-w-0">
                            <span class="text-muted-foreground"
                                >{{ item.type }}:</span
                            >
                            <Link
                                v-if="item.url"
                                :href="item.url"
                                class="ml-1 hover:underline"
                                >{{ item.title }}</Link
                            >
                            <span v-else class="ml-1">{{ item.title }}</span>
                        </div>
                        <span class="shrink-0 text-xs text-muted-foreground">
                            {{ item.published_at }}
                        </span>
                    </li>
                </ul>
                <p v-else class="text-sm text-muted-foreground">
                    Nimic programat.
                </p>
            </section>

            <section
                v-if="topNotFound !== null"
                class="rounded-xl border bg-card p-4 lg:col-span-2"
            >
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="flex items-center gap-2 font-medium">
                        <TriangleAlert class="size-4" /> Cele mai frecvente
                        erori 404
                    </h2>
                    <Link
                        :href="notFound.index()"
                        class="text-sm text-primary hover:underline"
                        >Toate</Link
                    >
                </div>
                <ul v-if="topNotFound.length" class="divide-y text-sm">
                    <li
                        v-for="log in topNotFound"
                        :key="log.id"
                        class="flex items-center justify-between gap-3 py-2"
                    >
                        <code class="truncate">{{ log.path }}</code>
                        <div class="flex shrink-0 items-center gap-3">
                            <span class="text-muted-foreground tabular-nums"
                                >{{ log.hits }} accesări</span
                            >
                            <Link
                                :href="
                                    redirects.index({
                                        query: { source: log.path },
                                    })
                                "
                                class="text-primary hover:underline"
                                >Redirecționează</Link
                            >
                        </div>
                    </li>
                </ul>
                <p v-else class="text-sm text-muted-foreground">
                    Nicio eroare 404 înregistrată.
                </p>
            </section>
        </div>
    </div>
</template>
