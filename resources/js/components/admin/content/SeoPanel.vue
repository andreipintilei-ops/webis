<script setup lang="ts">
import { CircleCheck, CircleX } from '@lucide/vue';
import { computed } from 'vue';
import SwitchField from '@/components/admin/fields/SwitchField.vue';
import TextField from '@/components/admin/fields/TextField.vue';
import AssetField from '@/components/admin/media/AssetField.vue';
import { Label } from '@/components/ui/label';
import { slugify } from '@/lib/slugify';
import type { SeoData } from '@/types/content';

/**
 * Per-item SEO overrides with a Google result preview. Empty fields fall back
 * to generated defaults (the item's title and excerpt), which the preview
 * shows greyed so the editor sees what Google would get.
 */
const props = withDefaults(
    defineProps<{
        /** The item's own title — the default <title>. */
        title: string;
        /** The default description (the item's excerpt). */
        fallbackDescription?: string | null;
        /** Public path, e.g. /blog/ghid-seo. */
        path: string;
        /** Appended to titles, from the SEO settings (e.g. " | Webis"). */
        titleSuffix?: string;
        /** Plain text of the start of the content, for the keyword check. */
        contentText?: string;
        errors?: Record<string, string>;
    }>(),
    {
        fallbackDescription: null,
        titleSuffix: ' | Webis',
        contentText: '',
        errors: () => ({}),
    },
);

const seo = defineModel<SeoData>({ required: true });

// Google truncates by rendered width, not characters: ~580px of 20px Arial
// for titles, ~920px of 14px Arial for descriptions on desktop.
const TITLE_PX = 580;
const DESCRIPTION_PX = 920;

let canvas: HTMLCanvasElement | null = null;

function measure(text: string, font: string): number {
    canvas ??= document.createElement('canvas');
    const context = canvas.getContext('2d');

    if (!context) {
        return text.length * 8;
    }

    context.font = font;

    return context.measureText(text).width;
}

const effectiveTitle = computed(() => {
    const base = seo.value.title?.trim() || props.title.trim();

    return base.includes(props.titleSuffix.trim())
        ? base
        : `${base}${props.titleSuffix}`;
});

const effectiveDescription = computed(
    () =>
        seo.value.description?.trim() ||
        props.fallbackDescription?.trim() ||
        '',
);

const titleWidth = computed(() => measure(effectiveTitle.value, '20px Arial'));
const descriptionWidth = computed(() =>
    measure(effectiveDescription.value, '14px Arial'),
);

function bar(width: number, max: number): { percent: number; tone: string } {
    const ratio = width / max;

    return {
        percent: Math.min(100, Math.round(ratio * 100)),
        tone:
            ratio > 1
                ? 'bg-destructive'
                : ratio < 0.5
                  ? 'bg-amber-500'
                  : 'bg-emerald-500',
    };
}

const titleBar = computed(() => bar(titleWidth.value, TITLE_PX));
const descriptionBar = computed(() =>
    bar(descriptionWidth.value, DESCRIPTION_PX),
);

const displayUrl = computed(() => {
    const host =
        typeof window !== 'undefined' ? window.location.host : 'webis.ro';

    return `${host}${props.path}`.replace(/\/$/, '').split('/').join(' › ');
});

// ---- Focus keyword -----------------------------------------------------

function contains(haystack: string, needle: string): boolean {
    return slugify(haystack).includes(slugify(needle));
}

const checks = computed(() => {
    const keyword = seo.value.focus_keyword?.trim() ?? '';

    if (keyword === '') {
        return [];
    }

    return [
        {
            label: 'În titlul SEO',
            ok: contains(effectiveTitle.value, keyword),
        },
        { label: 'În adresă (URL)', ok: contains(props.path, keyword) },
        {
            label: 'În descriere',
            ok: contains(effectiveDescription.value, keyword),
        },
        {
            label: 'La începutul conținutului',
            ok: contains(props.contentText.slice(0, 600), keyword),
        },
        { label: 'Pagina este indexabilă', ok: !seo.value.noindex },
    ];
});
</script>

<template>
    <div class="flex flex-col gap-5">
        <div class="rounded-lg border bg-background p-4">
            <p class="mb-1 text-xs text-muted-foreground">
                Previzualizare Google
            </p>
            <p class="truncate text-xs text-[#4d5156] dark:text-[#bdc1c6]">
                {{ displayUrl }}
            </p>
            <p
                class="truncate text-lg leading-snug text-[#1a0dab] dark:text-[#8ab4f8]"
                :class="seo.title ? '' : 'opacity-70'"
            >
                {{ effectiveTitle }}
            </p>
            <p
                class="line-clamp-2 text-sm text-[#4d5156] dark:text-[#bdc1c6]"
                :class="seo.description ? '' : 'opacity-70'"
            >
                {{
                    effectiveDescription ||
                    'Fără descriere — Google va alege singur un fragment din pagină.'
                }}
            </p>
        </div>

        <div class="grid gap-1.5">
            <TextField
                v-model="seo.title"
                label="Titlu SEO"
                :placeholder="title"
                :error="errors.title"
                hint="Gol = titlul paginii. Sufixul se adaugă automat."
            />
            <div class="h-1 overflow-hidden rounded bg-muted">
                <div
                    class="h-full transition-all"
                    :class="titleBar.tone"
                    :style="{ width: `${titleBar.percent}%` }"
                />
            </div>
        </div>

        <div class="grid gap-1.5">
            <TextField
                v-model="seo.description"
                label="Meta descriere"
                multiline
                :rows="3"
                :placeholder="fallbackDescription ?? ''"
                :error="errors.description"
                hint="Aproximativ 140–160 de caractere, cu un îndemn."
            />
            <div class="h-1 overflow-hidden rounded bg-muted">
                <div
                    class="h-full transition-all"
                    :class="descriptionBar.tone"
                    :style="{ width: `${descriptionBar.percent}%` }"
                />
            </div>
        </div>

        <div class="grid gap-2">
            <TextField
                v-model="seo.focus_keyword"
                label="Expresie principală"
                placeholder="ex. creare magazin online"
                :error="errors.focus_keyword"
            />
            <ul v-if="checks.length" class="grid gap-1 text-sm">
                <li
                    v-for="check in checks"
                    :key="check.label"
                    class="flex items-center gap-2"
                >
                    <CircleCheck
                        v-if="check.ok"
                        class="size-4 text-emerald-600"
                    />
                    <CircleX v-else class="size-4 text-muted-foreground" />
                    {{ check.label }}
                </li>
            </ul>
        </div>

        <div class="grid gap-1.5">
            <Label>Imagine pentru distribuire (Open Graph)</Label>
            <AssetField v-model="seo.og_image_asset_id" />
            <p class="text-xs text-muted-foreground">
                1200 × 630 px. Gol = se generează automat din titlu.
            </p>
        </div>

        <TextField
            v-model="seo.canonical"
            label="URL canonic"
            placeholder="Gol = adresa paginii"
            :error="errors.canonical"
            hint="Doar dacă același conținut există la altă adresă."
        />

        <SwitchField
            v-model="seo.noindex"
            label="Ascunde de motoarele de căutare (noindex)"
            hint="Pagina rămâne accesibilă, dar nu apare în Google și nici în sitemap."
        />
    </div>
</template>
