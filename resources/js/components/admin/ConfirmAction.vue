<script setup lang="ts">
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog';
import { buttonVariants } from '@/components/ui/button';

/**
 * Asks before doing something destructive. The trigger goes in the default
 * slot; `confirm` fires only when the user agrees.
 */
withDefaults(
    defineProps<{
        title: string;
        description?: string;
        confirmLabel?: string;
    }>(),
    { confirmLabel: 'Șterge', description: undefined },
);

const emit = defineEmits<{ confirm: [] }>();
</script>

<template>
    <AlertDialog>
        <AlertDialogTrigger as-child>
            <slot />
        </AlertDialogTrigger>
        <AlertDialogContent>
            <AlertDialogHeader>
                <AlertDialogTitle>{{ title }}</AlertDialogTitle>
                <AlertDialogDescription v-if="description">
                    {{ description }}
                </AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter>
                <AlertDialogCancel>Renunță</AlertDialogCancel>
                <AlertDialogAction
                    :class="buttonVariants({ variant: 'destructive' })"
                    @click="emit('confirm')"
                >
                    {{ confirmLabel }}
                </AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>
