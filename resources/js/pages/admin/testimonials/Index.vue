<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { useSortable } from '@vueuse/integrations/useSortable';
import { GripVertical, Plus } from '@lucide/vue';
import { computed, onMounted, ref, useTemplateRef, watch } from 'vue';
import ConfirmAction from '@/components/admin/ConfirmAction.vue';
import SelectField from '@/components/admin/fields/SelectField.vue';
import SwitchField from '@/components/admin/fields/SwitchField.vue';
import TextField from '@/components/admin/fields/TextField.vue';
import AssetField from '@/components/admin/media/AssetField.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import {
    destroy,
    index,
    reorder,
    restore,
    store,
    update,
} from '@/routes/admin/testimonials';
import type { Option } from '@/types/content';

type TestimonialRow = {
    id: number;
    author_name: string;
    author_role: string | null;
    company: string | null;
    quote: string;
    rating: number | null;
    avatar_asset_id: number | null;
    client_id: number | null;
    client: string | null;
    project_id: number | null;
    project: string | null;
    is_visible: boolean;
    sort_order: number;
    trashed: boolean;
};

const props = defineProps<{
    testimonials: TestimonialRow[];
    filters: { q: string; trashed: boolean };
    editId: number | null;
    clients: Option[];
    projects: Option[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Testimoniale', href: index() }],
    },
});

const ratings: Option[] = [5, 4, 3, 2, 1].map((n) => ({
    value: String(n),
    label: stars(n),
}));

function stars(n: number): string {
    return '★'.repeat(n) + '☆'.repeat(5 - n);
}

const search = ref(props.filters.q);
const trashed = ref(props.filters.trashed);

let debounce: ReturnType<typeof setTimeout> | undefined;

watch([search, trashed], () => {
    clearTimeout(debounce);
    debounce = setTimeout(() => {
        router.get(
            index.url(),
            {
                q: search.value || undefined,
                trashed: trashed.value ? 1 : undefined,
            },
            { preserveState: true, replace: true },
        );
    }, 300);
});

// The whole list is shown in its site order only without a search.
const canReorder = computed(() => !props.filters.trashed && !props.filters.q);

