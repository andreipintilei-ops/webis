<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';

const page = usePage();
const name = page.props.name;
</script>

<template>
    <!-- The brand icon brings its own background; the drawn fallback mark
         sits on the sidebar's accent square. -->
    <AppLogoIcon v-if="page.props.appIcon" class="size-8 shrink-0" />
    <div
        v-else
        class="flex aspect-square size-8 items-center justify-center rounded-md bg-sidebar-primary text-sidebar-primary-foreground"
    >
        <AppLogoIcon class="size-5 fill-current text-white dark:text-black" />
    </div>
    <div class="ml-1 grid flex-1 text-left text-sm">
        <span class="mb-0.5 truncate leading-tight font-semibold">{{
            name
        }}</span>
    </div>
</template>
