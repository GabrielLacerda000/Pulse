# PRD — Pulse

## 1. Visão geral

Sistema interno para gerenciamento de leads e prospecção comercial.

O sistema receberá leads de duas formas:

1. Cadastro manual.
2. Importação de planilhas XLSX/CSV provenientes de um sistema externo de captação de leads.

O objetivo principal é permitir que os leads sejam organizados em um pipeline comercial e que o usuário consiga iniciar contatos pelo WhatsApp de forma rápida através de links `wa.me`.

O sistema **não terá integração direta com a API do WhatsApp na V1** e não realizará disparos automáticos. O WhatsApp será aberto com uma mensagem previamente preenchida, cabendo ao usuário revisar e enviar.

---

# 2. Objetivo

Centralizar o processo de prospecção em um único lugar:

```text
Captar leads
     ↓
Importar
     ↓
Organizar
     ↓
Analisar
     ↓
Contatar pelo WhatsApp
     ↓
Acompanhar respostas
     ↓
Gerenciar oportunidades
     ↓
Fechar venda
```

O sistema deve reduzir principalmente o trabalho operacional de:

* cadastrar leads;
* copiar números;
* procurar informações das empresas;
* abrir conversas no WhatsApp;
* lembrar em qual etapa cada lead está;
* acompanhar quais empresas já foram contatadas.

---

# 3. Escopo da V1

## 3.1 Gestão de leads

O sistema deverá permitir:

* criar lead manualmente;
* visualizar lead;
* editar lead;
* excluir lead;
* pesquisar leads;
* filtrar leads;
* alterar status;
* adicionar e editar notas.

### Dados básicos

Cada lead poderá possuir:

* Nome da empresa;
* Categoria;
* Telefone;
* E-mail;
* Site;
* Instagram;
* Cidade;
* Estado;
* Notas.

### Dados provenientes da captação

Quando o lead vier da planilha, também deverão ser preservados:

* Tem site;
* Tem Instagram;
* Tipo;
* Score;
* Avaliação;
* Número de avaliações;
* Fonte;
* Google Maps;
* Data de criação na fonte.

---

# 4. Status do lead

O status representa **a etapa comercial do lead**, não a qualidade ou presença digital da empresa.

Status da V1:

```text
new
contacted
conversation
meeting
proposal
won
lost
```

Representação na interface:

```text
Novo
Contatado
Em conversa
Reunião
Proposta
Ganho
Perdido
```

### Definição

**Novo**

Lead importado ou cadastrado, ainda não contatado.

**Contatado**

Uma abordagem foi realizada, mas ainda não houve resposta.

**Em conversa**

O prospect respondeu e existe uma conversa comercial em andamento.

**Reunião**

Existe uma reunião marcada ou em andamento.

**Proposta**

Uma proposta comercial foi apresentada.

**Ganho**

O lead se tornou cliente.

**Perdido**

A oportunidade foi encerrada sem venda.

---

# 5. Pipeline

O sistema terá uma visualização Kanban baseada no status.

Exemplo:

```text
┌──────────┐ ┌────────────┐ ┌──────────────┐
│ NOVO     │ │ CONTATADO  │ │ EM CONVERSA  │
├──────────┤ ├────────────┤ ├──────────────┤
│ Empresa A│ │ Empresa C  │ │ Empresa E    │
│ Empresa B│ │ Empresa D  │ │ Empresa F    │
└──────────┘ └────────────┘ └──────────────┘

┌──────────┐ ┌──────────┐ ┌────────┐
│ REUNIÃO  │ │ PROPOSTA │ │ GANHO  │
├──────────┤ ├──────────┤ ├────────┤
│ Empresa G│ │ Empresa H│ │ Empresa│
└──────────┘ └──────────┘ └────────┘
```

## Drag & Drop

O usuário poderá arrastar um lead entre as colunas.

Ao mover:

```text
Novo
↓
Em conversa
```

o frontend deverá atualizar o status no backend.

O sistema deverá utilizar uma biblioteca de drag & drop para Vue, como Vue.Draggable Next.

---

# 6. WhatsApp

Cada lead que possuir telefone deverá possuir uma ação:

**Abrir WhatsApp**

Ao clicar:

1. O sistema monta uma URL `wa.me`.
2. O número é normalizado para o formato internacional.
3. A mensagem é codificada com `encodeURIComponent`.
4. O WhatsApp Web ou aplicativo é aberto.
5. A mensagem aparece preenchida.
6. O usuário revisa e envia manualmente.

Exemplo conceitual:

```text
https://wa.me/55XXXXXXXXXXX?text=MENSAGEM
```

### Regra

O sistema **não envia automaticamente a mensagem**.