const rows = ref<TestimonialRow[]>([...props.testimonials]);
watch(
    () => props.testimonials,
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

const open = ref(false);
const editing = ref<TestimonialRow | null>(null);

const form = useForm({
    author_name: '',
    author_role: null as string | null,
    company: null as string | null,
    quote: '',
    rating: null as number | null,
    avatar_asset_id: null as number | null,
    client_id: null as number | null,
    project_id: null as number | null,
    is_visible: true,
    sort_order: 0 as number | null,
});

function openNew(): void {
    editing.value = null;
    form.author_name = '';
    form.author_role = null;
    form.company = null;
    form.quote = '';
    form.rating = null;
    form.avatar_asset_id = null;
    form.client_id = null;
    form.project_id = null;
    form.is_visible = true;
    form.sort_order = 0;
    form.clearErrors();
    open.value = true;
}

function openEdit(testimonial: TestimonialRow): void {
    editing.value = testimonial;
    form.author_name = testimonial.author_name;
    form.author_role = testimonial.author_role;
    form.company = testimonial.company;
    form.quote = testimonial.quote;
    form.rating = testimonial.rating;
    form.avatar_asset_id = testimonial.avatar_asset_id;
    form.client_id = testimonial.client_id;
    form.project_id = testimonial.project_id;
    form.is_visible = testimonial.is_visible;
    form.sort_order = testimonial.sort_order;
    form.clearErrors();
    open.value = true;
}

// The media library links here with ?edit={id}.
onMounted(() => {
    const testimonial = props.testimonials.find(
        (row) => row.id === props.editId,
    );

    if (testimonial && !testimonial.trashed) {
        openEdit(testimonial);
    }
});

/** Drops ?edit= from the address, so a later reload does not reopen it. */
function forgetEditParam(): void {
    if (props.editId === null) {
        return;
    }

    const url = new URL(window.location.href);
    url.searchParams.delete('edit');

    router.replace({
        url: url.pathname + url.search,
        props: (current) => ({ ...current, editId: null }),
        preserveState: true,
        preserveScroll: true,
    });
}

function close(): void {
    open.value = false;
    editing.value = null;
    form.reset();
    form.clearErrors();
    forgetEditParam();
}

function submit(): void {
    const options = { preserveScroll: true, onSuccess: close };

    if (editing.value) {
        form.put(update.url(editing.value.id), options);
    } else {
        form.post(store.url(), options);
    }
}

function remove(testimonial: TestimonialRow): void {
    router.delete(destroy.url(testimonial.id), { preserveScroll: true });
}

function undelete(testimonial: TestimonialRow): void {
    router.post(restore.url(testimonial.id), {}, { preserveScroll: true });
}

function byline(row: TestimonialRow): string {
    return [row.author_role, row.company].filter(Boolean).join(', ');
}
</script>

<template>
    <Head title="Testimoniale" />

    <div class="flex flex-col gap-4 p-4">
        <div class="flex items-center justify-between gap-3">
            <h1 class="text-2xl font-bold tracking-tight">Testimoniale</h1>
            <Button type="button" @click="openNew"
                ><Plus /> Testimonial nou</Button
            >
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <Input
                v-model="search"
                type="search"
                placeholder="Caută autor, firmă sau text…"
                class="max-w-xs"
            />
            <label class="flex items-center gap-2 text-sm">
                <input v-model="trashed" type="checkbox" class="size-4" />
                Coș
            </label>
        </div>

        <p
            v-if="canReorder && rows.length > 1"
            class="text-sm text-muted-foreground"
        >
            Trage rândurile pentru a schimba ordinea în care apar pe site.
        </p>

        <div class="overflow-x-auto rounded-xl border">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left">
                    <tr>
                        <th v-if="canReorder" class="w-8 px-2 py-2"></th>
                        <th class="px-4 py-2">Autor</th>
                        <th class="px-4 py-2">Testimonial</th>
                        <th class="px-4 py-2">Legat de</th>
                        <th class="px-4 py-2"></th>
                    </tr>
                </thead>
                <tbody ref="body">
                    <tr
                        v-for="row in rows"
                        :key="row.id"
                        class="border-t align-top"
                    >
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
                            <div class="flex flex-wrap items-center gap-2">
                                <button
                                    v-if="!row.trashed"
                                    type="button"
                                    class="text-left font-medium hover:underline"
                                    @click="openEdit(row)"
                                >
                                    {{ row.author_name }}
                                </button>
                                <span v-else class="font-medium">{{
                                    row.author_name
                                }}</span>
                                <Badge
                                    v-if="!row.is_visible"
                                    variant="secondary"
                                    >Ascuns</Badge
                                >
                            </div>
                            <div
                                v-if="byline(row)"
                                class="text-xs text-muted-foreground"
                            >
                                {{ byline(row) }}
                            </div>
                        </td>
                        <td class="max-w-md px-4 py-2">
                            <p class="line-clamp-2">„{{ row.quote }}”</p>
                            <div
                                v-if="row.rating"
                                class="text-xs text-amber-500"
                                :aria-label="`${row.rating} din 5 stele`"
                            >
                                {{ stars(row.rating) }}
                            </div>
                        </td>
                        <td class="px-4 py-2 text-muted-foreground">
                            <div v-if="row.client">{{ row.client }}</div>
                            <div v-if="row.project" class="text-xs">
                                {{ row.project }}
                            </div>
                            <span v-if="!row.client && !row.project">—</span>
                        </td>
                        <td class="px-4 py-2 text-right whitespace-nowrap">
                            <Button
                                v-if="row.trashed"
                                type="button"
                                variant="ghost"
                                size="sm"
                                @click="undelete(row)"
                            >
                                Restaurează
                            </Button>
                            <template v-else>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    @click="openEdit(row)"
                                >
                                    Editează
                                </Button>
                                <ConfirmAction
                                    title="Muți testimonialul în coș?"
                                    description="Dispare de pe site. Îl poți restaura din coș."
                                    confirm-label="Mută în coș"
                                    @confirm="remove(row)"
                                >
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        class="text-destructive"
                                    >
                                        Mută în coș
                                    </Button>
                                </ConfirmAction>
                            </template>
                        </td>
                    </tr>
                    <tr v-if="rows.length === 0">
                        <td
                            :colspan="canReorder ? 5 : 4"
                            class="px-4 py-8 text-center text-muted-foreground"
                        >
                            {{
                                filters.trashed
                                    ? 'Coșul este gol.'
                                    : 'Niciun testimonial găsit.'
                            }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <Sheet :open="open" @update:open="(value) => !value && close()">
        <SheetContent class="w-full overflow-y-auto sm:max-w-md">
            <SheetHeader>
                <SheetTitle>
                    {{ editing ? 'Editează testimonialul' : 'Testimonial nou' }}
                </SheetTitle>
                <SheetDescription>
                    {{
                        editing
                            ? editing.author_name
                            : 'Ce spune un client despre colaborarea cu noi.'
                    }}
                </SheetDescription>
            </SheetHeader>

            <form
                :key="editing?.id ?? 'new'"
                class="flex flex-col gap-4 px-4 pb-6"
                @submit.prevent="submit"
            >
                <TextField
                    v-model="form.author_name"
                    label="Autor"
                    :max="150"
                    required
                    :error="form.errors.author_name"
                />
                <TextField
                    v-model="form.author_role"
                    label="Funcție"
                    :max="150"
                    placeholder="ex. Director general"
                    :error="form.errors.author_role"
                />
                <TextField
                    v-model="form.company"
                    label="Firmă"
                    :max="150"
                    :error="form.errors.company"
                />
                <TextField
                    v-model="form.quote"
                    label="Testimonial"
                    multiline
                    :rows="5"
                    :max="2000"
                    required
                    :error="form.errors.quote"
                />
                <SelectField
                    v-model="form.rating"
                    label="Rating"
                    :options="ratings"
                    numeric
                    nullable
                    empty-label="Fără rating"
                    hint="Stelele apar doar pe pagină, nu în rezultatele Google."
                    :error="form.errors.rating"
                />
                <div class="grid gap-1.5">
                    <Label>Fotografie</Label>
                    <AssetField v-model="form.avatar_asset_id" />
                    <InputError :message="form.errors.avatar_asset_id" />
                </div>
                <SelectField
                    v-model="form.client_id"
                    label="Client"
                    :options="clients"
                    numeric
                    nullable
                    :error="form.errors.client_id"
                />
                <SelectField
                    v-model="form.project_id"
                    label="Proiect"
                    :options="projects"
                    numeric
                    nullable
                    :error="form.errors.project_id"
                />
                <SwitchField
                    v-model="form.is_visible"
                    label="Vizibil pe site"
                />
                <TextField
                    v-model="form.sort_order"
                    label="Ordine"
                    type="number"
                    hint="Testimonialele cu număr mai mic apar primele. Poți și trage rândurile în listă."
                    :error="form.errors.sort_order"
                />

                <div class="flex justify-end gap-2">
                    <Button type="button" variant="ghost" @click="close">
                        Renunță
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        {{ editing ? 'Salvează' : 'Adaugă' }}
                    </Button>
                </div>
            </form>
        </SheetContent>
    </Sheet>
</template>
