<script setup lang="ts">
import { Form, Head, router } from '@inertiajs/vue3';
import { AlertTriangle, LogIn, LogOut, MessageCircle } from '@lucide/vue';
import QrcodeVue from 'qrcode.vue';
import { computed, onUnmounted, watch } from 'vue';
import Heading from '@/components/Heading.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Spinner } from '@/components/ui/spinner';
import { usePhoneMask } from '@/composables/usePhoneMask';
import { dashboard } from '@/routes';
import { connect, disconnect, show } from '@/routes/whatsapp';
import type { WhatsAppConnection, WhatsAppConnectionStatus } from '@/types';

const props = defineProps<{
    connection: WhatsAppConnection | null;
    error: string | null;
}>();

const { formatPhoneDisplay } = usePhoneMask();

const formattedPhone = computed(() => {
    if (!props.connection?.phone) {
        return '';
    }

    return `+55 ${formatPhoneDisplay(props.connection.phone)}`;
});

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'WhatsApp', href: show() },
        ],
    },
});

const statusLabels: Record<WhatsAppConnectionStatus, string> = {
    disconnected: 'Desconectado',
    connecting: 'Conectando...',
    qr_required: 'Aguardando leitura do QR Code',
    connected: 'Conectado',
    logged_out: 'Sessão encerrada',
};

const statusDotClasses: Record<WhatsAppConnectionStatus, string> = {
    connected: 'bg-green-500',
    connecting: 'bg-yellow-500',
    qr_required: 'bg-blue-500',
    disconnected: 'bg-muted-foreground',
    logged_out: 'bg-red-500',
};

const cardTitle = computed(() => {
    if (!props.connection) {
        return 'WhatsApp';
    }

    return props.connection.status === 'qr_required' ||
        props.connection.status === 'connecting'
        ? 'Conectar WhatsApp'
        : 'WhatsApp';
});

const POLL_STATUSES: WhatsAppConnection['status'][] = [
    'connecting',
    'qr_required',
];

let pollTimer: ReturnType<typeof setTimeout> | null = null;

function clearPoll() {
    if (pollTimer) {
        clearTimeout(pollTimer);
        pollTimer = null;
    }
}

function schedulePoll() {
    clearPoll();

    if (!props.connection || !POLL_STATUSES.includes(props.connection.status)) {
        return;
    }

    pollTimer = setTimeout(() => {
        router.reload({
            only: ['connection', 'error'],
            onFinish: schedulePoll,
        });
    }, 3000);
}

watch(
    () => props.connection?.status,
    () => schedulePoll(),
    { immediate: true },
);

onUnmounted(clearPoll);
</script>

