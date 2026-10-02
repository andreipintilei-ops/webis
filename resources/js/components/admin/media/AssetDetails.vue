<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Copy, ExternalLink, RefreshCw, Trash2 } from '@lucide/vue';
import { onMounted, reactive, ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import ConfirmAction from '@/components/admin/ConfirmAction.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { api, ApiError } from '@/lib/adminApi';
import { destroy, replace, show, update } from '@/routes/admin/media';
import type { Asset, AssetUsage } from '@/types/media';

/**
 * Everything about one asset: alt text and caption, where it is used, and
 * replacing or deleting its file. Used in the media page's side panel.
 */
const props = defineProps<{ assetId: number }>();

const emit = defineEmits<{
    updated: [asset: Asset];
    deleted: [id: number];
}>();

const asset = ref<Asset | null>(null);
const usages = ref<AssetUsage[]>([]);
const form = reactive({ title: '', alt: '', caption: '' });
const errors = ref<Record<string, string>>({});
const saving = ref(false);
const replacing = ref(false);
const replaceInput = ref<HTMLInputElement | null>(null);

async function load(): Promise<void> {
    const response = await api<{ asset: Asset; usages: AssetUsage[] }>(
        'get',
        show.url(props.assetId),
    );

    asset.value = response.asset;
    usages.value = response.usages;
    form.title = response.asset.title ?? '';
    form.alt = response.asset.alt ?? '';
    form.caption = response.asset.caption ?? '';
    errors.value = {};
}

onMounted(load);
watch(() => props.assetId, load);

async function save(): Promise<void> {
    saving.value = true;

    try {
        const response = await api<{ asset: Asset }>(
            'patch',
            update.url(props.assetId),
            { ...form },
        );

        asset.value = response.asset;
        errors.value = {};
        emit('updated', response.asset);
        toast.success('Imaginea a fost salvată.');
    } catch (error) {
        if (error instanceof ApiError) {
            errors.value = error.errors;
            toast.error(error.message);
        }
    } finally {
        saving.value = false;
    }
}

async function onReplace(event: Event): Promise<void> {
    const file = (event.target as HTMLInputElement).files?.[0];
    (event.target as HTMLInputElement).value = '';

    if (!file) {
        return;
    }

    const data = new FormData();
    data.append('file', file);
    replacing.value = true;

    try {
        const response = await api<{ asset: Asset }>(
            'post',
            replace.url(props.assetId),
            data,
        );

        asset.value = response.asset;
        emit('updated', response.asset);
        toast.success('Fișierul a fost înlocuit peste tot unde e folosit.');
    } catch (error) {
        toast.error(
            error instanceof ApiError
                ? (error.errors.file ?? error.message)
                : 'Înlocuirea a eșuat.',
        );
    } finally {
        replacing.value = false;
    }
}

async function remove(): Promise<void> {
    try {
        await api('delete', destroy.url(props.assetId));
        emit('deleted', props.assetId);
        toast.success('Imaginea a fost ștearsă.');
    } catch (error) {
        toast.error(
            error instanceof ApiError ? error.message : 'Ștergerea a eșuat.',
        );
    }
}

async function copyUrl(): Promise<void> {
    if (asset.value?.url) {
        await navigator.clipboard.writeText(asset.value.url);
        toast.success('Adresa a fost copiată.');
    }
}

function formatSize(bytes: number | null): string {
    if (!bytes) {
        return '';
    }

    return bytes > 1024 * 1024
        ? `${(bytes / 1024 / 1024).toFixed(1)} MB`
        : `${Math.round(bytes / 1024)} KB`;
}
</script>

<template>
    <div v-if="asset" class="flex flex-col gap-5">
        <div
            class="overflow-hidden rounded-lg border bg-[repeating-conic-gradient(var(--color-muted)_0_25%,transparent_0_50%)] bg-size-[16px_16px]"
        >
            <img
                v-if="asset.url"
                :src="asset.url"
                :alt="asset.alt ?? ''"
                class="mx-auto max-h-72 object-contain"
            />
        </div>

        <dl
            class="grid grid-cols-[auto_1fr] gap-x-3 gap-y-1 text-xs text-muted-foreground"
        >
            <dt>Fișier</dt>
            <dd class="truncate">{{ asset.file_name }}</dd>
            <dt>Dimensiuni</dt>
            <dd>
                {{
                    asset.width && asset.height
                        ? `${asset.width} × ${asset.height} px`
                        : '—'
                }}
                · {{ formatSize(asset.size) }}
            </dd>
        </dl>

        <form class="flex flex-col gap-4" @submit.prevent="save">
            <div class="grid gap-2">
                <Label for="asset-alt">Text alternativ (alt)</Label>
                <Textarea
                    id="asset-alt"
                    v-model="form.alt"
                    rows="2"
                    placeholder="Ce se vede în imagine, pentru cititoare de ecran și Google"
                />
                <p class="text-xs text-muted-foreground">
                    Lasă gol doar pentru imagini decorative.
                </p>
                <InputError :message="errors.alt" />
            </div>

            <div class="grid gap-2">
                <Label for="asset-title">Titlu intern</Label>
                <Input id="asset-title" v-model="form.title" />
                <InputError :message="errors.title" />
            </div>

            <div class="grid gap-2">
                <Label for="asset-caption">Legendă</Label>
                <Input id="asset-caption" v-model="form.caption" />
                <InputError :message="errors.caption" />
            </div>

            <Button type="submit" :disabled="saving" class="self-start">
                Salvează
            </Button>
        </form>

        <section class="flex flex-col gap-2">
            <h3 class="text-sm font-medium">Folosită în</h3>
            <p v-if="usages.length === 0" class="text-sm text-muted-foreground">
                Nicăieri încă.
            </p>
            <ul v-else class="flex flex-col gap-1 text-sm">
                <li
                    v-for="usage in usages"
                    :key="`${usage.type}-${usage.title}`"
                >
                    <span class="text-muted-foreground">{{ usage.type }}:</span>
                    <Link
                        v-if="usage.url"
                        :href="usage.url"
                        class="ml-1 text-primary hover:underline"
                        >{{ usage.title }}</Link
                    >
                    <span v-else class="ml-1">{{ usage.title }}</span>
                </li>
            </ul>
        </section>

        <div class="flex flex-wrap gap-2 border-t pt-4">
            <Button type="button" variant="outline" size="sm" @click="copyUrl">
                <Copy /> Copiază adresa
            </Button>
            <Button
                v-if="asset.url"
                as="a"
                :href="asset.url"
                target="_blank"
                rel="noopener"
                variant="outline"
                size="sm"
            >
                <ExternalLink /> Deschide
            </Button>
            <Button
                type="button"
                variant="outline"
                size="sm"
                :disabled="replacing"
                @click="replaceInput?.click()"
            >
                <RefreshCw /> Înlocuiește fișierul
            </Button>
            <input
                ref="replaceInput"
                type="file"
                accept="image/*"
                class="hidden"
                @change="onReplace"
            />

            <ConfirmAction
                v-if="usages.length === 0"
                title="Ștergi imaginea?"
                description="Fișierul și toate versiunile lui vor fi șterse definitiv."
                @confirm="remove"
            >
                <Button type="button" variant="destructive" size="sm">
                    <Trash2 /> Șterge
                </Button>
            </ConfirmAction>
            <p v-else class="w-full text-xs text-muted-foreground">
                Imaginea nu poate fi ștearsă cât timp e folosită.
            </p>
        </div>
    </div>
</template>
