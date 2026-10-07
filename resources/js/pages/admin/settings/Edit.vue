<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import ItemList from '@/components/admin/fields/ItemList.vue';
import SwitchField from '@/components/admin/fields/SwitchField.vue';
import TextField from '@/components/admin/fields/TextField.vue';
import AssetField from '@/components/admin/media/AssetField.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Textarea } from '@/components/ui/textarea';
import {
    analytics as analyticsRoute,
    brand as brandRoute,
    company as companyRoute,
    edit,
    leads as leadsRoute,
    seo as seoRoute,
} from '@/routes/admin/settings';
import type { Option } from '@/types/content';

type OpeningHours = { days: string[]; opens: string; closes: string };

type BrandSettings = {
    logo_asset_id: number | null;
    logo_negative_asset_id: number | null;
    favicon_asset_id: number | null;
    icon_asset_id: number | null;
};

type CompanySettings = {
    name: string;
    legal_name: string;
    vat_id: string | null;
    registration_number: string | null;
    phone: string | null;
    email: string | null;
    street_address: string | null;
    locality: string;
    region: string;
    postal_code: string | null;
    country_code: string;
    latitude: number | null;
    longitude: number | null;
    opening_hours: OpeningHours[];
    // An empty PHP array serialises as [], so this may arrive as a list.
    social: Record<string, string> | string[];
    google_rating: number | null;
    google_review_count: number | null;
    google_reviews_url: string | null;
};

type SeoSettings = {
    title_suffix: string;
    default_description: string | null;
    default_og_image_asset_id: number | null;
    logo_asset_id: number | null;
};

type AnalyticsSettings = {
    ga4_measurement_id: string | null;
    google_ads_id: string | null;
    google_site_verification: string | null;
    bing_site_verification: string | null;
};

type LeadSettings = {
    notification_emails: string[];
    budget_options: string[];
    send_confirmation: boolean;
};

const props = defineProps<{
    brand: BrandSettings;
    company: CompanySettings;
    seo: SeoSettings;
    analytics: AnalyticsSettings;
    leads: LeadSettings;
    socialPlatforms: string[];
    days: Option[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Setări', href: edit() }],
    },
});

const saveOptions = { preserveScroll: true };

// Identity

const brandForm = useForm({ ...props.brand });

function submitBrand(): void {
    brandForm.put(brandRoute.url(), saveOptions);
}

// Company

const savedSocial = props.company.social as Record<string, string | undefined>;

const companyForm = useForm({
    ...props.company,
    opening_hours: props.company.opening_hours.map((hours) => ({
        days: [...hours.days],
        opens: hours.opens,
        closes: hours.closes,
    })),
    social: Object.fromEntries(
        props.socialPlatforms.map((platform) => [
            platform,
            savedSocial[platform] ?? null,
        ]),
    ) as Record<string, string | null>,
});

const companyErrors = computed(
    () => companyForm.errors as Record<string, string>,
);

function capitalise(value: string): string {
    return value.charAt(0).toUpperCase() + value.slice(1);
}

function submitCompany(): void {
    companyForm.put(companyRoute.url(), saveOptions);
}

// SEO

const seoForm = useForm({ ...props.seo });

function submitSeo(): void {
    seoForm.put(seoRoute.url(), saveOptions);
}

// Analytics

const analyticsForm = useForm({ ...props.analytics });

function submitAnalytics(): void {
    analyticsForm.put(analyticsRoute.url(), saveOptions);
}

// Leads — the lists are edited as one entry per line.

const leadsForm = useForm({
    notification_emails: [...props.leads.notification_emails],
    budget_options: [...props.leads.budget_options],
    send_confirmation: props.leads.send_confirmation,
});

const emailsText = ref(props.leads.notification_emails.join('\n'));
const budgetsText = ref(props.leads.budget_options.join('\n'));

function lines(value: string): string[] {
    return value
        .split('\n')
        .map((line) => line.trim())
        .filter((line) => line !== '');
}

