<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Trash2 } from '@lucide/vue';
import { computed, watch } from 'vue';
import BlockEditor from '@/components/admin/blocks/BlockEditor.vue';
import { scoped } from '@/components/admin/blocks/errors';
import ConfirmAction from '@/components/admin/ConfirmAction.vue';
import PublishPanel from '@/components/admin/content/PublishPanel.vue';
import RevisionsPanel from '@/components/admin/content/RevisionsPanel.vue';
import SeoPanel from '@/components/admin/content/SeoPanel.vue';
import SlugInput from '@/components/admin/content/SlugInput.vue';
import ChoiceList from '@/components/admin/fields/ChoiceList.vue';
import SelectField from '@/components/admin/fields/SelectField.vue';
import TextField from '@/components/admin/fields/TextField.vue';
import AssetField from '@/components/admin/media/AssetField.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { useUnsavedChanges } from '@/composables/useUnsavedChanges';
import { contentText } from '@/lib/contentText';
import { destroy, index, store, update } from '@/routes/admin/pages';
import type {
    BlockType,
    Choices,
    ContentStatus,
    Option,
    RevisionSummary,
    SeoData,
    StoredBlock,
} from '@/types/content';

type PagePayload = {
    id: number | null;
    type: string;
    title: string;
    slug: string;
    excerpt: string | null;
    icon: string | null;
    hero_asset_id: number | null;
    blocks: StoredBlock[];
    details: { price_from: string | null; service_type: string | null };
    sort_order: number;
    project_ids: number[];
    seo: SeoData;
    url: string | null;
    status: ContentStatus;
    published_at: string | null;
    is_live: boolean;
};

const props = defineProps<{
    page: PagePayload;
    types: Option[];
    statuses: Option[];
    blockTypes: BlockType[];
    choices: Choices;
    revisions: RevisionSummary[];
    titleSuffix: string;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Pagini', href: index() }],
    },
});

const isNew = props.page.id === null;

const form = useForm({
    type: props.page.type,
    title: props.page.title,
    slug: props.page.slug,
    excerpt: props.page.excerpt,
    icon: props.page.icon,
    hero_asset_id: props.page.hero_asset_id,
    blocks: props.page.blocks,
    details: props.page.details,
    sort_order: props.page.sort_order,
    project_ids: props.page.project_ids,
    seo: props.page.seo,
    status: props.page.status,
    published_at: props.page.published_at,
});

useUnsavedChanges(() => form.isDirty && !form.processing);

// The home page is served at "/", but still needs a slug; give it one.
watch(
    () => form.type,
    (type) => {
        if (type === 'home' && form.slug === '') {
            form.slug = 'acasa';
        }
    },
    { immediate: true },
);

const hasRelatedWork = computed(() =>
    ['service', 'industry'].includes(form.type),
);

const path = computed(() => (form.type === 'home' ? '/' : `/${form.slug}`));

const errors = computed(() => form.errors as Record<string, string>);

function submit(): void {
    const options = {
        preserveScroll: true,
        // Keep the form (and folded blocks) only when validation failed; a
        // successful save reloads fresh from the server.
        preserveState: (page: { props: { errors: object } }) =>
            Object.keys(page.props.errors).length > 0,
    };

    if (isNew || props.page.id === null) {
        form.post(store.url(), options);
    } else {
        form.put(update.url(props.page.id), options);
    }
}

function remove(): void {
    if (props.page.id !== null) {
        router.delete(destroy.url(props.page.id));
    }
}
</script>

<template>
    <Head :title="isNew ? 'Pagină nouă' : page.title" />

    <form class="flex flex-col gap-4 p-4" @submit.prevent="submit">
        <div class="flex items-center gap-3">
            <Button
                as-child
                variant="ghost"
                size="icon"
                aria-label="Înapoi la pagini"
            >
                <Link :href="index()"><ArrowLeft /></Link>
            </Button>
            <h1 class="truncate text-2xl font-bold tracking-tight">
                {{ isNew ? 'Pagină nouă' : form.title || page.title }}
            </h1>
        </div>

        <div class="grid gap-6 lg:grid-cols-[1fr_20rem]">
            <Tabs default-value="content" class="min-w-0">
                <TabsList>
                    <TabsTrigger value="content">Conținut</TabsTrigger>
                    <TabsTrigger value="blocks">
                        Blocuri ({{ form.blocks.length }})
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
                        v-if="form.type !== 'home'"
                        v-model="form.slug"
                        prefix="/"
                        :title="form.title"
                        :original="page.slug"
                        :is-live="page.is_live"
                        :error="form.errors.slug"
                    />
                    <TextField
                        v-model="form.excerpt"
                        label="Rezumat"
                        multiline
                        :max="500"
                        :error="form.errors.excerpt"
                        hint="Apare pe carduri și în liste; e și descrierea implicită pentru Google."
                    />
                    <div class="grid gap-1.5">
                        <Label>Imagine reprezentativă</Label>
                        <AssetField v-model="form.hero_asset_id" />
                        <p class="text-xs text-muted-foreground">
                            Folosită pe cardurile care trimit la această pagină.
                        </p>
                    </div>
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
                        :fallback-description="form.excerpt"
                        :path="path"
                        :title-suffix="titleSuffix"
                        :content-text="contentText([form.excerpt, form.blocks])"
                        :errors="scoped(errors, 'seo')"
                    />
                </TabsContent>
            </Tabs>

            <aside class="flex flex-col gap-4">
                <PublishPanel
                    v-model:status="form.status"
                    v-model:published-at="form.published_at"
                    :statuses="statuses"
                    :is-live="page.is_live"
                    :url="page.url"
                    :processing="form.processing"
                    :is-dirty="form.isDirty"
                    :errors="errors"
                />

                <div class="flex flex-col gap-4 rounded-xl border bg-card p-4">
                    <h2 class="text-sm font-medium">Setări pagină</h2>
                    <SelectField
                        v-model="form.type"
                        label="Tip"
                        :options="types"
                        :error="form.errors.type"
                    />
                    <template v-if="form.type === 'service'">
                        <TextField
                            v-model="form.details.price_from"
                            label="Preț de pornire"
                            placeholder="ex. de la 2.500 lei"
                            :max="40"
                            :error="errors['details.price_from']"
                        />
                        <TextField
                            v-model="form.details.service_type"
                            label="Tip serviciu (schema.org)"
                            placeholder="ex. Web design"
                            :max="100"
                            :error="errors['details.service_type']"
                        />
                    </template>
                    <ChoiceList
                        v-if="hasRelatedWork"
                        v-model="form.project_ids"
                        label="Proiecte relevante"
                        :choices="choices.projects"
                        :max="24"
                        :error="form.errors.project_ids"
                        hint="Arătate pe pagină — o pagină de industrie are nevoie de lucrări reale."
                    />
                    <TextField
                        v-model="form.icon"
                        label="Iconiță"
                        placeholder="ex. shopping-cart"
                        :error="form.errors.icon"
                        hint="Nume de iconiță Lucide, pentru carduri."
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
                    title="Muți pagina în coș?"
                    description="Dispare de pe site. O poți restaura din coș; adresa ei rămâne rezervată până la ștergerea definitivă."
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
