<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { useSortable } from '@vueuse/integrations/useSortable';
import { GripVertical, ImageOff, Plus } from '@lucide/vue';
import { computed, onMounted, ref, useTemplateRef, watch } from 'vue';
import ConfirmAction from '@/components/admin/ConfirmAction.vue';
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
} from '@/routes/admin/clients';

type ClientRow = {
    id: number;
    name: string;
    logo_asset_id: number | null;
    logo_url: string | null;
    sector: string | null;
    url: string | null;
    is_institution: boolean;
    show_in_logos: boolean;
    sort_order: number;
    projects_count: number;
    testimonials_count: number;
    trashed: boolean;
};

const props = defineProps<{
    clients: ClientRow[];
    filters: { q: string; trashed: boolean };
    editId: number | null;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Clienți', href: index() }],
    },
});

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

const rows = ref<ClientRow[]>([...props.clients]);
watch(
    () => props.clients,
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
const editing = ref<ClientRow | null>(null);

const form = useForm({
    name: '',
    logo_asset_id: null as number | null,
    sector: null as string | null,
    url: null as string | null,
    is_institution: false,
    show_in_logos: true,
    sort_order: 0 as number | null,
});

function openNew(): void {
    editing.value = null;
    form.name = '';
    form.logo_asset_id = null;
    form.sector = null;
    form.url = null;
    form.is_institution = false;
    form.show_in_logos = true;
    form.sort_order = 0;
    form.clearErrors();
    open.value = true;
}

function openEdit(client: ClientRow): void {
    editing.value = client;
    form.name = client.name;
    form.logo_asset_id = client.logo_asset_id;
    form.sector = client.sector;
    form.url = client.url;
    form.is_institution = client.is_institution;
    form.show_in_logos = client.show_in_logos;
    form.sort_order = client.sort_order;
    form.clearErrors();
    open.value = true;
}

// The media library links here with ?edit={id}.
onMounted(() => {
    const client = props.clients.find((row) => row.id === props.editId);

    if (client && !client.trashed) {
        openEdit(client);
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

function remove(client: ClientRow): void {
    router.delete(destroy.url(client.id), { preserveScroll: true });
}

function undelete(client: ClientRow): void {
    router.post(restore.url(client.id), {}, { preserveScroll: true });
}
</script>

<template>
    <Head title="Clienți" />

    <div class="flex flex-col gap-4 p-4">
        <div class="flex items-center justify-between gap-3">
            <h1 class="text-2xl font-bold tracking-tight">Clienți</h1>
            <Button type="button" @click="openNew"><Plus /> Client nou</Button>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <Input
                v-model="search"
                type="search"
                placeholder="Caută după nume…"
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
                        <th class="w-14 px-2 py-2"></th>
                        <th class="px-4 py-2">Nume</th>
                        <th class="px-4 py-2">Folosit în</th>
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
                        <td class="px-2 py-2">
                            <div
                                class="flex size-10 items-center justify-center overflow-hidden rounded-md border bg-muted/40"
                            >
                                <img
                                    v-if="row.logo_url"
                                    :src="row.logo_url"
                                    alt=""
                                    class="size-full object-contain"
                                />
                                <ImageOff
                                    v-else
                                    class="size-4 text-muted-foreground"
                                />
                            </div>
                        </td>
                        <td class="px-4 py-2">
                            <div class="flex flex-wrap items-center gap-2">
                                <button
                                    v-if="!row.trashed"
                                    type="button"
                                    class="font-medium hover:underline"
                                    @click="openEdit(row)"
                                >
                                    {{ row.name }}
                                </button>
                                <span v-else class="font-medium">{{
                                    row.name
                                }}</span>
                                <Badge
                                    v-if="row.is_institution"
                                    variant="secondary"
                                    >Instituție</Badge
                                >
                                <Badge
                                    v-if="!row.show_in_logos"
                                    variant="outline"
                                    >Fără logo în bandă</Badge
                                >
                            </div>
                            <div
                                v-if="row.sector"
                                class="text-xs text-muted-foreground"
                            >
                                {{ row.sector }}
                            </div>
                        </td>
                        <td
                            class="px-4 py-2 whitespace-nowrap text-muted-foreground"
                        >
                            {{ row.projects_count }}
                            {{
                                row.projects_count === 1
                                    ? 'proiect'
                                    : 'proiecte'
                            }}
                            ·
                            {{ row.testimonials_count }}
                            {{
                                row.testimonials_count === 1
                                    ? 'testimonial'
                                    : 'testimoniale'
                            }}
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
                                    title="Muți clientul în coș?"
                                    description="Dispare de pe site. Proiectele și testimonialele lui rămân legate de el; îl poți restaura din coș."
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
                                    : 'Niciun client găsit.'
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
                    {{ editing ? 'Editează clientul' : 'Client nou' }}
                </SheetTitle>
                <SheetDescription>
                    {{
                        editing
                            ? editing.name
                            : 'Apare în banda de logo-uri și poate fi legat de proiecte și testimoniale.'
                    }}
                </SheetDescription>
            </SheetHeader>

            <form
                :key="editing?.id ?? 'new'"
                class="flex flex-col gap-4 px-4 pb-6"
                @submit.prevent="submit"
            >
                <TextField
                    v-model="form.name"
                    label="Nume"
                    :max="150"
                    required
                    :error="form.errors.name"
                />
                <div class="grid gap-1.5">
                    <Label>Logo</Label>
                    <AssetField v-model="form.logo_asset_id" />
                    <InputError :message="form.errors.logo_asset_id" />
                </div>
                <TextField
                    v-model="form.sector"
                    label="Domeniu"
                    :max="100"
                    placeholder="ex. Sănătate"
                    :error="form.errors.sector"
                />
                <TextField
                    v-model="form.url"
                    label="Site"
                    type="url"
                    placeholder="https://…"
                    :error="form.errors.url"
                />
                <SwitchField
                    v-model="form.is_institution"
                    label="Instituție publică"
                    hint="Apare în blocul „Clienți notabili”."
                />
                <SwitchField
                    v-model="form.show_in_logos"
                    label="Afișează în banda de logo-uri"
                />
                <TextField
                    v-model="form.sort_order"
                    label="Ordine"
                    type="number"
                    hint="Clienții cu număr mai mic apar primii. Poți și trage rândurile în listă."
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