watch(emailsText, (value) => (leadsForm.notification_emails = lines(value)));
watch(budgetsText, (value) => (leadsForm.budget_options = lines(value)));

const leadsErrors = computed(() => leadsForm.errors as Record<string, string>);

/** The list's own error, or the first per-line one with its line number. */
function listError(field: string): string | undefined {
    const errors = leadsErrors.value;

    if (errors[field]) {
        return errors[field];
    }

    const key = Object.keys(errors).find((name) =>
        name.startsWith(`${field}.`),
    );

    if (!key) {
        return undefined;
    }

    const line = Number(key.slice(field.length + 1)) + 1;

    return `Rândul ${line}: ${errors[key]}`;
}

function submitLeads(): void {
    leadsForm.put(leadsRoute.url(), saveOptions);
}
</script>

<template>
    <Head title="Setări" />

    <div class="flex max-w-3xl flex-col gap-4 p-4">
        <h1 class="text-2xl font-bold tracking-tight">Setări</h1>

        <Tabs default-value="brand" class="min-w-0">
            <TabsList>
                <TabsTrigger value="brand">Identitate</TabsTrigger>
                <TabsTrigger value="company">Companie</TabsTrigger>
                <TabsTrigger value="seo">SEO</TabsTrigger>
                <TabsTrigger value="analytics">Analiză</TabsTrigger>
                <TabsTrigger value="leads">Cereri</TabsTrigger>
            </TabsList>

            <TabsContent value="brand" class="pt-4">
                <form class="flex flex-col gap-4" @submit.prevent="submitBrand">
                    <section
                        class="flex flex-col gap-5 rounded-xl border bg-card p-4"
                    >
                        <div class="grid gap-1.5">
                            <Label>Logo</Label>
                            <AssetField v-model="brandForm.logo_asset_id" />
                            <p class="text-xs text-muted-foreground">
                                Pentru fundal deschis — în antetul site-ului.
                                SVG sau PNG cu fundal transparent.
                            </p>
                            <InputError
                                :message="brandForm.errors.logo_asset_id"
                            />
                        </div>
                        <div class="grid gap-1.5">
                            <Label>Logo negativ</Label>
                            <AssetField
                                v-model="brandForm.logo_negative_asset_id"
                            />
                            <p class="text-xs text-muted-foreground">
                                Pentru fundal închis — când meniul e deschis.
                                Gol = se folosește logo-ul de mai sus.
                            </p>
                            <InputError
                                :message="
                                    brandForm.errors.logo_negative_asset_id
                                "
                            />
                        </div>
                        <div class="grid gap-1.5">
                            <Label>Favicon</Label>
                            <AssetField v-model="brandForm.favicon_asset_id" />
                            <p class="text-xs text-muted-foreground">
                                Simbolul din tab-ul browserului, din Google și
                                de pe ecranul telefonului. O imagine pătrată,
                                minimum 180 × 180 px; variantele necesare se
                                generează automat la salvare.
                            </p>
                            <InputError
                                :message="brandForm.errors.favicon_asset_id"
                            />
                        </div>
                        <div class="grid gap-1.5">
                            <Label>Iconiță</Label>
                            <AssetField v-model="brandForm.icon_asset_id" />
                            <p class="text-xs text-muted-foreground">
                                Pătrată, cu fundal — apare în colțul panoului de
                                administrare și pe pagina de autentificare.
                            </p>
                            <InputError
                                :message="brandForm.errors.icon_asset_id"
                            />
                        </div>
                    </section>

                    <div class="flex justify-end">
                        <Button type="submit" :disabled="brandForm.processing">
                            Salvează
                        </Button>
                    </div>
                </form>
            </TabsContent>

            <TabsContent value="company" class="pt-4">
                <!-- novalidate: decimal coordinates would fail the number
                     inputs' default step; the server validates everything. -->
                <form
                    novalidate
                    class="flex flex-col gap-4"
                    @submit.prevent="submitCompany"
                >
                    <p
                        class="rounded-lg border border-amber-300 bg-amber-50 px-3 py-2 text-sm text-amber-900 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-200"
                    >
                        Numele, adresa și telefonul trebuie să fie identice cu
                        cele din Google Business Profile.
                    </p>

                    <section
                        class="flex flex-col gap-4 rounded-xl border bg-card p-4"
                    >
                        <h2 class="text-sm font-medium">Identitate</h2>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <TextField
                                v-model="companyForm.name"
                                label="Nume"
                                :max="100"
                                required
                                :error="companyForm.errors.name"
                            />
                            <TextField
                                v-model="companyForm.legal_name"
                                label="Denumire legală"
                                :max="150"
                                required
                                :error="companyForm.errors.legal_name"
                            />
                            <TextField
                                v-model="companyForm.vat_id"
                                label="CUI"
                                :error="companyForm.errors.vat_id"
                            />
                            <TextField
                                v-model="companyForm.registration_number"
                                label="Nr. Reg. Com."
                                :error="companyForm.errors.registration_number"
                            />
                        </div>
                    </section>

                    <section
                        class="flex flex-col gap-4 rounded-xl border bg-card p-4"
                    >
                        <h2 class="text-sm font-medium">Contact</h2>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <TextField
                                v-model="companyForm.phone"
                                label="Telefon"
                                type="tel"
                                :error="companyForm.errors.phone"
                            />
                            <TextField
                                v-model="companyForm.email"
                                label="Email"
                                type="email"
                                :error="companyForm.errors.email"
                            />
                        </div>
                    </section>

                    <section
                        class="flex flex-col gap-4 rounded-xl border bg-card p-4"
                    >
                        <h2 class="text-sm font-medium">Adresă</h2>
                        <TextField
                            v-model="companyForm.street_address"
                            label="Stradă și număr"
                            :error="companyForm.errors.street_address"
                        />
                        <div class="grid gap-4 sm:grid-cols-2">
                            <TextField
                                v-model="companyForm.locality"
                                label="Localitate"
                                required
                                :error="companyForm.errors.locality"
                            />
                            <TextField
                                v-model="companyForm.region"
                                label="Județ"
                                required
                                :error="companyForm.errors.region"
                            />
                            <TextField
                                v-model="companyForm.postal_code"
                                label="Cod poștal"
                                :error="companyForm.errors.postal_code"
                            />
                            <TextField
                                v-model="companyForm.country_code"
                                label="Țară (cod ISO)"
                                placeholder="RO"
                                required
                                :error="companyForm.errors.country_code"
                            />
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <TextField
                                v-model="companyForm.latitude"
                                label="Latitudine"
                                type="number"
                                :error="companyForm.errors.latitude"
                            />
                            <TextField
                                v-model="companyForm.longitude"
                                label="Longitudine"
                                type="number"
                                :error="companyForm.errors.longitude"
                            />
                        </div>
                        <p class="-mt-2 text-xs text-muted-foreground">
                            Pentru harta din Google și schema LocalBusiness.
                        </p>
                    </section>

                    <section
                        class="flex flex-col gap-4 rounded-xl border bg-card p-4"
                    >
                        <ItemList
                            v-model="companyForm.opening_hours"
                            label="Program"
                            :new-item="
                                () => ({
                                    days: [],
                                    opens: '09:00',
                                    closes: '17:00',
                                })
                            "
                            add-label="Adaugă interval"
                            :max="7"
                            :error="companyForm.errors.opening_hours"
                        >
                            <template #default="{ item, index: i }">
                                <div class="flex flex-col gap-3">
                                    <div class="flex flex-wrap gap-3">
                                        <label
                                            v-for="day in days"
                                            :key="day.value"
                                            class="flex items-center gap-1.5 text-sm"
                                        >
                                            <input
                                                v-model="item.days"
                                                type="checkbox"
                                                :value="day.value"
                                                class="size-4"
                                            />
                                            {{ day.label }}
                                        </label>
                                    </div>
                                    <InputError
                                        :message="
                                            companyErrors[
                                                `opening_hours.${i}.days`
                                            ] ??
                                            Object.entries(companyErrors).find(
                                                ([key]) =>
                                                    key.startsWith(
                                                        `opening_hours.${i}.days.`,
                                                    ),
                                            )?.[1]
                                        "
                                    />
                                    <div class="grid grid-cols-2 gap-3 sm:w-80">
                                        <div class="grid gap-1.5">
                                            <Label :for="`opens-${i}`">
                                                Deschide
                                            </Label>
                                            <Input
                                                :id="`opens-${i}`"
                                                v-model="item.opens"
                                                type="time"
                                            />
                                            <InputError
                                                :message="
                                                    companyErrors[
                                                        `opening_hours.${i}.opens`
                                                    ]
                                                "
                                            />
                                        </div>
                                        <div class="grid gap-1.5">
                                            <Label :for="`closes-${i}`">
                                                Închide
                                            </Label>
                                            <Input
                                                :id="`closes-${i}`"
                                                v-model="item.closes"
                                                type="time"
                                            />
                                            <InputError
                                                :message="
                                                    companyErrors[
                                                        `opening_hours.${i}.closes`
                                                    ]
                                                "
                                            />
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </ItemList>
                    </section>

                    <section
                        class="flex flex-col gap-4 rounded-xl border bg-card p-4"
                    >
                        <h2 class="text-sm font-medium">Rețele sociale</h2>
                        <TextField
                            v-for="platform in socialPlatforms"
                            :key="platform"
                            v-model="companyForm.social[platform]"
                            :label="capitalise(platform)"
                            placeholder="https://…"
                            :error="companyErrors[`social.${platform}`]"
                        />
                    </section>

                    <section
                        class="flex flex-col gap-4 rounded-xl border bg-card p-4"
                    >
                        <h2 class="text-sm font-medium">Recenzii Google</h2>
                        <p class="text-sm text-muted-foreground">
                            Apar în prima secțiune a paginilor, deasupra
                            logo-urilor clienților. Copiați-le din profilul
                            Google Business; fără notă, nu apar.
                        </p>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <TextField
                                v-model="companyForm.google_rating"
                                type="number"
                                label="Nota (1–5)"
                                placeholder="4.7"
                                :error="companyForm.errors.google_rating"
                            />
                            <TextField
                                v-model="companyForm.google_review_count"
                                type="number"
                                label="Număr de recenzii"
                                :error="companyForm.errors.google_review_count"
                            />
                        </div>
                        <TextField
                            v-model="companyForm.google_reviews_url"
                            label="Link către recenzii"
                            placeholder="https://…"
                            :error="companyForm.errors.google_reviews_url"
                        />
                    </section>

                    <div class="flex justify-end">
                        <Button
                            type="submit"
                            :disabled="companyForm.processing"
                        >
                            Salvează
                        </Button>
                    </div>
                </form>
            </TabsContent>

            <TabsContent value="seo" class="pt-4">
                <form class="flex flex-col gap-4" @submit.prevent="submitSeo">
                    <section
                        class="flex flex-col gap-4 rounded-xl border bg-card p-4"
                    >
                        <TextField
                            v-model="seoForm.title_suffix"
                            label="Sufix titlu"
                            :max="40"
                            hint='Adăugat la finalul fiecărui titlu, ex. " | Webis"'
                            :error="seoForm.errors.title_suffix"
                        />
                        <TextField
                            v-model="seoForm.default_description"
                            label="Descriere implicită"
                            multiline
                            :max="320"
                            :error="seoForm.errors.default_description"
                        />
                        <div class="grid gap-1.5">
                            <Label>Imagine implicită de distribuire</Label>
                            <AssetField
                                v-model="seoForm.default_og_image_asset_id"
                            />
                            <p class="text-xs text-muted-foreground">
                                1200 × 630 px
                            </p>
                            <InputError
                                :message="
                                    seoForm.errors.default_og_image_asset_id
                                "
                            />
                        </div>
                        <div class="grid gap-1.5">
                            <Label>Logo pentru Google</Label>
                            <AssetField v-model="seoForm.logo_asset_id" />
                            <p class="text-xs text-muted-foreground">
                                Pătrat, minimum 112 × 112 px
                            </p>
                            <InputError
                                :message="seoForm.errors.logo_asset_id"
                            />
                        </div>
                    </section>

                    <div class="flex justify-end">
                        <Button type="submit" :disabled="seoForm.processing">
                            Salvează
                        </Button>
                    </div>
                </form>
            </TabsContent>

            <TabsContent value="analytics" class="pt-4">
                <form
                    class="flex flex-col gap-4"
                    @submit.prevent="submitAnalytics"
                >
                    <section
                        class="flex flex-col gap-4 rounded-xl border bg-card p-4"
                    >
                        <TextField
                            v-model="analyticsForm.ga4_measurement_id"
                            label="Google Analytics 4 (ID de măsurare)"
                            placeholder="G-XXXXXXXXXX"
                            hint="Se încarcă doar după acordul vizitatorului pentru cookie-uri"
                            :error="analyticsForm.errors.ga4_measurement_id"
                        />
                        <TextField
                            v-model="analyticsForm.google_ads_id"
                            label="Google Ads"
                            placeholder="AW-…"
                            :error="analyticsForm.errors.google_ads_id"
                        />
                        <TextField
                            v-model="analyticsForm.google_site_verification"
                            label="Verificare Google Search Console"
                            hint="Doar codul din atributul content"
                            :error="
                                analyticsForm.errors.google_site_verification
                            "
                        />
                        <TextField
                            v-model="analyticsForm.bing_site_verification"
                            label="Verificare Bing Webmaster Tools"
                            hint="Doar codul din atributul content"
                            :error="analyticsForm.errors.bing_site_verification"
                        />
                    </section>

                    <div class="flex justify-end">
                        <Button
                            type="submit"
                            :disabled="analyticsForm.processing"
                        >
                            Salvează
                        </Button>
                    </div>
                </form>
            </TabsContent>

            <TabsContent value="leads" class="pt-4">
                <form class="flex flex-col gap-4" @submit.prevent="submitLeads">
                    <section
                        class="flex flex-col gap-4 rounded-xl border bg-card p-4"
                    >
                        <div class="grid gap-1.5">
                            <Label for="notification-emails">
                                Adrese notificate la o cerere nouă
                            </Label>
                            <Textarea
                                id="notification-emails"
                                v-model="emailsText"
                                :rows="4"
                                placeholder="office@webis.ro"
                                :aria-invalid="
                                    !!listError('notification_emails')
                                "
                            />
                            <p class="text-xs text-muted-foreground">
                                Câte o adresă pe rând, maximum 10.
                            </p>
                            <InputError
                                :message="listError('notification_emails')"
                            />
                        </div>
                        <div class="grid gap-1.5">
                            <Label for="budget-options">
                                Opțiuni de buget din formular
                            </Label>
                            <Textarea
                                id="budget-options"
                                v-model="budgetsText"
                                :rows="6"
                                :aria-invalid="!!listError('budget_options')"
                            />
                            <p class="text-xs text-muted-foreground">
                                Câte o opțiune pe rând, ex. „sub 5.000 lei”.
                            </p>
                            <InputError
                                :message="listError('budget_options')"
                            />
                        </div>
                        <SwitchField
                            v-model="leadsForm.send_confirmation"
                            label="Trimite vizitatorului un email de confirmare"
                        />
                    </section>

                    <div class="flex justify-end">
                        <Button type="submit" :disabled="leadsForm.processing">
                            Salvează
                        </Button>
                    </div>
                </form>
            </TabsContent>
        </Tabs>
    </div>
</template>