<template>
    <Head title="WhatsApp" />

    <div class="flex flex-col gap-6 p-6">
        <Heading
            title="WhatsApp"
            description="Conecte e gerencie o número usado para suas campanhas."
        />

        <Alert v-if="props.error" variant="destructive" class="max-w-xl">
            <AlertTriangle />
            <AlertTitle>Não foi possível falar com o WhatsApp</AlertTitle>
            <AlertDescription>{{ props.error }}</AlertDescription>
        </Alert>

        <template v-else-if="props.connection">
            <Card class="max-w-xl">
                <CardHeader class="flex flex-row items-center justify-between">
                    <CardTitle>{{ cardTitle }}</CardTitle>
                    <div class="flex items-center gap-2">
                        <span class="relative flex size-2.5">
                            <span
                                v-if="props.connection.status === 'connected'"
                                class="absolute inline-flex size-full animate-ping rounded-full bg-green-500 opacity-75"
                            />
                            <span
                                class="relative inline-flex size-2.5 rounded-full"
                                :class="
                                    statusDotClasses[props.connection.status]
                                "
                            />
                        </span>
                        <span
                            class="text-sm font-medium text-muted-foreground uppercase"
                        >
                            {{ statusLabels[props.connection.status] }}
                        </span>
                    </div>
                </CardHeader>
                <CardContent class="flex flex-col gap-4">
                    <!-- Conectado -->
                    <template v-if="props.connection.status === 'connected'">
                        <div class="flex items-center gap-3">
                            <span class="relative flex size-3">
                                <span
                                    class="absolute inline-flex size-full animate-ping rounded-full bg-green-500 opacity-75"
                                />
                                <span
                                    class="relative inline-flex size-3 rounded-full bg-green-500"
                                />
                            </span>
                            <div>
                                <p class="text-sm font-medium text-foreground">
                                    {{ formattedPhone }}
                                </p>
                                <p class="text-sm text-muted-foreground">
                                    Número conectado
                                </p>
                            </div>
                        </div>

                        <p class="text-sm text-muted-foreground">
                            Conectado e pronto para enviar mensagens.
                        </p>

                        <div class="flex justify-end">
                            <Form
                                v-bind="disconnect.form()"
                                :options="{ preserveScroll: true }"
                                v-slot="{ processing }"
                            >
                                <Button
                                    variant="outline"
                                    class="rounded-full"
                                    :disabled="processing"
                                >
                                    <Spinner v-if="processing" />
                                    <LogOut v-else class="size-4" />
                                    Desconectar
                                </Button>
                            </Form>
                        </div>
                    </template>

                    <!-- Desconectado / Sessão encerrada -->
                    <template
                        v-else-if="
                            props.connection.status === 'disconnected' ||
                            props.connection.status === 'logged_out'
                        "
                    >
                        <Alert v-if="props.connection.status === 'logged_out'">
                            <AlertTriangle />
                            <AlertTitle>Sessão encerrada no celular</AlertTitle>
                            <AlertDescription>
                                A conexão foi encerrada de fato (ex: número
                                removido no WhatsApp do celular). Conecte
                                novamente e escaneie o QR Code para
                                reautenticar.
                            </AlertDescription>
                        </Alert>

                        <div
                            class="flex flex-col items-center gap-3 py-6 text-center"
                        >
                            <div
                                class="flex size-16 items-center justify-center rounded-full bg-muted"
                            >
                                <MessageCircle
                                    class="size-8 text-muted-foreground"
                                />
                            </div>
                            <p class="text-sm font-medium text-foreground">
                                WhatsApp não conectado
                            </p>
                            <p class="max-w-xs text-sm text-muted-foreground">
                                Conecte um número para começar a enviar
                                campanhas.
                            </p>
                        </div>

                        <div class="flex justify-center">
                            <Form
                                v-bind="connect.form()"
                                :options="{ preserveScroll: true }"
                                v-slot="{ processing }"
                            >
                                <Button
                                    class="rounded-full"
                                    :disabled="processing"
                                >
                                    <Spinner v-if="processing" />
                                    <LogIn v-else class="size-4" />
                                    Conectar WhatsApp
                                </Button>
                            </Form>
                        </div>
                    </template>

                    <!-- QR Code / conectando -->
                    <template v-else>
                        <div
                            class="flex flex-col items-center gap-4 rounded-lg border border-border bg-muted/40 p-6"
                        >
                            <QrcodeVue
                                v-if="props.connection.qr"
                                :value="props.connection.qr"
                                :size="220"
                            />
                            <Spinner v-else class="size-6" />

                            <p
                                class="text-center text-sm font-medium text-foreground"
                            >
                                {{
                                    props.connection.qr
                                        ? 'Escaneie o código com seu celular'
                                        : 'Gerando QR Code, aguarde...'
                                }}
                            </p>

                            <ol
                                v-if="props.connection.qr"
                                class="w-full max-w-xs list-decimal space-y-1 pl-5 text-sm text-muted-foreground"
                            >
                                <li>Abra o WhatsApp</li>
                                <li>Acesse Aparelhos conectados</li>
                                <li>Toque em Conectar aparelho</li>
                                <li>Escaneie este código</li>
                            </ol>
                        </div>

                        <div class="flex justify-center">
                            <Form
                                v-bind="disconnect.form()"
                                :options="{ preserveScroll: true }"
                                v-slot="{ processing }"
                            >
                                <Button
                                    variant="outline"
                                    class="rounded-full"
                                    :disabled="processing"
                                >
                                    <Spinner v-if="processing" />
                                    Cancelar
                                </Button>
                            </Form>
                        </div>
                    </template>
                </CardContent>
            </Card>

            <Card
                v-if="props.connection.status === 'connected'"
                class="max-w-xs"
            >
                <CardHeader>
                    <CardTitle class="text-base">Conexão</CardTitle>
                </CardHeader>
                <CardContent class="flex flex-col gap-2 text-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-muted-foreground">Status</span>
                        <span
                            class="flex items-center gap-1.5 font-medium text-foreground"
                        >
                            <span class="size-2 rounded-full bg-green-500" />
                            Conectado
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-muted-foreground">Número</span>
                        <span class="font-medium text-foreground">{{
                            formattedPhone
                        }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-muted-foreground">Atualização</span>
                        <span class="font-medium text-foreground">Agora</span>
                    </div>
                </CardContent>
            </Card>
        </template>
    </div>
</template>
