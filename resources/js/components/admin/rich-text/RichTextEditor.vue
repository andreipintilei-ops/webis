<script setup lang="ts">
import { TableKit } from '@tiptap/extension-table';
import { CharacterCount, Placeholder } from '@tiptap/extensions';
import StarterKit from '@tiptap/starter-kit';
import { EditorContent, useEditor } from '@tiptap/vue-3';
import {
    Bold,
    Code,
    Columns3,
    Heading2,
    Heading3,
    Heading4,
    ImagePlus,
    Info,
    Italic,
    Link as LinkIcon,
    List,
    ListOrdered,
    Minus,
    Pilcrow,
    Quote,
    Redo2,
    Rows3,
    Strikethrough,
    Table as TableIcon,
    Trash2,
    Underline,
    Undo2,
} from '@lucide/vue';
import type { Component } from 'vue';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import MediaPicker from '@/components/admin/media/MediaPicker.vue';
import { AssetImage } from '@/components/admin/rich-text/extensions/AssetImage';
import { Callout } from '@/components/admin/rich-text/extensions/Callout';
import type { CalloutVariant } from '@/components/admin/rich-text/extensions/Callout';
import LinkDialog from '@/components/admin/rich-text/LinkDialog.vue';
import type { Asset } from '@/types/media';
import type { Choice, TiptapDoc } from '@/types/content';

/**
 * Tiptap v3 editor producing the JSON App\Support\RichText\RichText accepts:
 * StarterKit (h2–h4), links, tables, media-library images and callouts. The
 * server sanitises and renders it; anything not offered here is dropped there.
 */
const props = withDefaults(
    defineProps<{
        /** Pages offered by the link dialog's internal-page picker. */
        pages?: Choice[];
        placeholder?: string;
        /** A smaller toolbar (no tables, fewer headings) for short passages. */
        compact?: boolean;
    }>(),
    { pages: () => [], placeholder: 'Scrie aici…' },
);

const model = defineModel<TiptapDoc>({ required: true });

const editor = useEditor({
    content: model.value,
    extensions: [
        StarterKit.configure({
            heading: { levels: [2, 3, 4] },
            link: {
                openOnClick: false,
                autolink: true,
                defaultProtocol: 'https',
                // target/rel are decided on save, per link.
                HTMLAttributes: { target: null, rel: null },
            },
        }),
        TableKit.configure({ table: { resizable: false } }),
        Placeholder.configure({ placeholder: props.placeholder }),
        CharacterCount,
        AssetImage,
        Callout,
    ],
    onUpdate: ({ editor }) => {
        model.value = editor.getJSON() as TiptapDoc;
    },
});

// A document replaced from outside (e.g. a restored revision) reloads the editor.
watch(model, (doc) => {
    if (
        editor.value &&
        JSON.stringify(doc) !== JSON.stringify(editor.value.getJSON())
    ) {
        editor.value.commands.setContent(doc, { emitUpdate: false });
    }
});

onBeforeUnmount(() => editor.value?.destroy());

const words = computed(() => editor.value?.storage.characterCount.words() ?? 0);

// ---- Links -------------------------------------------------------------

const linkOpen = ref(false);
const linkHref = ref('');
const linkNofollow = ref(false);

function openLink(): void {
    const attrs = editor.value?.getAttributes('link') ?? {};
    linkHref.value = typeof attrs.href === 'string' ? attrs.href : '';
    linkNofollow.value =
        typeof attrs.rel === 'string' && attrs.rel.includes('nofollow');
    linkOpen.value = true;
}

function applyLink(href: string, nofollow: boolean): void {
    editor.value
        ?.chain()
        .focus()
        .extendMarkRange('link')
        .setLink({ href, rel: nofollow ? 'nofollow' : null, target: null })
        .run();
}

function removeLink(): void {
    editor.value?.chain().focus().extendMarkRange('link').unsetLink().run();
}

// ---- Images ------------------------------------------------------------

