<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import { ref, watch } from 'vue';
import ConfirmAction from '@/components/admin/ConfirmAction.vue';
import Pager from '@/components/admin/Pager.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { index as categoriesIndex } from '@/routes/admin/post-categories';
import {
    create,
    edit,
    forceDestroy,
    index,
    restore,
} from '@/routes/admin/posts';
import type { Option } from '@/types/content';
import type { Paginated } from '@/types/media';

type PostRow = {
    id: number;
    title: string;
    url: string;
    category: string | null;
    author: string | null;
    reading_minutes: number | null;
    status: string;
    status_label: string;
    is_live: boolean;
    published_at: string | null;
    trashed: boolean;
};

const props = defineProps<{
    posts: Paginated<PostRow>;
    filters: { q: string; status: string; category: string; trashed: boolean };
    statuses: Option[];
    categories: Option[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Blog', href: index() }],
    },
});

const search = ref(props.filters.q);
const status = ref(props.filters.status);
const category = ref(props.filters.category);
const trashed = ref(props.filters.trashed);

const selectClass =
    'border-input bg-background h-9 rounded-md border px-3 py-1 text-sm shadow-xs outline-none';

let debounce: ReturnType<typeof setTimeout> | undefined;

watch([search, status, category, trashed], () => {
    clearTimeout(debounce);
    debounce = setTimeout(() => {
        router.get(
            index.url(),
            {
                q: search.value || undefined,
                status: status.value || undefined,
                category: category.value || undefined,
                trashed: trashed.value ? 1 : undefined,
            },
            { preserveState: true, replace: true },
        );
    }, 300);
});
</script>

<template>
    <Head title="Blog" />

    <div class="flex flex-col gap-4 p-4">
        <div class="flex items-center justify-between gap-3">
            <h1 class="text-2xl font-bold tracking-tight">Blog</h1>
            <div class="flex items-center gap-2">
                <Button variant="outline" as-child>
                    <Link :href="categoriesIndex()">Categorii</Link>
                </Button>
                <Button as-child>
                    <Link :href="create()"><Plus /> Articol nou</Link>
                </Button>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <Input
                v-model="search"
                type="search"
                placeholder="Caută titlu sau adresă…"
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
                <input v-model="trashed" type="checkbox" class="size-4" />
                Coș
            </label>
        </div>

        <div class="overflow-x-auto rounded-xl border">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left">
                    <tr>
                        <th class="px-4 py-2">Titlu</th>
                        <th class="px-4 py-2">Categorie</th>
                        <th class="px-4 py-2">Autor</th>
                        <th class="px-4 py-2">Stare</th>
                        <th class="px-4 py-2">Publicat</th>
                        <th class="px-4 py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="row in posts.data"
                        :key="row.id"
                        class="border-t"
                    >
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
                            {{ row.category ?? '—' }}
                        </td>
                        <td class="px-4 py-2 text-muted-foreground">
                            {{ row.author ?? '—' }}
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
                            {{ row.published_at ?? '—' }}
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
                                    title="Ștergi articolul definitiv?"
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
                    <tr v-if="posts.data.length === 0">
                        <td
                            colspan="6"
                            class="px-4 py-8 text-center text-muted-foreground"
                        >
                            {{
                                filters.trashed
                                    ? 'Coșul este gol.'
                                    : 'Niciun articol găsit.'
                            }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <Pager
            :prev-url="posts.prev_page_url"
            :next-url="posts.next_page_url"
            :current="posts.current_page"
            :last="posts.last_page"
        />
    </div>
</template>
