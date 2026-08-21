<?php

use App\Actions\RenderMessageTemplateAction;
use App\Models\Lead;
use App\Models\MessageTemplate;

beforeEach(function () {
    $this->action = new RenderMessageTemplateAction;
});

test('substitutes {nome} and {cidade} with the lead company name and city', function () {
    $lead = new Lead(['company_name' => 'Padaria do João', 'city' => 'Recife']);
    $template = new MessageTemplate(['content' => 'Olá {nome}, tudo bem em {cidade}?']);

    expect($this->action->execute($template, $lead))
        ->toBe('Olá Padaria do João, tudo bem em Recife?');
});

test('a variable with no corresponding value on the lead resolves to an empty string', function () {
    $lead = new Lead(['company_name' => 'Padaria do João', 'city' => null]);
    $template = new MessageTemplate(['content' => 'Cidade: {cidade}.']);

    expect($this->action->execute($template, $lead))->toBe('Cidade: .');
});

test('text with no variables is left unchanged', function () {
    $lead = new Lead(['company_name' => 'Padaria do João']);
    $template = new MessageTemplate(['content' => 'Olá, tudo bem?']);

    expect($this->action->execute($template, $lead))->toBe('Olá, tudo bem?');
});

test('multiple occurrences of the same variable are all substituted', function () {
    $lead = new Lead(['company_name' => 'Acme']);
    $template = new MessageTemplate(['content' => '{nome}, {nome}, {nome}!']);

    expect($this->action->execute($template, $lead))->toBe('Acme, Acme, Acme!');
});

test('an unknown variable resolves to an empty string instead of a literal placeholder', function () {
    $lead = new Lead(['company_name' => 'Acme']);
    $template = new MessageTemplate(['content' => 'Olá {desconhecida}!']);

    expect($this->action->execute($template, $lead))->toBe('Olá !');
});