const pickerOpen = ref(false);

function insertImages(assets: Asset[]): void {
    for (const asset of assets) {
        editor.value
            ?.chain()
            .focus()
            .insertAssetImage({ assetId: asset.id })
            .run();
    }
}

// ---- Toolbar -----------------------------------------------------------

type Tool = {
    icon: Component;
    label: string;
    run: () => void;
    active?: () => boolean;
    disabled?: () => boolean;
};

function chain() {
    // eslint-disable-next-line @typescript-eslint/no-non-null-assertion
    return editor.value!.chain().focus();
}

const is = (name: string, attrs?: Record<string, unknown>) => () =>
    editor.value?.isActive(name, attrs) ?? false;

const history: Tool[] = [
    {
        icon: Undo2,
        label: 'Anulează',
        run: () => chain().undo().run(),
        disabled: () => !editor.value?.can().undo(),
    },
    {
        icon: Redo2,
        label: 'Refă',
        run: () => chain().redo().run(),
        disabled: () => !editor.value?.can().redo(),
    },
];

const blocks = computed<Tool[]>(() => [
    {
        icon: Pilcrow,
        label: 'Paragraf',
        run: () => chain().setParagraph().run(),
        active: is('paragraph'),
    },
    {
        icon: Heading2,
        label: 'Titlu 2',
        run: () => chain().toggleHeading({ level: 2 }).run(),
        active: is('heading', { level: 2 }),
    },
    {
        icon: Heading3,
        label: 'Titlu 3',
        run: () => chain().toggleHeading({ level: 3 }).run(),
        active: is('heading', { level: 3 }),
    },
    ...(props.compact
        ? []
        : [
              {
                  icon: Heading4,
                  label: 'Titlu 4',
                  run: () => chain().toggleHeading({ level: 4 }).run(),
                  active: is('heading', { level: 4 }),
              },
          ]),
]);

const marks: Tool[] = [
    {
        icon: Bold,
        label: 'Îngroșat',
        run: () => chain().toggleBold().run(),
        active: is('bold'),
    },
    {
        icon: Italic,
        label: 'Cursiv',
        run: () => chain().toggleItalic().run(),
        active: is('italic'),
    },
    {
        icon: Underline,
        label: 'Subliniat',
        run: () => chain().toggleUnderline().run(),
        active: is('underline'),
    },
    {
        icon: Strikethrough,
        label: 'Tăiat',
        run: () => chain().toggleStrike().run(),
        active: is('strike'),
    },
    {
        icon: Code,
        label: 'Cod',
        run: () => chain().toggleCode().run(),
        active: is('code'),
    },
    { icon: LinkIcon, label: 'Link', run: openLink, active: is('link') },
];

const structure = computed<Tool[]>(() => [
    {
        icon: List,
        label: 'Listă cu puncte',
        run: () => chain().toggleBulletList().run(),
        active: is('bulletList'),
    },
    {
        icon: ListOrdered,
        label: 'Listă numerotată',
        run: () => chain().toggleOrderedList().run(),
        active: is('orderedList'),
    },
    {
        icon: Quote,
        label: 'Citat',
        run: () => chain().toggleBlockquote().run(),
        active: is('blockquote'),
    },
    {
        icon: Info,
        label: 'Casetă evidențiată',
        run: () => chain().toggleCallout().run(),
        active: is('callout'),
    },
    {
        icon: ImagePlus,
        label: 'Imagine',
        run: () => (pickerOpen.value = true),
    },
    ...(props.compact
        ? []
        : [
              {
                  icon: Minus,
                  label: 'Linie separatoare',
                  run: () => chain().setHorizontalRule().run(),
              },
              {
                  icon: TableIcon,
                  label: 'Tabel',
                  run: () =>
                      chain()
                          .insertTable({
                              rows: 3,
                              cols: 3,
                              withHeaderRow: true,
                          })
                          .run(),
              },
          ]),
]);

const inTable = computed(() => editor.value?.isActive('table') ?? false);

