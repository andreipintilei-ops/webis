<script setup lang="ts">
import { useId } from 'vue';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';

defineProps<{
    label: string;
    hint?: string;
}>();

const model = defineModel<boolean>({ default: false });
const id = useId();
</script>

<template>
    <div class="flex items-start gap-3">
        <Switch :id="id" v-model="model" class="mt-0.5" />
        <div class="grid gap-0.5">
            <Label :for="id">{{ label }}</Label>
            <p v-if="hint" class="text-xs text-muted-foreground">{{ hint }}</p>
        </div>
    </div>
</template>
