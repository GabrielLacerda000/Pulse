<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    Kanban,
    LayoutGrid,
    Megaphone,
    MessageCircle,
    MessageSquareText,
    Users,
} from '@lucide/vue';
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
import { dashboard } from '@/routes';
import { index as campaignsIndex } from '@/routes/campaigns';
import { index as leadsIndex } from '@/routes/leads';
import { index as messageTemplatesIndex } from '@/routes/message-templates';
import { index as pipelineIndex } from '@/routes/pipeline';
import { show as whatsappShow } from '@/routes/whatsapp';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Leads',
        href: leadsIndex(),
        icon: Users,
    },
    {
        title: 'Pipeline',
        href: pipelineIndex(),
        icon: Kanban,
    },
    {
        title: 'Modelos de mensagem',
        href: messageTemplatesIndex(),
        icon: MessageSquareText,
    },
    {
        title: 'Campanhas',
        href: campaignsIndex(),
        icon: Megaphone,
    },
    {
        title: 'WhatsApp',
        href: whatsappShow(),
        icon: MessageCircle,
    },
];

const footerNavItems: NavItem[] = [
    // {
    //     title: 'Repository',
    //     href: 'https://github.com/laravel/vue-starter-kit',
    //     icon: FolderGit2,
    // },
    // {
    //     title: 'Documentation',
    //     href: 'https://laravel.com/docs/starter-kits#vue',
    //     icon: BookOpen,
    // },
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
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
