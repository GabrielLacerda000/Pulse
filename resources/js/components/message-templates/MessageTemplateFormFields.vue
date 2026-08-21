<script setup lang="ts">
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import type { MessageTemplate } from '@/types';

const SAMPLE_VARIABLES: Record<string, string> = {
    nome: 'Clínica Exemplo',
    cidade: 'Recife',
};

type Errors = Partial<Record<'name' | 'content', string>>;

const props = withDefaults(
    defineProps<{
        messageTemplate?: Partial<MessageTemplate>;
        errors?: Errors;
    }>(),
    {
        messageTemplate: () => ({}),
        errors: () => ({}),
    },
);

const content = ref(props.messageTemplate.content ?? '');

const preview = computed(() =>
    content.value.replace(
        /\{(\w+)\}/g,
        (match, variable: string) => SAMPLE_VARIABLES[variable] ?? '',
    ),
);
</script>

<template>
    <div class="grid gap-2">
        <Label for="name">Nome do modelo</Label>
        <Input
            id="name"
            name="name"
            :default-value="messageTemplate.name ?? ''"
            required
            placeholder="Primeiro contato"
        />
        <InputError :message="errors.name" />
    </div>

    <div class="grid gap-2">
        <Label for="content">Mensagem</Label>
        <Textarea
            id="content"
            name="content"
            v-model="content"
            required
            placeholder="Olá {nome}, tudo bem? Vi que sua empresa em {cidade}..."
            class="min-h-32"
        />
        <p class="text-sm text-muted-foreground">
            Use <code>{nome}</code> e <code>{cidade}</code> para inserir os
            dados do lead automaticamente.
        </p>
        <InputError :message="errors.content" />
    </div>

    <div class="grid gap-2">
        <Label>Preview (dados fictícios de exemplo)</Label>
        <div
            class="min-h-16 rounded-md border border-border bg-muted px-3 py-2 text-sm whitespace-pre-wrap text-foreground"
        >
            {{ preview || 'A prévia da mensagem aparece aqui.' }}
        </div>
    </div>
</template>
