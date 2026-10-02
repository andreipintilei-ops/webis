<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { useSortable } from '@vueuse/integrations/useSortable';
import { GripVertical, Plus } from '@lucide/vue';
import { computed, ref, useTemplateRef, watch } from 'vue';
import ConfirmAction from '@/components/admin/ConfirmAction.vue';
import Pager from '@/components/admin/Pager.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { index as categoriesIndex } from '@/routes/admin/project-categories';
import {
    create,
    edit,
    forceDestroy,
    index,
    reorder,
    restore,
} from '@/routes/admin/projects';
import type { Option } from '@/types/content';
import type { Paginated } from '@/types/media';

type ProjectRow = {
    id: number;
    title: string;
    url: string;
    client: string | null;
    year: number | null;
    categories: string[];
    is_featured: boolean;
    status: string;
    status_label: string;
    is_live: boolean;
    published_at: string | null;
    trashed: boolean;
};

const props = defineProps<{
    projects: Paginated<ProjectRow>;
    filters: {
        q: string;
        status: string;
        category: string;
        featured: boolean;
        trashed: boolean;
    };
    statuses: Option[];
    categories: Option[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Portofoliu', href: index() }],
    },
});

const search = ref(props.filters.q);
const status = ref(props.filters.status);
const category = ref(props.filters.category);
const featured = ref(props.filters.featured);
const trashed = ref(props.filters.trashed);

const selectClass =
    'border-input bg-background h-9 rounded-md border px-3 py-1 text-sm shadow-xs outline-none';

let debounce: ReturnType<typeof setTimeout> | undefined;

watch([search, status, category, featured, trashed], () => {
    clearTimeout(debounce);
    debounce = setTimeout(() => {
        router.get(
            index.url(),
            {
                q: search.value || undefined,
                status: status.value || undefined,
                category: category.value || undefined,
                featured: featured.value ? 1 : undefined,
                trashed: trashed.value ? 1 : undefined,
            },
            { preserveState: true, replace: true },
        );
    }, 300);
});

// Dragging is offered only when the whole portfolio is shown, in site order:
// no filters, not the trash, and everything on one page (the server numbers
// the posted ids from zero, so a partial list would clash with other pages).
const canReorder = computed(
    () =>
        !props.filters.q &&
        !props.filters.status &&
        !props.filters.category &&
        !props.filters.featured &&
        !props.filters.trashed &&
        props.projects.last_page === 1,
);

const rows = ref<ProjectRow[]>([...props.projects.data]);
watch(
    () => props.projects.data,
    (data) => (rows.value = [...data]),
);

const body = useTemplateRef<HTMLElement>('body');

useSortable(body, rows, {
    handle: '[data-drag-handle]',
    animation: 150,
    onUpdate: (event: { oldIndex?: number; newIndex?: number }) => {
        if (event.oldIndex === undefined || event.newIndex === undefined) {
            return;
        }

        const items = [...rows.value];
        const [moved] = items.splice(event.oldIndex, 1);
        items.splice(event.newIndex, 0, moved);
        rows.value = items;

        router.post(
            reorder.url(),
            { ids: items.map((row) => row.id) },
            { preserveScroll: true, preserveState: true },
        );
    },
});
</script>

