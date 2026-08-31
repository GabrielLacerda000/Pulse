<script setup lang="ts">
import { Plus, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import type { MessageTemplate } from '@/types';

defineProps<{
    messageTemplates: MessageTemplate[];
    error?: string;
}>();

const messages = defineModel<string[]>({ default: () => [''] });

const templateToCopy = ref<string>('');

function addMessage() {
    messages.value = [...messages.value, ''];
}

function removeMessage(index: number) {
    messages.value = messages.value.filter((_, i) => i !== index);
}

function copyTemplate(templateId: string, templates: MessageTemplate[]) {
    const template = templates.find(
        (candidate) => String(candidate.id) === templateId,
    );

    if (!template) {
        return;
    }

    messages.value = [...messages.value, template.content];
    templateToCopy.value = '';
}
</script>

<template>
    <div class="grid gap-2">
        <div class="flex items-center justify-between">
            <Label>Mensagens ({{ messages.length }})</Label>

            <div class="flex items-center gap-2">
                <Select
                    v-if="messageTemplates.length > 0"
                    :model-value="templateToCopy"
                    @update:model-value="
                        (value) => copyTemplate(String(value), messageTemplates)
                    "
                >
                    <SelectTrigger class="rounded-full" size="sm">
                        <SelectValue placeholder="Copiar modelo" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="template in messageTemplates"
                            :key="template.id"
                            :value="String(template.id)"
                        >
                            {{ template.name }}
                        </SelectItem>
                    </SelectContent>
                </Select>

                <Button
                    type="button"
                    variant="secondary"
                    size="sm"
                    class="rounded-full"
                    @click="addMessage"
                >
                    <Plus class="size-4" />
                    Nova mensagem
                </Button>
            </div>
        </div>

        <p class="text-sm text-muted-foreground">
            Copiar um modelo apenas preenche o texto inicial — editar a mensagem
            aqui não altera o modelo original.
        </p>

        <div
            v-for="(message, index) in messages"
            :key="index"
            class="flex items-start gap-2"
        >
            <Textarea
                v-model="messages[index]"
                name="messages[]"
                required
                placeholder="Olá {nome}, tudo bem?"
                class="min-h-24"
            />
            <Button
                v-if="messages.length > 1"
                type="button"
                variant="ghost"
                size="icon-sm"
                title="Remover mensagem"
                @click="removeMessage(index)"
            >
                <Trash2 class="size-4" />
            </Button>
        </div>

        <InputError :message="error" />
    </div>
</template>
