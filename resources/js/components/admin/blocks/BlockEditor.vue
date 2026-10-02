<script setup lang="ts">
import { useSortable } from '@vueuse/integrations/useSortable';
import {
    ChevronDown,
    ChevronRight,
    ChevronsDownUp,
    Copy,
    GripVertical,
    Plus,
    Trash2,
    TriangleAlert,
} from '@lucide/vue';
import { computed, ref, useTemplateRef, watch } from 'vue';
import { hasErrorsUnder, scoped } from '@/components/admin/blocks/errors';
import {
    blockDescriptions,
    blockEditors,
} from '@/components/admin/blocks/registry';
import ConfirmAction from '@/components/admin/ConfirmAction.vue';
import { Button } from '@/components/ui/button';
import {
    CommandDialog,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
} from '@/components/ui/command';
import type { BlockType, Choices, StoredBlock } from '@/types/content';

/**
 * The page builder: an ordered list of blocks, each edited by its own
 * *Editor.vue (see registry.ts). Drag to reorder, collapse, duplicate,
 * delete, and add from a searchable menu.
 */
const props = withDefaults(
    defineProps<{
        blockTypes: BlockType[];
        choices: Choices;
        errors?: Record<string, string>;
        /** The form field holding the blocks, for matching server errors. */
        errorKey?: string;
    }>(),
    { errors: () => ({}), errorKey: 'blocks' },
);

const blocks = defineModel<StoredBlock[]>({ required: true });

const typesByKey = computed(
    () => new Map(props.blockTypes.map((type) => [type.type, type])),
);

// Long pages start folded so the layout reads at a glance.
const expanded = ref(
    new Set<string>(
        blocks.value.length <= 3 ? blocks.value.map((b) => b.id) : [],
    ),
);

// A save that failed validation unfolds every block with an error in it.
watch(
    () => props.errors,
    (errors) => {
        blocks.value.forEach((block, index) => {
            if (hasErrorsUnder(errors, `${props.errorKey}.${index}`)) {
                expanded.value.add(block.id);
            }
        });
    },
);

function toggle(id: string): void {
    if (expanded.value.has(id)) {
        expanded.value.delete(id);
    } else {
        expanded.value.add(id);
    }
}

function collapseAll(): void {
    expanded.value.clear();
}

const list = useTemplateRef<HTMLElement>('list');

useSortable(list, blocks, {
    handle: '[data-drag-handle]',
    animation: 150,
});

// ---- Adding ------------------------------------------------------------

const menuOpen = ref(false);
const insertAt = ref<number | null>(null);

function openMenu(position: number | null): void {
    insertAt.value = position;
    menuOpen.value = true;
}

function add(type: BlockType): void {
    const block: StoredBlock = {
        id: crypto.randomUUID(),
        type: type.type,
        v: type.version,
        data: plainCopy(type.defaults),
    };

    const items = [...blocks.value];
    items.splice(insertAt.value ?? items.length, 0, block);
    blocks.value = items;
    expanded.value.add(block.id);
    menuOpen.value = false;
}

function duplicate(index: number): void {
    const copy: StoredBlock = {
        ...plainCopy(blocks.value[index]),
        id: crypto.randomUUID(),
    };

    const items = [...blocks.value];
    items.splice(index + 1, 0, copy);
    blocks.value = items;
    expanded.value.add(copy.id);
}

function remove(index: number): void {
    blocks.value = blocks.value.filter((_, i) => i !== index);
}

/**
 * A deep, non-reactive copy. Not structuredClone: page props and form data
 * are Vue proxies, which structuredClone refuses (DataCloneError). Block data
 * is plain JSON, so a JSON round-trip loses nothing.
 */
function plainCopy<T>(value: T): T {
    return JSON.parse(JSON.stringify(value)) as T;
}

/** A short hint of what a block says, shown when it is folded. */
function summary(block: StoredBlock): string {
    const data = block.data;
    const text = data.heading ?? data.title ?? data.question ?? '';

    return typeof text === 'string' ? text : '';
}
</script>

