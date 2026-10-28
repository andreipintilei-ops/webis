<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { useSortable } from '@vueuse/integrations/useSortable';
import { GripVertical, Plus } from '@lucide/vue';
import { computed, ref, useTemplateRef, watch } from 'vue';
import ConfirmAction from '@/components/admin/ConfirmAction.vue';
import Pager from '@/components/admin/Pager.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import {
    create,
    edit,
    forceDestroy,
    index,
    reorder,
    restore,
} from '@/routes/admin/pages';
import type { Option } from '@/types/content';
import type { Paginated } from '@/types/media';

type PageRow = {
    id: number;
    title: string;
    url: string;
    type: string;
    type_label: string;
    status: string;
    status_label: string;
    is_live: boolean;
    published_at: string | null;
    updated_at: string | null;
    trashed: boolean;
};

const props = defineProps<{
    pages: Paginated<PageRow>;
    filters: { q: string; type: string; status: string; trashed: boolean };
    types: Option[];
    statuses: Option[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Pagini', href: index() }],
    },
});

const search = ref(props.filters.q);
const type = ref(props.filters.type);
const status = ref(props.filters.status);
const trashed = ref(props.filters.trashed);

const selectClass =
    'border-input bg-background h-9 rounded-md border px-3 py-1 text-sm shadow-xs outline-none';

let debounce: ReturnType<typeof setTimeout> | undefined;

watch([search, type, status, trashed], () => {
    clearTimeout(debounce);
    debounce = setTimeout(() => {
        router.get(
            index.url(),
            {
                q: search.value || undefined,
                type: type.value || undefined,
                status: status.value || undefined,
                trashed: trashed.value ? 1 : undefined,
            },
            { preserveState: true, replace: true },
        );
    }, 300);
});

// Dragging is offered when one type is shown in full, in its site order.
const canReorder = computed(
    () =>
        props.filters.type !== '' && !props.filters.trashed && !props.filters.q,
);

const rows = ref<PageRow[]>([...props.pages.data]);
watch(
    () => props.pages.data,
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
    <Head title="Pagini" />

    <div class="flex flex-col gap-4 p-4">
        <div class="flex items-center justify-between gap-3">
            <h1 class="text-2xl font-bold tracking-tight">Pagini</h1>
            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <Button><Plus /> Pagină nouă</Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end">
                    <DropdownMenuItem
                        v-for="option in types"
                        :key="option.value"
                        as-child
                    >
                        <Link :href="create({ query: { type: option.value } })">
                            {{ option.label }}
                        </Link>
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <Input
                v-model="search"
                type="search"
                placeholder="Caută titlu sau adresă…"
                class="max-w-xs"
            />
            <select v-model="type" :class="selectClass">
                <option value="">Toate tipurile</option>
                <option v-for="t in types" :key="t.value" :value="t.value">
                    {{ t.label }}
                </option>
            </select>
            <select v-model="status" :class="selectClass">
                <option value="">Toate stările</option>
                <option v-for="s in statuses" :key="s.value" :value="s.value">
                    {{ s.label }}
                </option>
            </select>
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
                        <th class="px-4 py-2">Tip</th>
                        <th class="px-4 py-2">Stare</th>
                        <th class="px-4 py-2">Modificată</th>
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
                            <Link
                                v-if="!row.trashed"
                                :href="edit(row.id)"
                                class="font-medium hover:underline"
                                >{{ row.title }}</Link
                            >
                            <span v-else class="font-medium">{{
                                row.title
                            }}</span>
                            <div class="text-xs text-muted-foreground">
                                {{ row.url }}
                            </div>
                        </td>
                        <td class="px-4 py-2 text-muted-foreground">
                            {{ row.type_label }}
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
                        <td class="px-4 py-2 text-muted-foreground">
                            {{ row.updated_at }}
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
                                    title="Ștergi pagina definitiv?"
                                    description="Nu mai poate fi recuperată. Adresa ei devine liberă; linkurile vechi vor da 404 dacă nu adaugi o redirecționare."
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
                            colspan="6"
                            class="px-4 py-8 text-center text-muted-foreground"
                        >
                            {{
                                filters.trashed
                                    ? 'Coșul este gol.'
                                    : 'Nicio pagină găsită.'
                            }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <Pager
            :prev-url="pages.prev_page_url"
            :next-url="pages.next_page_url"
            :current="pages.current_page"
            :last="pages.last_page"
        />
    </div>
</template>
