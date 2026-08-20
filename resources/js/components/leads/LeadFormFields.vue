<script setup lang="ts">
import { vMaska } from 'maska/vue';
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { usePhoneMask } from '@/composables/usePhoneMask';
import type { Lead } from '@/types';

const { phoneMaskOptions, formatPhoneDisplay } = usePhoneMask();

type Errors = Partial<
    Record<
        | 'company_name'
        | 'category'
        | 'phone'
        | 'email'
        | 'website'
        | 'instagram'
        | 'city'
        | 'state'
        | 'notes',
        string
    >
>;

withDefaults(
    defineProps<{
        lead?: Partial<Lead>;
        errors?: Errors;
    }>(),
    {
        lead: () => ({}),
        errors: () => ({}),
    },
);
</script>

<template>
    <div class="grid gap-2">
        <Label for="company_name">Nome da empresa</Label>
        <Input
            id="company_name"
            name="company_name"
            :default-value="lead.company_name ?? ''"
            required
            placeholder="Clínica Exemplo"
        />
        <InputError :message="errors.company_name" />
    </div>

    <div class="grid gap-2">
        <Label for="category">Categoria</Label>
        <Input
            id="category"
            name="category"
            :default-value="lead.category ?? ''"
            placeholder="Clínica odontológica"
        />
        <InputError :message="errors.category" />
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div class="grid gap-2">
            <Label for="phone">Telefone</Label>
            <Input
                id="phone"
                name="phone"
                v-maska="phoneMaskOptions"
                :default-value="formatPhoneDisplay(lead.phone)"
                placeholder="(81) 99999-9999"
            />
            <InputError :message="errors.phone" />
        </div>

        <div class="grid gap-2">
            <Label for="email">E-mail</Label>
            <Input
                id="email"
                type="email"
                name="email"
                :default-value="lead.email ?? ''"
                placeholder="contato@exemplo.com.br"
            />
            <InputError :message="errors.email" />
        </div>
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div class="grid gap-2">
            <Label for="website">Site</Label>
            <Input
                id="website"
                name="website"
                :default-value="lead.website ?? ''"
                placeholder="exemplo.com.br"
            />
            <InputError :message="errors.website" />
        </div>

        <div class="grid gap-2">
            <Label for="instagram">Instagram</Label>
            <Input
                id="instagram"
                name="instagram"
                :default-value="lead.instagram ?? ''"
                placeholder="@clinicaexemplo"
            />
            <InputError :message="errors.instagram" />
        </div>
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div class="grid gap-2">
            <Label for="city">Cidade</Label>
            <Input
                id="city"
                name="city"
                :default-value="lead.city ?? ''"
                placeholder="Recife"
            />
            <InputError :message="errors.city" />
        </div>

        <div class="grid gap-2">
            <Label for="state">Estado</Label>
            <Input
                id="state"
                name="state"
                :default-value="lead.state ?? ''"
                placeholder="PE"
            />
            <InputError :message="errors.state" />
        </div>
    </div>

    <div class="grid gap-2">
        <Label for="notes">Notas</Label>
        <Textarea
            id="notes"
            name="notes"
            :default-value="lead.notes ?? ''"
            placeholder="Observações sobre o prospect..."
        />
        <InputError :message="errors.notes" />
    </div>
</template>
