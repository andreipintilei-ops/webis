<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    BriefcaseBusiness,
    Building2,
    ExternalLink,
    FileText,
    Images,
    Inbox,
    LayoutGrid,
    Newspaper,
    Quote,
    Settings,
    SquareArrowRight,
    TriangleAlert,
    Users,
} from '@lucide/vue';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { home } from '@/routes';
import { dashboard } from '@/routes/admin';
import * as clients from '@/routes/admin/clients';
import * as leads from '@/routes/admin/leads';
import * as media from '@/routes/admin/media';
import * as notFound from '@/routes/admin/not-found';
import * as pages from '@/routes/admin/pages';
import * as posts from '@/routes/admin/posts';
import * as projects from '@/routes/admin/projects';
import * as redirects from '@/routes/admin/redirects';
import * as settings from '@/routes/admin/settings';
import * as testimonials from '@/routes/admin/testimonials';
import * as users from '@/routes/admin/users';
import type { NavGroup, NavItem } from '@/types';

const page = usePage();

const groups = computed<NavGroup[]>(() => {
    const all: (NavGroup & { adminOnly?: boolean })[] = [
        {
            items: [
                {
                    title: 'Panou',
                    href: dashboard(),
                    icon: LayoutGrid,
                    exact: true,
                },
                {
                    title: 'Cereri',
                    href: leads.index(),
                    icon: Inbox,
                    badge: page.props.newLeads,
                },
            ],
        },
        {
            label: 'Conținut',
            items: [
                { title: 'Pagini', href: pages.index(), icon: FileText },
                {
                    title: 'Portofoliu',
                    href: projects.index(),
                    icon: BriefcaseBusiness,
                },
                { title: 'Blog', href: posts.index(), icon: Newspaper },
                {
                    title: 'Testimoniale',
                    href: testimonials.index(),
                    icon: Quote,
                },
                { title: 'Clienți', href: clients.index(), icon: Building2 },
                { title: 'Media', href: media.index(), icon: Images },
            ],
        },
        {
            label: 'SEO',
            adminOnly: true,
            items: [
                {
                    title: 'Redirecționări',
                    href: redirects.index(),
                    icon: SquareArrowRight,
                },
                {
                    title: 'Erori 404',
                    href: notFound.index(),
                    icon: TriangleAlert,
                },
            ],
        },
        {
            label: 'Administrare',
            adminOnly: true,
            items: [
                { title: 'Setări', href: settings.edit(), icon: Settings },
                { title: 'Utilizatori', href: users.index(), icon: Users },
            ],
        },
    ];

    return all.filter((group) => !group.adminOnly || page.props.auth.isAdmin);
});

const footerNavItems: NavItem[] = [
    {
        title: 'Vezi site-ul',
        href: home(),
        icon: ExternalLink,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain
                v-for="(group, index) in groups"
                :key="group.label ?? index"
                :label="group.label"
                :items="group.items"
            />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
