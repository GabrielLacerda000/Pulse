<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { AlertTriangle, LogIn, LogOut } from '@lucide/vue';
import QrcodeVue from 'qrcode.vue';
import { onUnmounted } from 'vue';
import Heading from '@/components/Heading.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import type { BadgeVariants } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { dashboard } from '@/routes';
import { connect, disconnect, show } from '@/routes/whatsapp';
import type { WhatsAppConnection } from '@/types';

const props = defineProps<{
    connection: WhatsAppConnection | null;
    error: string | null;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'WhatsApp', href: show() },
        ],
    },
});

const statusLabels: Record<WhatsAppConnection['status'], string> = {
    disconnected: 'Desconectado',
    connecting: 'Conectando...',
    qr_required: 'Aguardando leitura do QR Code',
    connected: 'Conectado',
    logged_out: 'Sessão encerrada',
};

const statusVariants: Record<
    WhatsAppConnection['status'],
    NonNullable<BadgeVariants['variant']>
> = {
    disconnected: 'secondary',
    connecting: 'secondary',
    qr_required: 'outline',
    connected: 'default',
    logged_out: 'destructive',
};

const POLL_STATUSES: WhatsAppConnection['status'][] = [
    'connecting',
    'qr_required',
];

let pollTimer: ReturnType<typeof setTimeout> | null = null;

function schedulePoll() {
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

schedulePoll();

onUnmounted(() => {
    if (pollTimer) {
        clearTimeout(pollTimer);
    }
});

function handleConnect() {
    router.post(connect.url(), {}, { preserveScroll: true });
}

function handleDisconnect() {
    router.post(disconnect.url(), {}, { preserveScroll: true });
}
</script>

<template>
    <Head title="WhatsApp" />

    <div class="flex flex-col gap-6 p-6">
        <Heading
            title="WhatsApp"
            description="Conecte o número de WhatsApp que enviará suas mensagens"
        />

        <Alert v-if="props.error" variant="destructive" class="max-w-xl">
            <AlertTriangle />
            <AlertTitle>Não foi possível falar com o WhatsApp</AlertTitle>
            <AlertDescription>{{ props.error }}</AlertDescription>
        </Alert>

        <Card v-else-if="props.connection" class="max-w-xl">
            <CardHeader class="flex flex-row items-center justify-between">
                <CardTitle>Status da conexão</CardTitle>
                <Badge :variant="statusVariants[props.connection.status]">
                    {{ statusLabels[props.connection.status] }}
                </Badge>
            </CardHeader>
            <CardContent class="flex flex-col gap-4">
                <p
                    v-if="props.connection.status === 'connected'"
                    class="text-sm text-muted-foreground"
                >
                    Número conectado:
                    <span class="font-medium text-foreground">{{
                        props.connection.phone
                    }}</span>
                </p>

                <Alert v-if="props.connection.status === 'logged_out'">
                    <AlertTriangle />
                    <AlertTitle>Sessão encerrada no celular</AlertTitle>
                    <AlertDescription>
                        A conexão foi encerrada de fato (ex: número removido no
                        WhatsApp do celular). Conecte novamente e escaneie o
                        QR Code para reautenticar.
                    </AlertDescription>
                </Alert>

                <p
                    v-else-if="props.connection.status === 'disconnected'"
                    class="text-sm text-muted-foreground"
                >
                    Nenhuma conexão ativa no momento.
                </p>

                <div
                    v-if="props.connection.status === 'qr_required' && props.connection.qr"
                    class="flex flex-col items-center gap-2 rounded-lg border border-border bg-muted/40 p-6"
                >
                    <QrcodeVue :value="props.connection.qr" :size="220" />
                    <p class="text-center text-sm text-muted-foreground">
                        Abra o WhatsApp no celular, acesse Aparelhos conectados
                        e escaneie o código acima.
                    </p>
                </div>

                <div class="flex gap-2">
                    <Button
                        v-if="
                            props.connection.status === 'disconnected' ||
                            props.connection.status === 'logged_out'
                        "
                        class="rounded-full"
                        @click="handleConnect"
                    >
                        <LogIn class="size-4" />
                        Conectar
                    </Button>
                    <Button
                        v-if="
                            props.connection.status === 'connected' ||
                            props.connection.status === 'qr_required' ||
                            props.connection.status === 'connecting'
                        "
                        variant="outline"
                        class="rounded-full"
                        @click="handleDisconnect"
                    >
                        <LogOut class="size-4" />
                        Desconectar
                    </Button>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