<template>
    <div class="flex flex-col gap-3">
        <div class="flex items-center justify-between">
            <h2 class="text-sm font-medium">Blocuri ({{ blocks.length }})</h2>
            <Button
                v-if="blocks.length > 1"
                type="button"
                variant="ghost"
                size="sm"
                @click="collapseAll"
            >
                <ChevronsDownUp /> Pliază tot
            </Button>
        </div>

        <ol ref="list" class="flex flex-col gap-3">
            <li
                v-for="(block, index) in blocks"
                :key="block.id"
                class="rounded-xl border bg-card"
                :class="
                    hasErrorsUnder(errors, `${errorKey}.${index}`)
                        ? 'border-destructive'
                        : ''
                "
            >
                <div class="flex items-center gap-2 px-2 py-2">
                    <button
                        type="button"
                        data-drag-handle
                        class="cursor-grab rounded p-1 text-muted-foreground hover:bg-muted active:cursor-grabbing"
                        aria-label="Trage pentru a muta"
                    >
                        <GripVertical class="size-4" />
                    </button>
                    <button
                        type="button"
                        class="flex min-w-0 flex-1 items-center gap-2 text-left"
                        :aria-expanded="expanded.has(block.id)"
                        @click="toggle(block.id)"
                    >
                        <ChevronDown
                            v-if="expanded.has(block.id)"
                            class="size-4 shrink-0"
                        />
                        <ChevronRight v-else class="size-4 shrink-0" />
                        <span class="shrink-0 text-sm font-medium">
                            {{
                                typesByKey.get(block.type)?.label ?? block.type
                            }}
                        </span>
                        <span
                            v-if="!expanded.has(block.id) && summary(block)"
                            class="truncate text-sm text-muted-foreground"
                        >
                            — {{ summary(block) }}
                        </span>
                        <TriangleAlert
                            v-if="
                                hasErrorsUnder(errors, `${errorKey}.${index}`)
                            "
                            class="size-4 shrink-0 text-destructive"
                        />
                    </button>
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon-sm"
                        aria-label="Adaugă un bloc după acesta"
                        title="Adaugă un bloc după acesta"
                        @click="openMenu(index + 1)"
                    >
                        <Plus />
                    </Button>
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon-sm"
                        aria-label="Duplică"
                        title="Duplică"
                        @click="duplicate(index)"
                    >
                        <Copy />
                    </Button>
                    <ConfirmAction
                        title="Ștergi blocul?"
                        description="Conținutul lui se pierde (poate fi recuperat din revizii după salvare)."
                        @confirm="remove(index)"
                    >
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon-sm"
                            aria-label="Șterge"
                            title="Șterge"
                        >
                            <Trash2 />
                        </Button>
                    </ConfirmAction>
                </div>

                <div v-if="expanded.has(block.id)" class="border-t p-4">
                    <component
                        :is="blockEditors[block.type]"
                        v-if="blockEditors[block.type]"
                        v-model:data="block.data"
                        :errors="scoped(errors, `${errorKey}.${index}.data`)"
                        :choices="choices"
                    />
                    <p v-else class="text-sm text-muted-foreground">
                        Tip de bloc necunoscut „{{ block.type }}”. Datele lui
                        sunt păstrate, dar nu apare pe site.
                    </p>
                </div>
            </li>
        </ol>

        <p
            v-if="blocks.length === 0"
            class="rounded-xl border border-dashed p-8 text-center text-sm text-muted-foreground"
        >
            Pagina nu are încă niciun bloc.
        </p>

        <Button
            type="button"
            variant="outline"
            class="self-start"
            @click="openMenu(null)"
        >
            <Plus /> Adaugă bloc
        </Button>

        <CommandDialog
            v-model:open="menuOpen"
            title="Adaugă bloc"
            description="Alege tipul de bloc"
        >
            <CommandInput placeholder="Caută un tip de bloc…" />
            <CommandList>
                <CommandEmpty>Niciun rezultat.</CommandEmpty>
                <CommandGroup>
                    <CommandItem
                        v-for="type in blockTypes"
                        :key="type.type"
                        :value="`${type.label} ${blockDescriptions[type.type] ?? ''}`"
                        @select="add(type)"
                    >
                        <div class="flex flex-col">
                            <span>{{ type.label }}</span>
                            <span class="text-xs text-muted-foreground">
                                {{ blockDescriptions[type.type] }}
                            </span>
                        </div>
                    </CommandItem>
                </CommandGroup>
            </CommandList>
        </CommandDialog>
    </div>
</template>
