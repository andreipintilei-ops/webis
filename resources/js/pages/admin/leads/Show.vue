<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Mail, Phone, Trash2 } from '@lucide/vue';
import { computed } from 'vue';
import ConfirmAction from '@/components/admin/ConfirmAction.vue';
import SelectField from '@/components/admin/fields/SelectField.vue';
import TextField from '@/components/admin/fields/TextField.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { destroy, index, update } from '@/routes/admin/leads';
import type { Option } from '@/types/content';

type LeadDetail = {
    id: number;
    name: string;
    email: string | null;
    phone: string | null;
    company: string | null;
    service: string | null;
    service_page_url: string | null;
    budget: string | null;
    message: string | null;
    source_url: string | null;
    referrer: string | null;
    // An empty PHP array arrives as [], a filled one as an object.
    utm: Record<string, string | null> | unknown[];
    gclid: string | null;
    consent_at: string | null;
    ip_address: string | null;
    user_agent: string | null;
    status: string;
    notes: string | null;
    assigned_to: number | null;
    created_at: string | null;
};

const props = defineProps<{
    lead: LeadDetail;
    statuses: Option[];
    users: Option[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Cereri de ofertă', href: index() }],
    },
});

const form = useForm({
    status: props.lead.status,
    notes: props.lead.notes,
    assigned_to: props.lead.assigned_to,
});

const utmEntries = computed(() =>
    Object.entries(props.lead.utm ?? {}).filter(
        ([, value]) => value !== null && value !== '',
    ),
);

// Spaces, dots and dashes are for reading; the dialer wants digits.
const telHref = computed(() =>
    props.lead.phone
        ? `tel:${props.lead.phone.replace(/[^\d+]/g, '')}`
        : undefined,
);

function submit(): void {
    form.patch(update.url(props.lead.id), { preserveScroll: true });
}

function remove(): void {
    router.delete(destroy.url(props.lead.id));
}
</script>

