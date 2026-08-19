# Database Tables — Sistema de Gestão de Prospecção

Documento de referência para visualizar as tabelas da V1 e o propósito de cada uma.

> **Princípio:** começar com o menor modelo de dados que represente corretamente o domínio. Não criar tabelas para funcionalidades que ainda não existem.

## 1. leads

**Propósito:** tabela principal do sistema. Representa uma empresa/prospect e concentra os dados obtidos pela captação, cadastro manual e informações comerciais.

| Campo | Tipo sugerido | Propósito |
|---|---|---|
| id | bigint | Identificador único |
| company_name | string | Nome da empresa |
| category | string nullable | Categoria/segmento da empresa |
| phone | string nullable | Telefone normalizado |
| email | string nullable | E-mail |
| website | string nullable | Site da empresa |
| instagram | string nullable | Instagram |
| has_website | boolean nullable | Indica se possui site |
| has_instagram | boolean nullable | Indica se possui Instagram |
| city | string nullable | Cidade |
| state | string nullable | Estado/UF |
| status | enum/string | Etapa comercial do lead |
| type | string nullable | Classificação proveniente do sistema de captação |
| score | integer nullable | Score proveniente do sistema de captação |
| rating | decimal nullable | Avaliação no Google |
| reviews_count | integer nullable | Número de avaliações |
| source | string nullable | Origem do lead |
| google_maps_url | text nullable | Link do Google Maps |
| source_created_at | datetime nullable | Data de criação na fonte |
| notes | text nullable | Observações comerciais feitas pelo usuário |
| created_at | timestamp | Data de criação no sistema |
| updated_at | timestamp | Data da última atualização |

### Status

O campo `status` representa exclusivamente o estágio comercial:

| Valor | Descrição |
|---|---|
| new | Novo |
| contacted | Contatado |
| conversation | Em conversa |
| meeting | Reunião |
| proposal | Proposta |
| won | Ganho |
| lost | Perdido |

O status deverá utilizar um Enum no código.

## 2. Tabelas que NÃO são necessárias na V1

A V1 não precisa criar tabelas para tudo que pode existir futuramente.

| Tabela | Motivo para não criar agora |
|---|---|
| messages | Não haverá histórico de mensagens do WhatsApp na V1. O WhatsApp será aberto através de um link wa.me e a mensagem será enviada manualmente pelo usuário. |
| interactions | Embora futuramente seja útil registrar contatos, respostas, ligações, reuniões e follow-ups, na V1 o status e notes são suficientes. |
| follow_ups | Não haverá inicialmente um sistema de tarefas ou lembretes de follow-up. |
| lead_sources | A origem será inicialmente armazenada diretamente em `leads.source`. |
| pipeline_stages | As etapas são fixas na V1 e representadas pelo Enum `LeadStatus`. Não existe necessidade de uma tabela configurável. |

## 3. Visão geral da V1

O banco começa propositalmente pequeno:

```
┌──────────────────────────────┐
│            leads             │
├──────────────────────────────┤
│ id                           │
│ company_name                 │
│ category                     │
│ phone                        │
│ email                        │
│ website                      │
│ instagram                    │
│ has_website                  │
│ has_instagram                │
│ city                         │
│ state                        │
│ status                       │
│ type                         │
│ score                        │
│ rating                       │
│ reviews_count                │
│ source                       │
│ google_maps_url              │
│ source_created_at            │
│ notes                        │
│ created_at                   │
│ updated_at                   │
└──────────────────────────────┘
```

## 4. Evolução futura

Quando o sistema exigir mais controle comercial, o modelo poderá evoluir para:

```
users
  │
  ▼
leads
  │
  ├──────────► interactions
  │
  ├──────────► follow_ups
  │
  └──────────► messages
```

Essas relações não devem ser implementadas antes de existir uma necessidade concreta.

## 5. Decisões importantes

- O `status` fica em `leads` e representa onde o lead está no processo comercial.
- Dados como score, avaliação, número de avaliações, tipo, fonte e Google Maps são atributos do lead na V1.
- Notas comerciais ficam em `leads.notes`.
- O banco inicial será SQLite.
- As migrations devem evitar dependências específicas de SQLite que dificultem uma futura migração para PostgreSQL.

## 6. Modelo mínimo

Se precisarmos reduzir ainda mais a V1, o núcleo absoluto é:

**leads**

Todo o sistema — cadastro, importação, tabela, Kanban, WhatsApp e pipeline — pode funcionar inicialmente em torno dessa única tabela.

Adicionar uma tabela quando surgir uma nova responsabilidade real é preferível a criar uma estrutura complexa antecipadamente.
