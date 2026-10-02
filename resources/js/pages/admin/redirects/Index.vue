<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { Download, Plus, Upload } from '@lucide/vue';
import { onMounted, ref, watch } from 'vue';
import ConfirmAction from '@/components/admin/ConfirmAction.vue';
import SelectField from '@/components/admin/fields/SelectField.vue';
import TextField from '@/components/admin/fields/TextField.vue';
import Pager from '@/components/admin/Pager.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import {
    destroy,
    exportMethod,
    importMethod,
    index,
    store,
    update,
} from '@/routes/admin/redirects';
import type { Option } from '@/types/content';
import type { Paginated } from '@/types/media';

type RedirectRow = {
    id: number;
    source_path: string;
    match_type: string;
    target: string | null;
    status_code: number;
    hits: number;
    last_hit_at: string | null;
    notes: string | null;
};

const props = defineProps<{
    redirects: Paginated<RedirectRow>;
    filters: { q: string; code: string };
    codes: Option[];
    matchTypes: Option[];
    prefillSource: string | null;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Redirecționări', href: index() }],
    },
});

const search = ref(props.filters.q);
const code = ref(props.filters.code);

const selectClass =
    'border-input bg-background h-9 rounded-md border px-3 py-1 text-sm shadow-xs outline-none';

let debounce: ReturnType<typeof setTimeout> | undefined;

watch([search, code], () => {
    clearTimeout(debounce);
    debounce = setTimeout(() => {
        router.get(
            index.url(),
            {
                q: search.value || undefined,
                code: code.value || undefined,
            },
            { preserveState: true, replace: true },
        );
    }, 300);
});

function matchTypeLabel(value: string): string {
    return (
        props.matchTypes.find((option) => option.value === value)?.label ??
        value
    );
}

function codeVariant(
    statusCode: number,
): 'default' | 'secondary' | 'destructive' {
    if (statusCode === 410) {
        return 'destructive';
    }

    return statusCode === 301 ? 'default' : 'secondary';
}

// Create / edit sheet

const open = ref(false);
const editing = ref<RedirectRow | null>(null);

const form = useForm({
    source_path: '',
    match_type: 'exact',
    status_code: 301,
    target: null as string | null,
    notes: null as string | null,
});

function openNew(source: string | null = null): void {
    editing.value = null;
    form.source_path = source ?? '';
    form.match_type = props.matchTypes[0]?.value ?? 'exact';
    form.status_code = 301;
    form.target = null;
    form.notes = null;
    form.clearErrors();
    open.value = true;
}

function openEdit(redirect: RedirectRow): void {
    editing.value = redirect;
    form.source_path = redirect.source_path;
    form.match_type = redirect.match_type;
    form.status_code = redirect.status_code;
    form.target = redirect.target;
    form.notes = redirect.notes;
    form.clearErrors();
    open.value = true;
}

function close(): void {
    open.value = false;
    editing.value = null;
    form.reset();
    form.clearErrors();
}

function submit(): void {
    // Keep the component mounted so a ?source= prefill does not reopen the
    // sheet after saving.
    const options = {
        preserveScroll: true,
        preserveState: true,
        onSuccess: close,
    };

    if (editing.value) {
        form.put(update.url(editing.value.id), options);
    } else {
        form.post(store.url(), options);
    }
}

function remove(redirect: RedirectRow): void {
    router.delete(destroy.url(redirect.id), { preserveScroll: true });
}

onMounted(() => {
    if (props.prefillSource) {
        openNew(props.prefillSource);
    }
});

// CSV import

const importOpen = ref(false);
const importForm = useForm({ file: null as File | null });

function onFileChange(event: Event): void {
    importForm.file = (event.target as HTMLInputElement).files?.[0] ?? null;
}

function openImport(): void {
    importForm.reset();
    importForm.clearErrors();
    importOpen.value = true;
}

