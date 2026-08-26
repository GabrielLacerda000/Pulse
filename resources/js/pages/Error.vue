<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { FileQuestion, Lock, ServerCrash, TriangleAlert } from '@lucide/vue';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';

const props = defineProps<{
    status: number;
}>();

const content = computed(() => {
    return (
        {
            403: {
                icon: Lock,
                title: 'Acesso negado',
                description: 'Você não tem permissão para acessar esta página.',
            },
            404: {
                icon: FileQuestion,
                title: 'Página não encontrada',
                description: 'A página que você procura não existe ou foi movida.',
            },
            419: {
                icon: TriangleAlert,
                title: 'Sessão expirada',
                description: 'Sua sessão expirou. Tente novamente.',
            },
            500: {
                icon: ServerCrash,
                title: 'Algo deu errado',
                description:
                    'Ocorreu um erro inesperado no servidor. Nossa equipe já foi notificada, tente novamente em instantes.',
            },
            503: {
                icon: ServerCrash,
                title: 'Manutenção em andamento',
                description: 'Estamos em manutenção rápida. Volte em alguns minutos.',
            },
        }[props.status] ?? {
            icon: TriangleAlert,
            title: 'Algo deu errado',
            description: 'Ocorreu um erro inesperado. Tente novamente em instantes.',
        }
    );
});
</script>

<template>
    <Head :title="content.title" />

    <div
        class="flex min-h-screen flex-col items-center justify-center gap-8 bg-background p-6 text-center text-foreground"
    >
        <div class="flex flex-col items-center gap-6">
            <div
                class="flex size-16 items-center justify-center rounded-full bg-muted"
            >
                <component :is="content.icon" class="size-8 text-muted-foreground" />
            </div>

            <div class="flex max-w-md flex-col gap-2">
                <h1 class="text-2xl font-semibold tracking-tight">
                    {{ content.title }}
                </h1>
                <p class="text-balance text-muted-foreground">
                    {{ content.description }}
                </p>
            </div>
        </div>

        <Button as-child size="lg" class="rounded-full">
            <Link :href="dashboard()">Voltar para o início</Link>
        </Button>
    </div>
</template>
