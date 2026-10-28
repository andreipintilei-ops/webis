<script setup lang="ts">
import { ref, watch } from 'vue';
import SelectField from '@/components/admin/fields/SelectField.vue';
import TextField from '@/components/admin/fields/TextField.vue';
import SwitchField from '@/components/admin/fields/SwitchField.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import type { Choice } from '@/types/content';

/**
 * Add or edit a link: pick a page of the site, or type any address. The
 * server decides target/rel (internal links stay plain); the editor may only
 * mark an external link nofollow.
 */
const props = defineProps<{
    pages: Choice[];
    initialHref: string;
    initialNofollow: boolean;
}>();

const open = defineModel<boolean>('open', { default: false });

const emit = defineEmits<{
    apply: [href: string, nofollow: boolean];
    remove: [];
}>();

const href = ref<string | number | null>('');
const page = ref<string | number | null>(null);
const nofollow = ref(false);

watch(open, (isOpen) => {
    if (isOpen) {
        href.value = props.initialHref;
        nofollow.value = props.initialNofollow;
        page.value = null;
    }
});

watch(page, (value) => {
    const choice = props.pages.find((p) => p.id === Number(value));

    if (choice?.url) {
        href.value = choice.url;
    }
});

function apply(): void {
    const value = String(href.value ?? '').trim();

    if (value === '') {
        emit('remove');
    } else {
        emit('apply', value, nofollow.value);
    }

    open.value = false;
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>Link</DialogTitle>
            </DialogHeader>

            <form class="grid gap-4" @submit.prevent="apply">
                <SelectField
                    v-if="pages.length"
                    v-model="page"
                    label="Pagină din site"
                    :options="
                        pages
                            .filter((p) => p.url)
                            .map((p) => ({
                                value: String(p.id),
                                label: p.label,
                            }))
                    "
                    nullable
                    empty-label="Alege o pagină…"
                    numeric
                />
                <TextField
                    v-model="href"
                    label="Adresă"
                    placeholder="/servicii, https://…, mailto:…, tel:…"
                    hint="Linkurile interne încep cu /. Cele externe se deschid într-un tab nou."
                />
                <SwitchField
                    v-model="nofollow"
                    label="Nofollow"
                    hint="Doar pentru linkuri externe pe care nu vrei să le recomanzi motoarelor de căutare."
                />

                <DialogFooter class="gap-2">
                    <Button
                        v-if="initialHref"
                        type="button"
                        variant="ghost"
                        class="mr-auto"
                        @click="
                            emit('remove');
                            open = false;
                        "
                    >
                        Elimină linkul
                    </Button>
                    <Button type="submit">Aplică</Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