function closeImport(): void {
    importOpen.value = false;
    importForm.reset();
    importForm.clearErrors();
}

function submitImport(): void {
    importForm.post(importMethod.url(), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: closeImport,
    });
}
</script>

<template>
    <Head title="Redirecționări" />

    <div class="flex flex-col gap-4 p-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-2xl font-bold tracking-tight">Redirecționări</h1>
            <div class="flex flex-wrap items-center gap-2">
                <Button type="button" variant="outline" @click="openImport">
                    <Upload /> Importă CSV
                </Button>
                <Button as-child variant="outline">
                    <a :href="exportMethod.url()"><Download /> Exportă CSV</a>
                </Button>
                <Button type="button" @click="openNew()">
                    <Plus /> Redirecționare nouă
                </Button>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <Input
                v-model="search"
                type="search"
                placeholder="Caută sursă sau destinație…"
                class="max-w-xs"
            />
            <select v-model="code" :class="selectClass">
                <option value="">Toate codurile</option>
                <option v-for="c in codes" :key="c.value" :value="c.value">
                    {{ c.label }}
                </option>
            </select>
        </div>

        <div class="overflow-x-auto rounded-xl border">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left">
                    <tr>
                        <th class="px-4 py-2">Sursă</th>
                        <th class="px-4 py-2">Destinație</th>
                        <th class="px-4 py-2">Cod</th>
                        <th class="px-4 py-2">Tip</th>
                        <th class="px-4 py-2">Accesări</th>
                        <th class="px-4 py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="redirect in redirects.data"
                        :key="redirect.id"
                        class="border-t"
                    >
                        <td class="px-4 py-2">
                            <button
                                type="button"
                                class="text-left font-mono text-xs break-all hover:underline"
                                @click="openEdit(redirect)"
                            >
                                {{ redirect.source_path }}
                            </button>
                            <div
                                v-if="redirect.notes"
                                class="text-xs text-muted-foreground"
                            >
                                {{ redirect.notes }}
                            </div>
                        </td>
                        <td class="px-4 py-2">
                            <Badge
                                v-if="redirect.status_code === 410"
                                variant="destructive"
                                >410 Șters</Badge
                            >
                            <span
                                v-else
                                class="font-mono text-xs break-all text-muted-foreground"
                                >→ {{ redirect.target }}</span
                            >
                        </td>
                        <td class="px-4 py-2">
                            <Badge :variant="codeVariant(redirect.status_code)">
                                {{ redirect.status_code }}
                            </Badge>
                        </td>
                        <td class="px-4 py-2 text-muted-foreground">
                            {{ matchTypeLabel(redirect.match_type) }}
                        </td>
                        <td class="px-4 py-2 tabular-nums">
                            {{ redirect.hits }}
                            <div
                                v-if="redirect.last_hit_at"
                                class="text-xs text-muted-foreground"
                            >
                                {{ redirect.last_hit_at }}
                            </div>
                        </td>
                        <td class="px-4 py-2 text-right whitespace-nowrap">
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                @click="openEdit(redirect)"
                            >
                                Editează
                            </Button>
                            <ConfirmAction
                                title="Ștergi redirecționarea?"
                                description="Adresa veche va da 404 dacă nu mai există altă regulă pentru ea."
                                @confirm="remove(redirect)"
                            >
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    class="text-destructive"
                                >
                                    Șterge
                                </Button>
                            </ConfirmAction>
                        </td>
                    </tr>
                    <tr v-if="redirects.data.length === 0">
                        <td
                            colspan="6"
                            class="px-4 py-8 text-center text-muted-foreground"
                        >
                            Nicio redirecționare găsită.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <Pager
            :prev-url="redirects.prev_page_url"
            :next-url="redirects.next_page_url"
            :current="redirects.current_page"
            :last="redirects.last_page"
        />
    </div>

    <Sheet :open="open" @update:open="(value) => !value && close()">
        <SheetContent class="w-full overflow-y-auto sm:max-w-md">
            <SheetHeader>
                <SheetTitle>
                    {{
                        editing
                            ? 'Editează redirecționarea'
                            : 'Redirecționare nouă'
                    }}
                </SheetTitle>
                <SheetDescription>
                    Trimite vizitatorii și motoarele de căutare de la o adresă
                    veche la cea nouă.
                </SheetDescription>
            </SheetHeader>

            <form
                :key="editing?.id ?? 'new'"
                class="flex flex-col gap-4 px-4 pb-6"
                @submit.prevent="submit"
            >
                <TextField
                    v-model="form.source_path"
                    label="Sursă"
                    placeholder="/proiect/nume-vechi"
                    hint="Calea veche, fără domeniu. Se normalizează automat."
                    :max="512"
                    required
                    :error="form.errors.source_path"
                />
                <SelectField
                    v-model="form.match_type"
                    label="Tip potrivire"
                    :options="matchTypes"
                    :hint="
                        form.match_type === 'prefix'
                            ? 'Prefix: orice adresă care începe cu sursa'
                            : undefined
                    "
                    :error="form.errors.match_type"
                />
                <SelectField
                    v-model="form.status_code"
                    label="Cod"
                    :options="codes"
                    numeric
                    :error="form.errors.status_code"
                />
                <TextField
                    v-if="form.status_code !== 410"
                    v-model="form.target"
                    label="Destinație"
                    placeholder="/noua-adresa sau https://…"
                    required
                    :error="form.errors.target"
                />
                <TextField
                    v-model="form.notes"
                    label="Notițe"
                    :max="255"
                    :error="form.errors.notes"
                />

                <div class="flex justify-end gap-2">
                    <Button type="button" variant="ghost" @click="close">
                        Renunță
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        {{ editing ? 'Salvează' : 'Creează' }}
                    </Button>
                </div>
            </form>
        </SheetContent>
    </Sheet>

    <Dialog
        :open="importOpen"
        @update:open="(value) => !value && closeImport()"
    >
        <DialogContent>
            <form class="flex flex-col gap-4" @submit.prevent="submitImport">
                <DialogHeader>
                    <DialogTitle>Importă redirecționări</DialogTitle>
                    <DialogDescription>
                        Un fișier CSV cu câte o redirecționare pe rând.
                    </DialogDescription>
                </DialogHeader>

                <div class="flex flex-col gap-2 text-sm">
                    <p>
                        Coloane:
                        <code class="rounded bg-muted px-1 font-mono text-xs"
                            >sursa;destinatie;cod;tip</code
                        >
                    </p>
                    <ul
                        class="list-disc pl-5 text-muted-foreground [&>li]:mt-1"
                    >
                        <li>
                            <code class="font-mono text-xs">cod</code> și
                            <code class="font-mono text-xs">tip</code> sunt
                            opționale: implicit 301 și
                            <code class="font-mono text-xs">exact</code>.
                        </li>
                        <li>Primul rând poate fi un antet.</li>
                        <li>
                            Sursele care există deja sunt actualizate, nu
                            dublate.
                        </li>
                        <li>
                            Același format ca la „Exportă CSV”; se acceptă și
                            virgula ca separator.
                        </li>
                    </ul>
                </div>

                <div class="grid gap-1.5">
                    <input
                        type="file"
                        accept=".csv,.txt,text/csv,text/plain"
                        class="text-sm file:mr-3 file:rounded-md file:border file:border-input file:bg-background file:px-3 file:py-1 file:text-sm"
                        @change="onFileChange"
                    />
                    <InputError :message="importForm.errors.file" />
                </div>

                <DialogFooter>
                    <Button type="button" variant="ghost" @click="closeImport">
                        Renunță
                    </Button>
                    <Button
                        type="submit"
                        :disabled="importForm.processing || !importForm.file"
                    >
                        Importă
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
