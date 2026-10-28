<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import AssetDetails from '@/components/admin/media/AssetDetails.vue';
import AssetThumb from '@/components/admin/media/AssetThumb.vue';
import MediaUploader from '@/components/admin/media/MediaUploader.vue';
import Pager from '@/components/admin/Pager.vue';
import { Input } from '@/components/ui/input';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { index } from '@/routes/admin/media';
import type { Asset, Paginated } from '@/types/media';

const props = defineProps<{
    assets: Paginated<Asset>;
    filters: { q: string; type: string; unused: boolean };
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Media', href: index() }],
    },
});

const search = ref(props.filters.q);
const type = ref(props.filters.type);
const unused = ref(props.filters.unused);
const selectedId = ref<number | null>(null);

const selectClass =
    'border-input bg-background h-9 rounded-md border px-3 py-1 text-sm shadow-xs outline-none';

let debounce: ReturnType<typeof setTimeout> | undefined;

watch([search, type, unused], () => {
    clearTimeout(debounce);
    debounce = setTimeout(() => {
        router.get(
            index.url(),
            {
                q: search.value || undefined,
                type: type.value || undefined,
                unused: unused.value ? 1 : undefined,
            },
            { preserveState: true, replace: true },
        );
    }, 300);
});

function reload(): void {
    router.reload({ only: ['assets'] });
}

function onDeleted(): void {
    selectedId.value = null;
    reload();
}
</script>

<template>
    <Head title="Media" />

    <div class="flex flex-col gap-4 p-4">
        <h1 class="text-2xl font-bold tracking-tight">Media</h1>

        <MediaUploader @uploaded="reload" />

        <div class="flex flex-wrap items-center gap-3">
            <Input
                v-model="search"
                type="search"
                placeholder="Caută după titlu, alt sau nume fișier…"
                class="max-w-xs"
            />
            <select v-model="type" :class="selectClass">
                <option value="">Toate tipurile</option>
                <option value="raster">Fotografii și imagini</option>
                <option value="svg">SVG (logo-uri, iconițe)</option>
            </select>
            <label class="flex items-center gap-2 text-sm">
                <input v-model="unused" type="checkbox" class="size-4" />
                Doar nefolosite
            </label>
            <span class="ml-auto text-sm text-muted-foreground">
                {{ assets.total }} fișiere
            </span>
        </div>

        <div
            v-if="assets.data.length"
            class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-6"
        >
            <button
                v-for="asset in assets.data"
                :key="asset.id"
                type="button"
                class="text-left"
                @click="selectedId = asset.id"
            >
                <AssetThumb
                    :asset="asset"
                    :selected="selectedId === asset.id"
                />
            </button>
        </div>
        <p v-else class="py-12 text-center text-muted-foreground">
            Nicio imagine găsită.
        </p>

        <Pager
            :prev-url="assets.prev_page_url"
            :next-url="assets.next_page_url"
            :current="assets.current_page"
            :last="assets.last_page"
        />
    </div>

    <Sheet
        :open="selectedId !== null"
        @update:open="(open) => !open && (selectedId = null)"
    >
        <SheetContent class="w-full overflow-y-auto sm:max-w-md">
            <SheetHeader>
                <SheetTitle>Detalii imagine</SheetTitle>
            </SheetHeader>
            <div class="px-4 pb-6">
                <AssetDetails
                    v-if="selectedId !== null"
                    :asset-id="selectedId"
                    @updated="reload"
                    @deleted="onDeleted"
                />
            </div>
        </SheetContent>
    </Sheet>
</template>
