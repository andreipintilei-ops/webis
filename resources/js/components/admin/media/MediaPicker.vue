<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import AssetThumb from '@/components/admin/media/AssetThumb.vue';
import MediaUploader from '@/components/admin/media/MediaUploader.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { api } from '@/lib/adminApi';
import { browse } from '@/routes/admin/media';
import type { Asset, Paginated } from '@/types/media';

/**
 * Choose one image (or several, with `multiple`) from the library, or upload
 * new ones on the spot. Emits the chosen assets; the caller stores their ids.
 */
const props = withDefaults(
    defineProps<{
        multiple?: boolean;
        title?: string;
    }>(),
    { multiple: false, title: 'Alege o imagine' },
);

const open = defineModel<boolean>('open', { default: false });
const emit = defineEmits<{ select: [assets: Asset[]] }>();

const tab = ref('library');
const search = ref('');
const items = ref<Asset[]>([]);
const page = ref(1);
const lastPage = ref(1);
const loading = ref(false);
const selected = ref<Asset[]>([]);

const selectedIds = computed(() => new Set(selected.value.map((a) => a.id)));

async function load(reset = true): Promise<void> {
    loading.value = true;

    try {
        const nextPage = reset ? 1 : page.value + 1;
        const result = await api<Paginated<Asset>>(
            'get',
            browse.url({
                query: { q: search.value || undefined, page: nextPage },
            }),
        );

        items.value = reset ? result.data : [...items.value, ...result.data];
        page.value = result.current_page;
        lastPage.value = result.last_page;
    } finally {
        loading.value = false;
    }
}

watch(open, (isOpen) => {
    if (isOpen) {
        selected.value = [];
        tab.value = 'library';
        void load();
    }
});

let debounce: ReturnType<typeof setTimeout> | undefined;

watch(search, () => {
    clearTimeout(debounce);
    debounce = setTimeout(() => void load(), 300);
});

function toggle(asset: Asset): void {
    if (!props.multiple) {
        selected.value = [asset];

        return;
    }

    selected.value = selectedIds.value.has(asset.id)
        ? selected.value.filter((a) => a.id !== asset.id)
        : [...selected.value, asset];
}

function onUploaded(asset: Asset): void {
    items.value = [asset, ...items.value];

    if (props.multiple) {
        selected.value = [...selected.value, asset];
    } else {
        selected.value = [asset];
    }

    tab.value = 'library';
}

function confirm(): void {
    emit('select', selected.value);
    open.value = false;
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="flex max-h-[90vh] flex-col sm:max-w-4xl">
            <DialogHeader>
                <DialogTitle>{{ title }}</DialogTitle>
            </DialogHeader>

            <Tabs v-model="tab" class="flex min-h-0 flex-1 flex-col">
                <TabsList>
                    <TabsTrigger value="library">Bibliotecă</TabsTrigger>
                    <TabsTrigger value="upload">Încarcă</TabsTrigger>
                </TabsList>

                <TabsContent
                    value="library"
                    class="flex min-h-0 flex-1 flex-col gap-3"
                >
                    <Input
                        v-model="search"
                        type="search"
                        placeholder="Caută…"
                        class="max-w-xs"
                    />
                    <div class="min-h-0 flex-1 overflow-y-auto p-1">
                        <div class="grid grid-cols-3 gap-3 sm:grid-cols-5">
                            <button
                                v-for="asset in items"
                                :key="asset.id"
                                type="button"
                                class="text-left"
                                @click="toggle(asset)"
                                @dblclick="
                                    !multiple && (toggle(asset), confirm())
                                "
                            >
                                <AssetThumb
                                    :asset="asset"
                                    :selected="selectedIds.has(asset.id)"
                                />
                            </button>
                        </div>
                        <p
                            v-if="!loading && items.length === 0"
                            class="py-10 text-center text-sm text-muted-foreground"
                        >
                            Nicio imagine. Încarcă una din fila „Încarcă”.
                        </p>
                        <div
                            v-if="page < lastPage"
                            class="flex justify-center py-3"
                        >
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                :disabled="loading"
                                @click="load(false)"
                            >
                                Mai multe
                            </Button>
                        </div>
                    </div>
                </TabsContent>

                <TabsContent value="upload">
                    <MediaUploader @uploaded="onUploaded" />
                </TabsContent>
            </Tabs>

            <DialogFooter class="items-center gap-2">
                <span
                    v-if="multiple"
                    class="mr-auto text-sm text-muted-foreground"
                >
                    {{ selected.length }} selectate
                </span>
                <Button type="button" variant="outline" @click="open = false">
                    Renunță
                </Button>
                <Button
                    type="button"
                    :disabled="selected.length === 0"
                    @click="confirm"
                >
                    Alege
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
