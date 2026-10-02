<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

defineProps<{
    prevUrl: string | null;
    nextUrl: string | null;
    current: number;
    last: number;
}>();
</script>

<template>
    <div
        v-if="last > 1"
        class="flex items-center justify-between text-sm text-muted-foreground"
    >
        <Link
            v-if="prevUrl"
            :href="prevUrl"
            class="hover:text-foreground hover:underline"
            preserve-scroll
            >← Anterior</Link
        >
        <span v-else></span>

        <span>Pagina {{ current }} din {{ last }}</span>

        <Link
            v-if="nextUrl"
            :href="nextUrl"
            class="hover:text-foreground hover:underline"
            preserve-scroll
            >Următor →</Link
        >
        <span v-else></span>
    </div>
</template>
