<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import { ref } from 'vue';
import { toast } from 'vue-sonner';
import ConfirmAction from '@/components/admin/ConfirmAction.vue';
import SelectField from '@/components/admin/fields/SelectField.vue';
import TextField from '@/components/admin/fields/TextField.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { destroy, index, sendReset, store, update } from '@/routes/admin/users';
import type { Option } from '@/types/content';

type UserRow = {
    id: number;
    name: string;
    email: string;
    role: string;
    role_label: string;
    two_factor: boolean;
    created_at: string | null;
    is_me: boolean;
};

const props = defineProps<{
    users: UserRow[];
    roles: Option[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Utilizatori', href: index() }],
    },
});

const roleHint =
    'Administrator: tot, inclusiv setări, utilizatori și redirecționări. Editor: conținut, media și cereri.';

// New accounts default to the least powerful role.
const defaultRole =
    props.roles.find((role) => role.value === 'editor')?.value ??
    props.roles[0]?.value ??
    '';

const open = ref(false);
const editing = ref<UserRow | null>(null);

const form = useForm({
    name: '',
    email: '',
    role: defaultRole,
});

function openNew(): void {
    editing.value = null;
    form.name = '';
    form.email = '';
    form.role = defaultRole;
    form.clearErrors();
    open.value = true;
}

function openEdit(user: UserRow): void {
    editing.value = user;
    form.name = user.name;
    form.email = user.email;
    form.role = user.role;
    form.clearErrors();
    open.value = true;
}

function close(): void {
    open.value = false;
    editing.value = null;
    form.reset();
    form.clearErrors();
}

function submit(): void {
    const options = { preserveScroll: true, onSuccess: close };

    if (editing.value) {
        form.put(update.url(editing.value.id), options);
    } else {
        form.post(store.url(), options);
    }
}

function resetPassword(user: UserRow): void {
    router.post(sendReset.url(user.id), {}, { preserveScroll: true });
}

function remove(user: UserRow): void {
    router.delete(destroy.url(user.id), {
        preserveScroll: true,
        onError: (errors) => {
            if (errors.user) {
                toast.error(errors.user);
            }
        },
    });
}
</script>

<template>
    <Head title="Utilizatori" />

    <div class="flex flex-col gap-4 p-4">
        <div class="flex items-center justify-between gap-3">
            <h1 class="text-2xl font-bold tracking-tight">Utilizatori</h1>
            <Button type="button" @click="openNew">
                <Plus /> Utilizator nou
            </Button>
        </div>

        <p class="text-sm text-muted-foreground">
            Conturile nu se pot crea din site. Un cont nou primește pe email
            linkul pentru setarea parolei.
        </p>

        <div class="overflow-x-auto rounded-xl border">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left">
                    <tr>
                        <th class="px-4 py-2">Nume</th>
                        <th class="px-4 py-2">Email</th>
                        <th class="px-4 py-2">Rol</th>
                        <th class="px-4 py-2">2FA</th>
                        <th class="px-4 py-2">Creat</th>
                        <th class="px-4 py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="user in users" :key="user.id" class="border-t">
                        <td class="px-4 py-2">
                            <button
                                type="button"
                                class="font-medium hover:underline"
                                @click="openEdit(user)"
                            >
                                {{ user.name }}
                            </button>
                            <span
                                v-if="user.is_me"
                                class="ml-1 text-muted-foreground"
                                >(tu)</span
                            >
                        </td>
                        <td class="px-4 py-2 text-muted-foreground">
                            {{ user.email }}
                        </td>
                        <td class="px-4 py-2">
                            <Badge
                                :variant="
                                    user.role === 'admin'
                                        ? 'default'
                                        : 'secondary'
                                "
                                >{{ user.role_label }}</Badge
                            >
                        </td>
                        <td class="px-4 py-2">
                            <span v-if="user.two_factor" aria-label="Activat"
                                >✓</span
                            >
                            <span v-else class="text-muted-foreground">
                                — <span class="text-xs">neactivat</span>
                            </span>
                        </td>
                        <td class="px-4 py-2 text-muted-foreground">
                            {{ user.created_at }}
                        </td>
                        <td class="px-4 py-2 text-right whitespace-nowrap">
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                @click="openEdit(user)"
                            >
                                Editează
                            </Button>
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                @click="resetPassword(user)"
                            >
                                Trimite resetare parolă
                            </Button>
                            <ConfirmAction
                                v-if="!user.is_me"
                                title="Ștergi utilizatorul?"
                                :description="`${user.name} nu se va mai putea autentifica.`"
                                @confirm="remove(user)"
                            >
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    class="text-destructive"
                                >
                                    Șterge
                                </Button>
                            </ConfirmAction>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <Sheet :open="open" @update:open="(value) => !value && close()">
        <SheetContent class="w-full overflow-y-auto sm:max-w-md">
            <SheetHeader>
                <SheetTitle>
                    {{ editing ? 'Editează utilizatorul' : 'Utilizator nou' }}
                </SheetTitle>
                <SheetDescription>
                    {{
                        editing
                            ? editing.email
                            : 'Va primi pe email linkul pentru setarea parolei.'
                    }}
                </SheetDescription>
            </SheetHeader>

            <form
                :key="editing?.id ?? 'new'"
                class="flex flex-col gap-4 px-4 pb-6"
                @submit.prevent="submit"
            >
                <TextField
                    v-model="form.name"
                    label="Nume"
                    :max="255"
                    required
                    :error="form.errors.name"
                />
                <div v-if="editing" class="grid gap-1.5">
                    <Label>Email</Label>
                    <p class="text-sm text-muted-foreground">
                        {{ editing.email }}
                    </p>
                </div>
                <TextField
                    v-else
                    v-model="form.email"
                    label="Email"
                    type="email"
                    required
                    :error="form.errors.email"
                />
                <SelectField
                    v-model="form.role"
                    label="Rol"
                    :options="roles"
                    :hint="roleHint"
                    :error="form.errors.role"
                />

                <div class="flex justify-end gap-2">
                    <Button type="button" variant="ghost" @click="close">
                        Renunță
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        {{ editing ? 'Salvează' : 'Creează' }}
                    </Button>
                </div>
            </form>
        </SheetContent>
    </Sheet>
</template>