A V1 não utilizará:

* WhatsApp Cloud API;
* bots;
* automação de disparos;
* extensões de navegador;
* serviços externos de disparo.

---

# 7. Mensagens

A V1 poderá trabalhar inicialmente com uma mensagem padrão.

Exemplo:

```text
Olá, tudo bem?

Estava pesquisando algumas empresas da região e encontrei vocês.

Tive uma ideia que pode ajudar a empresa a melhorar a forma como apresenta seus serviços e gera novos contatos.

Posso te explicar rapidamente?
```

A estrutura deverá permitir posteriormente a criação de templates.

No futuro:

```text
Olá, {{empresa}}...
```

poderá utilizar dados do próprio lead.

### Evolução futura

* múltiplos templates;
* templates por segmento;
* variáveis;
* personalização automática;
* IA para geração de mensagens.

Esses recursos não fazem parte da V1.

---

# 8. Cadastro manual

O usuário poderá cadastrar um lead através de um formulário.

Campos:

* Nome da empresa;
* Categoria;
* Telefone;
* E-mail;
* Site;
* Instagram;
* Cidade;
* Estado;
* Notas.

O status inicial será:

```text
Novo
```

---

# 9. Importação de planilhas

O sistema deverá permitir importar arquivos:

* `.xlsx`;
* `.csv`.

O formato principal esperado será semelhante à planilha gerada pelo sistema externo:

```text
Nome
Categoria
Telefone
E-mail
Site
Tem site
Instagram
Tem Instagram
Cidade
Estado
Status
Tipo
Score
Avaliação
Nº avaliações
Fonte
Google Maps
Criado em
```

## Fluxo

```text
Selecionar arquivo
       ↓
Ler planilha
       ↓
Validar estrutura
       ↓
Normalizar dados
       ↓
Detectar duplicados
       ↓
Mostrar prévia
       ↓
Confirmar importação
       ↓
Criar/atualizar leads
```

---

# 10. Normalização de telefone

O telefone deverá ser normalizado durante a importação.

Exemplo:

```text
(81) 3482-7554
```

deverá ser armazenado em formato adequado para geração do link:

```text
558134827554
```

O sistema deverá remover:

* espaços;
* parênteses;
* hífens;
* caracteres não numéricos.

A regra deverá considerar o código do país.

Para o contexto inicial, o país padrão será Brasil (`55`).

---

# 11. Detecção de duplicados

O sistema deverá evitar a criação de leads duplicados.

Critério inicial:

```text
Telefone normalizado
```

Se o telefone já existir:

```text
Lead existente
      ↓
não criar outro lead
```

O importador deverá informar ao usuário quantos registros foram:

* novos;
* duplicados;
* inválidos.

Exemplo:

```text
150 registros encontrados

130 novos
15 duplicados
5 inválidos

[ Importar 130 leads ]
```

A estratégia de deduplicação poderá ser expandida posteriormente para considerar:

* telefone;
* nome;
* site;
* identificador externo.

---

# 12. Dados de origem

Os dados provenientes do sistema externo deverão ser preservados.

Exemplo:

```text
Fonte: google_maps
Google Maps: URL
Score: 87
Avaliação: 4.5
Nº avaliações: 32
Tipo: Sem presença
```

Esses dados representam características da empresa e **não devem ser confundidos com o status comercial**.

Exemplo:

```text
Tipo: Sem presença
Score: 87

Status: Em conversa
```

---

# 13. Interface

A aplicação terá inicialmente duas áreas principais.

## 13.1 Leads

Visualização em tabela.

Colunas sugeridas:

```text
Empresa
Categoria
Telefone
Cidade
Score
Tipo
Status
Ações
```

Ações:

* visualizar;
* editar;
* abrir WhatsApp;
* excluir.

Também deverá existir:

* busca;
* filtros;
* ordenação;
* botão de novo lead;
* botão de importar.

---

## 13.2 Pipeline

Visualização Kanban.

Cada coluna representa um status.

O card deverá mostrar, no mínimo:

```text
Nome da empresa
Categoria
Telefone
Score
```

E poderá possuir ações:

```text
WhatsApp
Visualizar
```

O card poderá ser arrastado entre as colunas.

---

# 14. Página de detalhes do lead

Ao abrir um lead:

```text
┌──────────────────────────────────────────┐
│ Clínica Exemplo                          │
│ Clínica odontológica                     │
│                                          │
│ 📞 (81) 99999-9999                       │
│ 🌐 exemplo.com.br                        │
│ 📷 @clinicaexemplo                       │
│ 📍 Recife - PE                           │
│                                          │
│ Status                                   │
│ Em conversa                              │
│                                          │
│ Presença digital                         │
│ Site: Sim                                │
│ Instagram: Não                           │
│ Score: 86                                │
│ Google: 4.8 ★ (89 avaliações)            │
│                                          │
│ Notas                                    │
│ ┌──────────────────────────────────────┐ │
│ │ Observações sobre o prospect...      │ │
│ └──────────────────────────────────────┘ │
│                                          │
│ [ WhatsApp ] [ Editar ]                  │
└──────────────────────────────────────────┘
```

---

# 15. Modelo de dados inicial

A entidade principal será `Lead`.

Estrutura conceitual:

```text
leads
────────────────────────────
id
company_name
category
phone
email
website
instagram
has_website
has_instagram
city
state
status
type
score
rating
reviews_count
source
google_maps_url
source_created_at
notes
created_at
updated_at
```

O campo `status` será controlado pelo sistema de prospecção.

---

# 16. Stack

## Backend

* Laravel
* Eloquent ORM
* SQLite
* Laravel Validation
* Laravel Authentication

## Frontend

* Vue
* Inertia.js
* TypeScript
* Vue.Draggable Next

## Banco

SQLite na V1.

A arquitetura deverá evitar dependências específicas de SQLite que dificultem uma migração futura para PostgreSQL.

---

# 17. Arquitetura

uso de actions para logica
controllers finos
guilines no arquivo CODE_GUIDELINES.md


---

# 18. Regras importantes

### Status comercial

O status sempre representa o estágio da negociação.

Não utilizar `Tipo` ou `Score` para representar status.

### WhatsApp

Somente leads com telefone válido terão a ação de WhatsApp habilitada.

### Importação

A importação nunca deverá sobrescrever silenciosamente dados comerciais já existentes.

Por exemplo, se um lead já estiver:

```text
Status: Em conversa
```

e uma nova planilha trouxer:

```text
Status: Novo
```

o status comercial existente deverá ser preservado.

### Notas

Notas comerciais adicionadas manualmente pelo usuário nunca deverão ser apagadas por uma nova importação.

### Dados externos

Informações provenientes da fonte poderão ser atualizadas durante uma nova importação.

---

# 19. Fora do escopo da V1

Não implementar inicialmente:

* WhatsApp API;
* disparo automático;
* chatbot;
* automação de follow-up;
* IA;
* geração automática de mensagens;
* múltiplos usuários;
* permissões avançadas;
* CRM completo;
* histórico de todas as mensagens do WhatsApp;
* integração direta com Google Maps;
* captura automática de leads;
* dashboards avançados;
* cobrança;
* multiempresa.

Esses recursos podem ser considerados posteriormente.

---

# 20. Futuras evoluções

Após validar o uso da V1, possíveis evoluções:

### Mensagens

* templates;
* variáveis;
* mensagens por segmento;
* geração de mensagens com IA.

### Pipeline

* histórico de movimentações;
* tarefas;
* follow-ups;
* lembretes;
* próxima ação.

### Inteligência

* priorização automática;
* análise do site;
* análise de presença digital;
* recomendação de abordagem;
* score comercial próprio.

### Métricas

```text
Leads captados
Leads contatados
Taxa de resposta
Conversas
Reuniões
Propostas
Vendas
Taxa de conversão
```

### Importação

* múltiplos formatos;
* mapeamento personalizado de colunas;
* sincronização com o sistema de captação;
* identificador externo;
* atualização incremental.

---

# 21. Critérios de sucesso da V1

A V1 será considerada funcional quando for possível executar o seguinte fluxo sem intervenção técnica:

```text
1. Importar uma planilha
        ↓
2. Visualizar os leads
        ↓
3. Abrir um lead
        ↓
4. Ver suas informações
        ↓
5. Clicar em WhatsApp
        ↓
6. Abrir a conversa com mensagem preenchida
        ↓
7. Marcar/mover lead para Contatado
        ↓
8. Quando houver resposta, mover para Em conversa
        ↓
9. Mover para Reunião
        ↓
10. Mover para Proposta
        ↓
11. Marcar como Ganho ou Perdido
```

O sistema deve tornar esse fluxo rápido o suficiente para ser utilizado diariamente como ferramenta principal de prospecção.

---

# 22. Princípio de desenvolvimento

A primeira versão deve priorizar:

**simplicidade → velocidade → uso real → aprendizado → automação.**

Não construir infraestrutura de automação antes de validar o processo comercial.

O sistema deve resolver primeiro o problema operacional:

> **"Tenho muitos leads e preciso organizar, abordar e acompanhar cada oportunidade sem me perder."**

Depois de validar esse fluxo, funcionalidades de automação e inteligência poderão ser adicionadas sobre uma base já utilizada e comprovada.
