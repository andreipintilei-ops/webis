<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, ImagePlus, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import BlockEditor from '@/components/admin/blocks/BlockEditor.vue';
import { scoped } from '@/components/admin/blocks/errors';
import ConfirmAction from '@/components/admin/ConfirmAction.vue';
import PublishPanel from '@/components/admin/content/PublishPanel.vue';
import RevisionsPanel from '@/components/admin/content/RevisionsPanel.vue';
import SeoPanel from '@/components/admin/content/SeoPanel.vue';
import SlugInput from '@/components/admin/content/SlugInput.vue';
import ChoiceList from '@/components/admin/fields/ChoiceList.vue';
import ItemList from '@/components/admin/fields/ItemList.vue';
import SelectField from '@/components/admin/fields/SelectField.vue';
import SwitchField from '@/components/admin/fields/SwitchField.vue';
import TextField from '@/components/admin/fields/TextField.vue';
import AssetField from '@/components/admin/media/AssetField.vue';
import MediaPicker from '@/components/admin/media/MediaPicker.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { useUnsavedChanges } from '@/composables/useUnsavedChanges';
import { contentText } from '@/lib/contentText';
import { destroy, index, store, update } from '@/routes/admin/projects';
import type {
    BlockType,
    Choices,
    ContentStatus,
    Option,
    RevisionSummary,
    SeoData,
    StoredBlock,
} from '@/types/content';
import type { Asset } from '@/types/media';

type Metric = { value: string; label: string };

type ProjectPayload = {
    id: number | null;
    title: string;
    slug: string;
    client_id: number | null;
    type: string | null;
    year: number | null;
    url: string | null;
    summary: string | null;
    need: string | null;
    built: string | null;
    /** Comma-separated; the server splits it into a list. */
    stack: string | null;
    cover_asset_id: number | null;
    images: (number | null)[];
    blocks: StoredBlock[];
    metrics: Metric[];
    is_featured: boolean;
    sort_order: number;
    category_ids: number[];
    seo: SeoData;
    public_url: string | null;
    status: ContentStatus;
    published_at: string | null;
    is_live: boolean;
};

const props = defineProps<{
    project: ProjectPayload;
    statuses: Option[];
    types: Option[];
    blockTypes: BlockType[];
    choices: Choices;
    revisions: RevisionSummary[];
    titleSuffix: string;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Portofoliu', href: index() }],
    },
});

const isNew = props.project.id === null;

const form = useForm({
    title: props.project.title,
    slug: props.project.slug,
    client_id: props.project.client_id,
    type: props.project.type,
    year: props.project.year,
    url: props.project.url,
    summary: props.project.summary,
    need: props.project.need,
    built: props.project.built,
    stack: props.project.stack,
    cover_asset_id: props.project.cover_asset_id,
    images: props.project.images,
    blocks: props.project.blocks,
    metrics: props.project.metrics,
    is_featured: props.project.is_featured,
    sort_order: props.project.sort_order,
    category_ids: props.project.category_ids,
    seo: props.project.seo,
    status: props.project.status,
    published_at: props.project.published_at,
});

useUnsavedChanges(() => form.isDirty && !form.processing);

const errors = computed(() => form.errors as Record<string, string>);

const clientOptions = computed<Option[]>(() =>
    props.choices.clients.map((client) => ({
        value: String(client.id),
        label: client.label,
    })),
);

const MAX_IMAGES = 30;

const galleryPickerOpen = ref(false);

function addImages(assets: Asset[]): void {
    const room = MAX_IMAGES - form.images.length;
    const ids = assets
        .map((asset) => asset.id)
        .filter((id) => !form.images.includes(id));

    form.images = [...form.images, ...ids.slice(0, Math.max(room, 0))];
}

