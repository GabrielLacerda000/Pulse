<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { dashboard, login, register } from '@/routes';
import { Button } from '@/components/ui/button';
</script>

<template>
    <Head title="Bem-vindo" />
    <div
        class="flex min-h-screen flex-col items-center justify-center bg-background p-6 text-foreground"
    >
        <div class="flex flex-col items-center gap-8 text-center">
            <!-- <div class="flex items-center gap-2">
                <div
                    class="flex aspect-square size-10 items-center justify-center rounded-md bg-sidebar-primary text-sidebar-primary-foreground"
                >
                    <AppLogoIcon
                        class="size-6 fill-current text-white dark:text-black"
                    />
                </div>
                <span class="text-lg font-semibold">{{
                    $page.props.name
                }}</span>
            </div> -->

            <div class="flex max-w-xl flex-col gap-3">
                <h1 class="text-3xl font-semibold tracking-tight sm:text-4xl">
                    Seu pipeline comercial, organizado.
                </h1>
                <p class="text-balance text-muted-foreground">
                    Gerencie leads, acompanhe oportunidades e mantenha seu
                    processo de vendas sob controle.
                </p>
            </div>

            <div class="flex flex-col items-center gap-4">
                <div class="flex items-center gap-3">
                    <Button v-if="$page.props.auth.user" as-child size="lg">
                        <Link :href="dashboard()">Ir para o painel</Link>
                    </Button>
                    <template v-else>
                        <Button as-child size="lg">
                            <Link :href="login()">Entrar</Link>
                        </Button>
                        <Button as-child variant="outline" size="lg">
                            <Link :href="register()">Criar conta</Link>
                        </Button>
                    </template>
                </div>
            </div>
        </div>
    </div>
</template>
