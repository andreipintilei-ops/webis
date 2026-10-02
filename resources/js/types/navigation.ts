import type { InertiaLinkProps } from '@inertiajs/vue3';
import type { LucideIcon } from '@lucide/vue';

export type BreadcrumbItem = {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
};

export type NavItem = {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon;
    isActive?: boolean;
    /** Highlight only on this exact URL, not on its sub-pages (e.g. the dashboard at /admin). */
    exact?: boolean;
    /** A count shown beside the item, hidden when 0. */
    badge?: number;
};

export type NavGroup = {
    label?: string;
    items: NavItem[];
};