function submit(): void {
    const options = {
        preserveScroll: true,
        preserveState: (page: { props: { errors: object } }) =>
            Object.keys(page.props.errors).length > 0,
    };

    if (props.project.id === null) {
        form.post(store.url(), options);
    } else {
        form.put(update.url(props.project.id), options);
    }
}

function remove(): void {
    if (props.project.id !== null) {
        router.delete(destroy.url(props.project.id));
    }
}
</script>

<template>
    <Head :title="isNew ? 'Proiect nou' : project.title" />

    <form class="flex flex-col gap-4 p-4" @submit.prevent="submit">
        <div class="flex items-center gap-3">
            <Button
                as-child
                variant="ghost"
                size="icon"
                aria-label="Înapoi la portofoliu"
            >
                <Link :href="index()"><ArrowLeft /></Link>
            </Button>
            <h1 class="truncate text-2xl font-bold tracking-tight">
                {{ isNew ? 'Proiect nou' : form.title || project.title }}
            </h1>
        </div>

        <div class="grid gap-6 lg:grid-cols-[1fr_20rem]">
            <Tabs default-value="content" class="min-w-0">
                <TabsList>
                    <TabsTrigger value="content">Conținut</TabsTrigger>
                    <TabsTrigger value="blocks">
                        Studiu de caz ({{ form.blocks.length }})
                    </TabsTrigger>
                    <TabsTrigger value="seo">SEO</TabsTrigger>
                </TabsList>

                <TabsContent value="content" class="flex flex-col gap-5 pt-4">
                    <TextField
                        v-model="form.title"
                        label="Titlu"
                        :max="200"
                        :error="form.errors.title"
                        required
                    />
                    <SlugInput
                        v-model="form.slug"
                        prefix="/proiect/"
                        :title="form.title"
                        :original="project.slug"
                        :is-live="project.is_live"
                        :error="form.errors.slug"
                    />
                    <SelectField
                        v-model="form.type"
                        label="Tip"
                        :options="types"
                        nullable
                        empty-label="Alege tipul…"
                        hint="Filtrul de pe /portofoliu și lucrările arătate pe paginile de servicii."
                        :error="form.errors.type"
                    />
                    <TextField
                        v-model="form.summary"
                        label="Rezumat"
                        :max="200"
                        :error="form.errors.summary"
                        hint="O singură frază: ce s-a făcut și pentru cine — apare pe cardul proiectului."
                    />
                    <TextField
                        v-model="form.need"
                        label="De ce avea nevoie clientul"
                        multiline
                        :rows="4"
                        :max="2000"
                        :error="form.errors.need"
                    />
                    <TextField
                        v-model="form.built"
                        label="Ce am construit"
                        multiline
                        :rows="4"
                        :max="2000"
                        :error="form.errors.built"
                    />
                    <TextField
                        v-model="form.stack"
                        label="Tehnologii"
                        placeholder="Laravel, Vue, MySQL"
                        :max="500"
                        :error="form.errors.stack"
                        hint="Separate prin virgulă."
                    />
                    <div class="grid gap-1.5">
                        <Label>Imagine de copertă</Label>
                        <AssetField v-model="form.cover_asset_id" />
                        <InputError :message="form.errors.cover_asset_id" />
                    </div>
                    <ItemList
                        v-model="form.images"
                        label="Galerie"
                        :new-item="() => null"
                        add-label="Adaugă o imagine"
                        :max="MAX_IMAGES"
                        :error="form.errors.images"
                    >
                        <template #default="{ index: i }">
                            <AssetField v-model="form.images[i]" />
                            <InputError :message="errors[`images.${i}`]" />
                        </template>
                    </ItemList>
                    <Button
                        v-if="form.images.length < MAX_IMAGES"
                        type="button"
                        variant="outline"
                        size="sm"
                        class="-mt-3 self-start"
                        @click="galleryPickerOpen = true"
                    >
                        <ImagePlus /> Adaugă mai multe imagini
                    </Button>
                    <MediaPicker
                        v-model:open="galleryPickerOpen"
                        multiple
                        title="Alege imaginile galeriei"
                        @select="addImages"
                    />
                    <div class="grid gap-4 sm:grid-cols-3">
                        <SelectField
                            v-model="form.client_id"
                            label="Client"
                            :options="clientOptions"
                            numeric
                            nullable
                            empty-label="Fără client"
                            :error="form.errors.client_id"
                        />
                        <TextField
                            v-model="form.year"
                            label="An"
                            type="number"
                            :error="form.errors.year"
                        />
                        <TextField
                            v-model="form.url"
                            label="Site-ul live"
                            placeholder="https://…"
                            :error="form.errors.url"
                        />
                    </div>
                    <ItemList
                        v-model="form.metrics"
                        label="Rezultate"
                        :new-item="() => ({ value: '', label: '' })"
                        add-label="Adaugă rezultat"
                        :max="6"
                        :error="form.errors.metrics"
                    >
                        <template #default="{ item, index: i }">
                            <div class="grid gap-3 sm:grid-cols-[8rem_1fr]">
                                <TextField
                                    v-model="item.value"
                                    label="Valoare"
                                    placeholder="+180%"
                                    :max="30"
                                    :error="errors[`metrics.${i}.value`]"
                                    required
                                />
                                <TextField
                                    v-model="item.label"
                                    label="Descriere"
                                    placeholder="comenzi online în primul an"
                                    :max="80"
                                    :error="errors[`metrics.${i}.label`]"
                                    required
                                />
                            </div>
                        </template>
                    </ItemList>
                </TabsContent>

                <TabsContent value="blocks" class="pt-4">
                    <BlockEditor
                        v-model="form.blocks"
                        :block-types="blockTypes"
                        :choices="choices"
                        :errors="errors"
                    />
                </TabsContent>

                <TabsContent value="seo" class="pt-4">
                    <SeoPanel
                        v-model="form.seo"
                        :title="form.title"
                        :fallback-description="form.summary"
                        :path="`/proiect/${form.slug}`"
                        :title-suffix="titleSuffix"
                        :content-text="
                            contentText([
                                form.summary,
                                form.need,
                                form.built,
                                form.blocks,
                            ])
                        "
                        :errors="scoped(errors, 'seo')"
                    />
                </TabsContent>
            </Tabs>

            <aside class="flex flex-col gap-4">
                <PublishPanel
                    v-model:status="form.status"
                    v-model:published-at="form.published_at"
                    :statuses="statuses"
                    :is-live="project.is_live"
                    :url="project.public_url"
                    :processing="form.processing"
                    :is-dirty="form.isDirty"
                    :errors="errors"
                />

                <div class="flex flex-col gap-4 rounded-xl border bg-card p-4">
                    <h2 class="text-sm font-medium">Setări proiect</h2>
                    <ChoiceList
                        v-model="form.category_ids"
                        label="Categorii"
                        :choices="choices.projectCategories"
                        :max="10"
                        :error="form.errors.category_ids"
                    />
                    <SwitchField
                        v-model="form.is_featured"
                        label="Recomandat"
                        hint="Apare în blocurile „Proiecte recomandate”."
                    />
                    <TextField
                        v-model="form.sort_order"
                        label="Ordine"
                        type="number"
                        :error="form.errors.sort_order"
                    />
                </div>

                <RevisionsPanel
                    v-if="revisions.length"
                    :revisions="revisions"
                />

                <ConfirmAction
                    v-if="!isNew"
                    title="Muți proiectul în coș?"
                    description="Dispare de pe site. Îl poți restaura din coș."
                    confirm-label="Mută în coș"
                    @confirm="remove"
                >
                    <Button
                        type="button"
                        variant="ghost"
                        class="self-start text-destructive"
                    >
                        <Trash2 /> Mută în coș
                    </Button>
                </ConfirmAction>
            </aside>
        </div>
    </form>
</template>
