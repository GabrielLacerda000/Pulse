<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Pencil, Plus } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import LeadsPagination from '@/components/LeadsPagination.vue';
import MessageTemplateDeleteDialog from '@/components/message-templates/MessageTemplateDeleteDialog.vue';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';
import { create, edit, index } from '@/routes/message-templates';
import type { MessageTemplate, Paginated } from '@/types';

const props = defineProps<{
    messageTemplates: Paginated<MessageTemplate>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Modelos de mensagem', href: index() },
        ],
    },
});

function goToPage(page: number) {
    router.get(
        index.url(),
        { page },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}
</script>

<template>
    <Head title="Modelos de mensagem" />

    <div class="flex flex-col gap-6 p-6">
        <div class="flex items-center justify-between">
            <Heading
                title="Modelos de mensagem"
                description="Gerencie seus modelos de mensagem de WhatsApp"
            />

            <Button as-child class="rounded-full">
                <Link :href="create()">
                    <Plus class="size-4" />
                    Novo modelo
                </Link>
            </Button>
        </div>

        <div class="overflow-x-auto rounded-xl border border-border">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr
                        class="border-b border-border bg-muted text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                    >
                        <th class="px-4 py-3 font-semibold">Nome</th>
                        <th class="px-4 py-3 font-semibold">Mensagem</th>
                        <th class="px-4 py-3 font-semibold">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-if="props.messageTemplates.data.length === 0"
                        class="border-b border-border/60 last:border-b-0"
                    >
                        <td
                            colspan="3"
                            class="px-4 py-8 text-center text-muted-foreground"
                        >
                            Nenhum modelo de mensagem cadastrado.
                        </td>
                    </tr>
                    <tr
                        v-for="messageTemplate in props.messageTemplates.data"
                        :key="messageTemplate.id"
                        class="border-b border-border/60 last:border-b-0"
                    >
                        <td class="px-4 py-3 font-medium">
                            {{ messageTemplate.name }}
                        </td>
                        <td
                            class="max-w-md truncate px-4 py-3 text-muted-foreground"
                        >
                            {{ messageTemplate.content }}
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-1">
                                <Button
                                    as-child
                                    variant="ghost"
                                    size="icon-sm"
                                    title="Editar"
                                >
                                    <Link :href="edit(messageTemplate.id)">
                                        <Pencil class="size-4" />
                                    </Link>
                                </Button>
                                <MessageTemplateDeleteDialog
                                    :message-template="messageTemplate"
                                />
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <LeadsPagination
            :current-page="props.messageTemplates.current_page"
            :last-page="props.messageTemplates.last_page"
            @change="goToPage"
        />
    </div>
</template>