<template>
    <Head :title="lead.name" />

    <div class="flex flex-col gap-4 p-4">
        <div class="flex items-center gap-3">
            <Button
                as-child
                variant="ghost"
                size="icon"
                aria-label="Înapoi la cereri"
            >
                <Link :href="index()"><ArrowLeft /></Link>
            </Button>
            <h1 class="truncate text-2xl font-bold tracking-tight">
                {{ lead.name }}
            </h1>
        </div>

        <div class="grid gap-6 lg:grid-cols-[1fr_20rem]">
            <div class="flex min-w-0 flex-col gap-4">
                <section
                    class="flex flex-col gap-3 rounded-xl border bg-card p-4"
                >
                    <h2 class="text-sm font-medium">Contact</h2>
                    <dl
                        class="grid gap-x-6 gap-y-2 text-sm sm:grid-cols-[8rem_1fr]"
                    >
                        <dt class="text-muted-foreground">Nume</dt>
                        <dd>{{ lead.name }}</dd>
                        <dt class="text-muted-foreground">Firmă</dt>
                        <dd>{{ lead.company ?? '—' }}</dd>
                        <dt class="text-muted-foreground">Email</dt>
                        <dd class="break-all">
                            <a
                                v-if="lead.email"
                                :href="`mailto:${lead.email}`"
                                class="text-primary hover:underline"
                                >{{ lead.email }}</a
                            >
                            <template v-else>—</template>
                        </dd>
                        <dt class="text-muted-foreground">Telefon</dt>
                        <dd>
                            <a
                                v-if="lead.phone"
                                :href="telHref"
                                class="text-primary hover:underline"
                                >{{ lead.phone }}</a
                            >
                            <template v-else>—</template>
                        </dd>
                        <dt class="text-muted-foreground">Primită</dt>
                        <dd>{{ lead.created_at ?? '—' }}</dd>
                    </dl>
                    <div
                        v-if="lead.email || lead.phone"
                        class="flex flex-wrap gap-2 pt-1"
                    >
                        <Button v-if="lead.email" as-child size="sm">
                            <a :href="`mailto:${lead.email}`"
                                ><Mail /> Scrie email</a
                            >
                        </Button>
                        <Button
                            v-if="lead.phone"
                            as-child
                            size="sm"
                            variant="outline"
                        >
                            <a :href="telHref"><Phone /> Sună</a>
                        </Button>
                    </div>
                </section>

                <section
                    class="flex flex-col gap-3 rounded-xl border bg-card p-4"
                >
                    <h2 class="text-sm font-medium">Mesaj</h2>
                    <p
                        v-if="lead.message"
                        class="text-sm break-words whitespace-pre-line"
                    >
                        {{ lead.message }}
                    </p>
                    <p v-else class="text-sm text-muted-foreground">
                        Fără mesaj.
                    </p>
                    <dl
                        class="grid gap-x-6 gap-y-2 border-t pt-3 text-sm sm:grid-cols-[8rem_1fr]"
                    >
                        <dt class="text-muted-foreground">Serviciu</dt>
                        <dd>
                            <Link
                                v-if="lead.service_page_url && lead.service"
                                :href="lead.service_page_url"
                                class="text-primary hover:underline"
                                >{{ lead.service }}</Link
                            >
                            <template v-else>{{
                                lead.service ?? '—'
                            }}</template>
                        </dd>
                        <dt class="text-muted-foreground">Buget</dt>
                        <dd>{{ lead.budget ?? '—' }}</dd>
                    </dl>
                </section>

                <section
                    class="flex flex-col gap-3 rounded-xl border bg-card p-4"
                >
                    <h2 class="text-sm font-medium">Proveniență</h2>
                    <dl
                        class="grid gap-x-6 gap-y-2 text-sm sm:grid-cols-[8rem_1fr]"
                    >
                        <dt class="text-muted-foreground">Pagina</dt>
                        <dd class="break-all">
                            <a
                                v-if="lead.source_url"
                                :href="lead.source_url"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="text-primary hover:underline"
                                >{{ lead.source_url }}</a
                            >
                            <template v-else>—</template>
                        </dd>
                        <dt class="text-muted-foreground">Referrer</dt>
                        <dd class="break-all">{{ lead.referrer ?? '—' }}</dd>
                        <template v-for="[key, value] in utmEntries" :key="key">
                            <dt class="text-muted-foreground">{{ key }}</dt>
                            <dd class="break-all">{{ value }}</dd>
                        </template>
                        <dt class="text-muted-foreground">gclid</dt>
                        <dd class="break-all">{{ lead.gclid ?? '—' }}</dd>
                    </dl>
                    <p class="text-sm">
                        Acord GDPR: {{ lead.consent_at ?? '—' }}
                    </p>
                    <div
                        class="flex flex-col gap-1 border-t pt-3 text-xs text-muted-foreground"
                    >
                        <p>IP: {{ lead.ip_address ?? '—' }}</p>
                        <p class="break-all">
                            Browser: {{ lead.user_agent ?? '—' }}
                        </p>
                        <p>Șterse automat după 90 de zile.</p>
                    </div>
                </section>
            </div>

            <aside class="flex flex-col gap-4">
                <form
                    class="flex flex-col gap-4 rounded-xl border bg-card p-4"
                    @submit.prevent="submit"
                >
                    <fieldset class="grid gap-2">
                        <legend class="mb-2 text-sm font-medium">Stare</legend>
                        <label
                            v-for="option in statuses"
                            :key="option.value"
                            class="flex items-center gap-2 text-sm"
                        >
                            <input
                                v-model="form.status"
                                type="radio"
                                name="status"
                                :value="option.value"
                                class="size-4"
                            />
                            {{ option.label }}
                        </label>
                        <InputError :message="form.errors.status" />
                    </fieldset>
                    <SelectField
                        v-model="form.assigned_to"
                        label="Responsabil"
                        :options="users"
                        numeric
                        nullable
                        empty-label="Nimeni"
                        :error="form.errors.assigned_to"
                    />
                    <TextField
                        v-model="form.notes"
                        label="Note interne"
                        multiline
                        :rows="6"
                        :error="form.errors.notes"
                    />
                    <Button type="submit" :disabled="form.processing">
                        Salvează
                    </Button>
                </form>

                <ConfirmAction
                    title="Ștergi cererea definitiv?"
                    description="Toate datele persoanei din această cerere se șterg, de exemplu la o cerere de ștergere conform GDPR. Acțiunea nu poate fi anulată."
                    confirm-label="Șterge definitiv"
                    @confirm="remove"
                >
                    <Button
                        type="button"
                        variant="ghost"
                        class="self-start text-destructive"
                    >
                        <Trash2 /> Șterge cererea
                    </Button>
                </ConfirmAction>
            </aside>
        </div>
    </div>
</template>
