<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Trash2 } from '@lucide/vue';
import { computed } from 'vue';
import { scoped } from '@/components/admin/blocks/errors';
import ConfirmAction from '@/components/admin/ConfirmAction.vue';
import PublishPanel from '@/components/admin/content/PublishPanel.vue';
import RevisionsPanel from '@/components/admin/content/RevisionsPanel.vue';
import SeoPanel from '@/components/admin/content/SeoPanel.vue';
import SlugInput from '@/components/admin/content/SlugInput.vue';
import SelectField from '@/components/admin/fields/SelectField.vue';
import SwitchField from '@/components/admin/fields/SwitchField.vue';
import TextField from '@/components/admin/fields/TextField.vue';
import AssetField from '@/components/admin/media/AssetField.vue';
import RichTextEditor from '@/components/admin/rich-text/RichTextEditor.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { useUnsavedChanges } from '@/composables/useUnsavedChanges';
import { contentText } from '@/lib/contentText';
import { destroy, index, store, update } from '@/routes/admin/posts';
import type {
    Choices,
    ContentStatus,
    Option,
    RevisionSummary,
    SeoData,
    TiptapDoc,
} from '@/types/content';

type PostPayload = {
    id: number | null;
    title: string;
    slug: string;
    excerpt: string | null;
    body: TiptapDoc;
    cover_asset_id: number | null;
    post_category_id: number | null;
    author_id: number | null;
    cta_page_id: number | null;
    is_featured: boolean;
    reading_minutes: number;
    seo: SeoData;
    public_url: string | null;
    status: ContentStatus;
    published_at: string | null;
    is_live: boolean;
};

const props = defineProps<{
    post: PostPayload;
    statuses: Option[];
    choices: Choices;
    authors: Option[];
    revisions: RevisionSummary[];
    titleSuffix: string;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Blog', href: index() }],
    },
});

const isNew = props.post.id === null;

const form = useForm({
    title: props.post.title,
    slug: props.post.slug,
    excerpt: props.post.excerpt,
    body: props.post.body,
    cover_asset_id: props.post.cover_asset_id,
    post_category_id: props.post.post_category_id,
    author_id: props.post.author_id,
    cta_page_id: props.post.cta_page_id,
    is_featured: props.post.is_featured,
    seo: props.post.seo,
    status: props.post.status,
    published_at: props.post.published_at,
});

useUnsavedChanges(() => form.isDirty && !form.processing);

const errors = computed(() => form.errors as Record<string, string>);

const toOptions = (choices: { id: number; label: string }[]): Option[] =>
    choices.map((choice) => ({
        value: String(choice.id),
        label: choice.label,
    }));

function submit(): void {
    const options = {
        preserveScroll: true,
        preserveState: (page: { props: { errors: object } }) =>
            Object.keys(page.props.errors).length > 0,
    };

    if (props.post.id === null) {
        form.post(store.url(), options);
    } else {
        form.put(update.url(props.post.id), options);
    }
}

function remove(): void {
    if (props.post.id !== null) {
        router.delete(destroy.url(props.post.id));
    }
}
</script>

<template>
    <Head :title="isNew ? 'Articol nou' : post.title" />

    <form class="flex flex-col gap-4 p-4" @submit.prevent="submit">
        <div class="flex items-center gap-3">
            <Button
                as-child
                variant="ghost"
                size="icon"
                aria-label="Înapoi la blog"
            >
                <Link :href="index()"><ArrowLeft /></Link>
            </Button>
            <h1 class="truncate text-2xl font-bold tracking-tight">
                {{ isNew ? 'Articol nou' : form.title || post.title }}
            </h1>
        </div>

        <div class="grid gap-6 lg:grid-cols-[1fr_20rem]">
            <Tabs default-value="content" class="min-w-0">
                <TabsList>
                    <TabsTrigger value="content">Conținut</TabsTrigger>
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
                        prefix="/blog/"
                        :title="form.title"
                        :original="post.slug"
                        :is-live="post.is_live"
                        :error="form.errors.slug"
                    />
                    <TextField
                        v-model="form.excerpt"
                        label="Rezumat"
                        multiline
                        :max="500"
                        :error="form.errors.excerpt"
                        hint="Apare în lista de articole; e și descrierea implicită pentru Google."
                    />
                    <div class="grid gap-1.5">
                        <Label>Conținut</Label>
                        <RichTextEditor
                            v-model="form.body"
                            :pages="choices.pages"
                            placeholder="Începe articolul…"
                        />
                        <p class="text-xs text-muted-foreground">
                            Titlurile de nivel 2 și 3 formează cuprinsul
                            articolului.
                        </p>
                        <InputError :message="form.errors.body" />
                    </div>
                </TabsContent>

                <TabsContent value="seo" class="pt-4">
                    <SeoPanel
                        v-model="form.seo"
                        :title="form.title"
                        :fallback-description="form.excerpt"
                        :path="`/blog/${form.slug}`"
                        :title-suffix="titleSuffix"
                        :content-text="contentText([form.excerpt, form.body])"
                        :errors="scoped(errors, 'seo')"
                    />
                </TabsContent>
            </Tabs>

            <aside class="flex flex-col gap-4">
                <PublishPanel
                    v-model:status="form.status"
                    v-model:published-at="form.published_at"
                    :statuses="statuses"
                    :is-live="post.is_live"
                    :url="post.public_url"
                    :processing="form.processing"
                    :is-dirty="form.isDirty"
                    :errors="errors"
                />

                <div class="flex flex-col gap-4 rounded-xl border bg-card p-4">
                    <h2 class="text-sm font-medium">Setări articol</h2>
                    <div class="grid gap-1.5">
                        <Label>Imagine de copertă</Label>
                        <AssetField v-model="form.cover_asset_id" />
                    </div>
                    <SelectField
                        v-model="form.post_category_id"
                        label="Categorie"
                        :options="toOptions(choices.postCategories)"
                        numeric
                        nullable
                        empty-label="Fără categorie"
                        :error="form.errors.post_category_id"
                    />
                    <SelectField
                        v-model="form.author_id"
                        label="Autor"
                        :options="authors"
                        numeric
                        nullable
                        empty-label="Echipa Webis"
                        :error="form.errors.author_id"
                    />
                    <SelectField
                        v-model="form.cta_page_id"
                        label="Serviciu promovat la final"
                        :options="toOptions(choices.servicePages)"
                        numeric
                        nullable
                        empty-label="Fără"
                        :error="form.errors.cta_page_id"
                        hint="Articolul se încheie cu un îndemn spre această pagină."
                    />
                    <SwitchField
                        v-model="form.is_featured"
                        label="Recomandat"
                    />
                    <p
                        v-if="post.reading_minutes"
                        class="text-xs text-muted-foreground"
                    >
                        Timp de citire: {{ post.reading_minutes }} min
                    </p>
                </div>

                <RevisionsPanel
                    v-if="revisions.length"
                    :revisions="revisions"
                />

                <ConfirmAction
                    v-if="!isNew"
                    title="Muți articolul în coș?"
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
