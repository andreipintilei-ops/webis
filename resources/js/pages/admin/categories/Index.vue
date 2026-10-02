<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import { computed, ref } from 'vue';
import ConfirmAction from '@/components/admin/ConfirmAction.vue';
import SlugInput from '@/components/admin/content/SlugInput.vue';
import SwitchField from '@/components/admin/fields/SwitchField.vue';
import TextField from '@/components/admin/fields/TextField.vue';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import type { SeoData } from '@/types/content';

type CategoryRow = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    sort_order: number;
    seo: SeoData;
    url: string;
    items_count: number;
    update_url: string;
    destroy_url: string;
};

const props = defineProps<{
    title: string;
    itemsLabel: string;
    urlPrefix: string;
    storeUrl: string;
    categories: CategoryRow[];
}>();

// Shared by portfolio and blog categories, so the heading comes from props;
// the layout options cannot read them.
defineOptions({
    layout: {
        breadcrumbs: [],
    },
});

const open = ref(false);
const editing = ref<CategoryRow | null>(null);

const form = useForm({
    name: '',
    slug: '',
    description: null as string | null,
    sort_order: 0 as number | null,
    seo: {
        title: null as string | null,
        description: null as string | null,
        noindex: false,
    },
});

const errors = computed(() => form.errors as Record<string, string>);

function openNew(): void {
    editing.value = null;
    form.name = '';
    form.slug = '';
    form.description = null;
    form.sort_order = 0;
    form.seo = { title: null, description: null, noindex: false };
    form.clearErrors();
    open.value = true;
}

function openEdit(category: CategoryRow): void {
    editing.value = category;
    form.name = category.name;
    form.slug = category.slug;
    form.description = category.description;
    form.sort_order = category.sort_order;
    form.seo = {
        title: category.seo.title,
        description: category.seo.description,
        noindex: category.seo.noindex,
    };
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
    const options = { preserveScroll: true, onSuccess: close };

    if (editing.value) {
        form.put(editing.value.update_url, options);
    } else {
        form.post(props.storeUrl, options);
    }
}

function remove(category: CategoryRow): void {
    router.delete(category.destroy_url, { preserveScroll: true });
}
</script>

<template>
    <Head :title="title" />

    <div class="flex flex-col gap-4 p-4">
        <div class="flex items-center justify-between gap-3">
            <h1 class="text-2xl font-bold tracking-tight">{{ title }}</h1>
            <Button type="button" @click="openNew"
                ><Plus /> Categorie nouă</Button
            >
        </div>

        <div class="overflow-x-auto rounded-xl border">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left">
                    <tr>
                        <th class="px-4 py-2">Nume</th>
                        <th class="px-4 py-2">Adresă</th>
                        <th class="px-4 py-2">Conținut</th>
                        <th class="px-4 py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="category in categories"
                        :key="category.id"
                        class="border-t"
                    >
                        <td class="px-4 py-2">
                            <button
                                type="button"
                                class="font-medium hover:underline"
                                @click="openEdit(category)"
                            >
                                {{ category.name }}
                            </button>
                        </td>
                        <td class="px-4 py-2 text-muted-foreground">
                            {{ category.url }}
                        </td>
                        <td class="px-4 py-2 text-muted-foreground">
                            {{ category.items_count }} {{ itemsLabel }}
                        </td>
                        <td class="px-4 py-2 text-right whitespace-nowrap">
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                @click="openEdit(category)"
                            >
                                Editează
                            </Button>
                            <ConfirmAction
                                title="Ștergi categoria?"
                                description="Elementele din ea rămân, doar nu mai sunt în această categorie. Adresa categoriei va da 404 dacă nu adaugi o redirecționare."
                                @confirm="remove(category)"
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
                    <tr v-if="categories.length === 0">
                        <td
                            colspan="4"
                            class="px-4 py-8 text-center text-muted-foreground"
                        >
                            Nicio categorie încă.
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
                    {{ editing ? 'Editează categoria' : 'Categorie nouă' }}
                </SheetTitle>
                <SheetDescription>
                    {{ editing ? editing.url : title }}
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
                    :max="100"
                    required
                    :error="form.errors.name"
                />
                <SlugInput
                    v-model="form.slug"
                    :prefix="urlPrefix"
                    :title="form.name"
                    :original="editing?.slug ?? ''"
                    :is-live="editing !== null"
                    :error="form.errors.slug"
                />
                <TextField
                    v-model="form.description"
                    label="Descriere"
                    multiline
                    :error="form.errors.description"
                />
                <TextField
                    v-model="form.sort_order"
                    label="Ordine"
                    type="number"
                    hint="Categoriile cu număr mai mic apar primele."
                    :error="form.errors.sort_order"
                />

                <div class="flex flex-col gap-4 border-t pt-4">
                    <h3 class="text-sm font-semibold">SEO</h3>
                    <TextField
                        v-model="form.seo.title"
                        label="Titlu SEO"
                        :max="120"
                        :placeholder="form.name"
                        :error="errors['seo.title']"
                    />
                    <TextField
                        v-model="form.seo.description"
                        label="Descriere SEO"
                        multiline
                        :max="320"
                        :error="errors['seo.description']"
                    />
                    <SwitchField
                        v-model="form.seo.noindex"
                        label="Ascunde de motoarele de căutare (noindex)"
                    />
                </div>

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
</template>