const tableTools: Tool[] = [
    {
        icon: Rows3,
        label: 'Adaugă rând',
        run: () => chain().addRowAfter().run(),
    },
    {
        icon: Columns3,
        label: 'Adaugă coloană',
        run: () => chain().addColumnAfter().run(),
    },
    {
        icon: Trash2,
        label: 'Șterge rândul',
        run: () => chain().deleteRow().run(),
    },
    {
        icon: Trash2,
        label: 'Șterge coloana',
        run: () => chain().deleteColumn().run(),
    },
    {
        icon: Trash2,
        label: 'Șterge tabelul',
        run: () => chain().deleteTable().run(),
    },
];

const calloutVariants: { value: CalloutVariant; label: string }[] = [
    { value: 'info', label: 'Informație' },
    { value: 'tip', label: 'Sfat' },
    { value: 'warning', label: 'Atenție' },
];

const calloutVariant = computed({
    get: () =>
        (editor.value?.getAttributes('callout').variant as CalloutVariant) ??
        'info',
    set: (variant: CalloutVariant) => chain().setCalloutVariant(variant).run(),
});

const groups = computed(() => [history, blocks.value, marks, structure.value]);
</script>

<template>
    <div
        class="rich-text-editor overflow-hidden rounded-md border border-input shadow-xs focus-within:ring-[3px] focus-within:ring-ring/50"
    >
        <div
            v-if="editor"
            class="sticky top-0 z-10 flex flex-wrap items-center gap-1 border-b bg-muted/40 p-1"
            role="toolbar"
            aria-label="Formatare text"
        >
            <template v-for="(group, groupIndex) in groups" :key="groupIndex">
                <span
                    v-if="groupIndex > 0"
                    class="mx-1 h-5 w-px bg-border"
                    aria-hidden="true"
                />
                <button
                    v-for="tool in group"
                    :key="tool.label"
                    type="button"
                    class="inline-flex size-8 items-center justify-center rounded text-muted-foreground hover:bg-background hover:text-foreground disabled:opacity-40"
                    :class="
                        tool.active?.() ? 'bg-background text-foreground' : ''
                    "
                    :title="tool.label"
                    :aria-label="tool.label"
                    :aria-pressed="tool.active ? tool.active() : undefined"
                    :disabled="tool.disabled?.()"
                    @click="tool.run"
                >
                    <component :is="tool.icon" class="size-4" />
                </button>
            </template>

            <select
                v-if="editor.isActive('callout')"
                v-model="calloutVariant"
                class="h-8 rounded border bg-background px-2 text-xs"
                aria-label="Tip casetă"
            >
                <option
                    v-for="variant in calloutVariants"
                    :key="variant.value"
                    :value="variant.value"
                >
                    {{ variant.label }}
                </option>
            </select>

            <template v-if="inTable">
                <span class="mx-1 h-5 w-px bg-border" aria-hidden="true" />
                <button
                    v-for="tool in tableTools"
                    :key="tool.label"
                    type="button"
                    class="inline-flex h-8 items-center gap-1 rounded px-2 text-xs text-muted-foreground hover:bg-background hover:text-foreground"
                    :title="tool.label"
                    @click="tool.run"
                >
                    <component :is="tool.icon" class="size-3.5" />
                    {{ tool.label }}
                </button>
            </template>
        </div>

        <EditorContent :editor="editor" class="px-4 py-3" />

        <div
            class="flex justify-end border-t px-3 py-1 text-xs text-muted-foreground"
        >
            {{ words }} cuvinte
        </div>

        <LinkDialog
            v-model:open="linkOpen"
            :pages="pages"
            :initial-href="linkHref"
            :initial-nofollow="linkNofollow"
            @apply="applyLink"
            @remove="removeLink"
        />
        <MediaPicker
            v-model:open="pickerOpen"
            multiple
            title="Inserează imagini"
            @select="insertImages"
        />
    </div>
</template>