<template>
    <Head title="Portofoliu" />

    <div class="flex flex-col gap-4 p-4">
        <div class="flex items-center justify-between gap-3">
            <h1 class="text-2xl font-bold tracking-tight">Portofoliu</h1>
            <div class="flex items-center gap-2">
                <Button variant="outline" as-child>
                    <Link :href="categoriesIndex()">Categorii</Link>
                </Button>
                <Button as-child>
                    <Link :href="create()"><Plus /> Proiect nou</Link>
                </Button>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <Input
                v-model="search"
                type="search"
                placeholder="Caută titlu, adresă sau client…"
                class="max-w-xs"
            />
            <select v-model="status" :class="selectClass">
                <option value="">Toate stările</option>
                <option v-for="s in statuses" :key="s.value" :value="s.value">
                    {{ s.label }}
                </option>
            </select>
            <select v-model="category" :class="selectClass">
                <option value="">Toate categoriile</option>
                <option v-for="c in categories" :key="c.value" :value="c.value">
                    {{ c.label }}
                </option>
            </select>
            <label class="flex items-center gap-2 text-sm">
                <input v-model="featured" type="checkbox" class="size-4" />
                Doar recomandate
            </label>
            <label class="flex items-center gap-2 text-sm">
                <input v-model="trashed" type="checkbox" class="size-4" />
                Coș
            </label>
        </div>

        <p v-if="canReorder" class="text-sm text-muted-foreground">
            Trage rândurile pentru a schimba ordinea în care apar pe site.
        </p>

        <div class="overflow-x-auto rounded-xl border">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left">
                    <tr>
                        <th v-if="canReorder" class="w-8 px-2 py-2"></th>
                        <th class="px-4 py-2">Titlu</th>
                        <th class="px-4 py-2">Client</th>
                        <th class="px-4 py-2">An</th>
                        <th class="px-4 py-2">Categorii</th>
                        <th class="px-4 py-2">Stare</th>
                        <th class="px-4 py-2"></th>
                    </tr>
                </thead>
                <tbody ref="body">
                    <tr v-for="row in rows" :key="row.id" class="border-t">
                        <td v-if="canReorder" class="px-2 py-2">
                            <button
                                type="button"
                                data-drag-handle
                                class="cursor-grab text-muted-foreground"
                                aria-label="Trage pentru a muta"
                            >
                                <GripVertical class="size-4" />
                            </button>
                        </td>
                        <td class="px-4 py-2">
                            <div class="flex items-center gap-2">
                                <Link
                                    v-if="!row.trashed"
                                    :href="edit(row.id)"
                                    class="font-medium hover:underline"
                                    >{{ row.title }}</Link
                                >
                                <span v-else class="font-medium">{{
                                    row.title
                                }}</span>
                                <Badge v-if="row.is_featured" variant="outline"
                                    >Recomandat</Badge
                                >
                            </div>
                            <div class="text-xs text-muted-foreground">
                                {{ row.url }}
                            </div>
                        </td>
                        <td class="px-4 py-2 text-muted-foreground">
                            {{ row.client ?? '—' }}
                        </td>
                        <td class="px-4 py-2 text-muted-foreground">
                            {{ row.year ?? '—' }}
                        </td>
                        <td class="px-4 py-2 text-muted-foreground">
                            {{
                                row.categories.length
                                    ? row.categories.join(', ')
                                    : '—'
                            }}
                        </td>
                        <td class="px-4 py-2">
                            <Badge
                                :variant="row.is_live ? 'default' : 'secondary'"
                                >{{ row.status_label }}</Badge
                            >
                            <div
                                v-if="row.status === 'scheduled'"
                                class="text-xs text-muted-foreground"
                            >
                                {{ row.published_at }}
                            </div>
                        </td>
                        <td class="px-4 py-2 text-right whitespace-nowrap">
                            <template v-if="row.trashed">
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    @click="router.post(restore.url(row.id))"
                                >
                                    Restaurează
                                </Button>
                                <ConfirmAction
                                    title="Ștergi proiectul definitiv?"
                                    description="Nu mai poate fi recuperat. Adresa lui devine liberă; linkurile vechi vor da 404 dacă nu adaugi o redirecționare."
                                    confirm-label="Șterge definitiv"
                                    @confirm="
                                        router.delete(forceDestroy.url(row.id))
                                    "
                                >
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        class="text-destructive"
                                    >
                                        Șterge definitiv
                                    </Button>
                                </ConfirmAction>
                            </template>
                            <Link
                                v-else
                                :href="edit(row.id)"
                                class="text-primary hover:underline"
                                >Editează</Link
                            >
                        </td>
                    </tr>
                    <tr v-if="rows.length === 0">
                        <td
                            colspan="7"
                            class="px-4 py-8 text-center text-muted-foreground"
                        >
                            {{
                                filters.trashed
                                    ? 'Coșul este gol.'
                                    : 'Niciun proiect găsit.'
                            }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <Pager
            :prev-url="projects.prev_page_url"
            :next-url="projects.next_page_url"
            :current="projects.current_page"
            :last="projects.last_page"
        />
    </div>
</template>
