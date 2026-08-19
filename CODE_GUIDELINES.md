# Nuxt Code Guidelines

  

> O objetivo destas guidelines é manter o código simples, previsível e fácil de evoluir. Sempre priorize legibilidade, baixo acoplamento e reutilização.

  

---

  

# Princípios

  

* Escreva código para pessoas, não apenas para o computador.

* Prefira simplicidade à abstração desnecessária.

* Evite duplicação de lógica.

* Cada arquivo deve possuir uma única responsabilidade.

* O código deve ser fácil de modificar sem gerar efeitos colaterais.

  

---

  

# Arquitetura

  

As dependências devem seguir sempre este fluxo:

  

```

Pages

    ↓

Components

    ↓

Composables

    ↓

Services

    ↓

useApi

    ↓

Backend

```

  

Cada camada conhece apenas a imediatamente abaixo.

  

---

  

# Componentes

  

## Componentes devem ser responsáveis apenas pela interface

  

Componentes devem:

  

* Renderizar informações.

* Emitir eventos.

* Receber props.
* No esqueleto do componente vue, sempre script acima de template
* usar variaveis de talwind definidos no main.css ou arquivos correlatos como por exemplo text-brand-red


Evite colocar:

  

* regras de negócio;

* chamadas HTTP;

* transformações complexas;

* lógica de autenticação.

  

---

  

## Componentes devem ser pequenos

  

Quando um componente crescer demais, considere dividi-lo.

  

Sinais de que ele está grande:

  

* mais de ~300 linhas;

* muitos `v-if`;

* vários `watch`;

* diversas responsabilidades.

  

---

  

## Evite lógica complexa no template

  

Ruim

  

```vue

<div v-if="user && user.plan === 'premium' && user.subscription.active">

```

  

Bom

  

```ts

const canAccessPremium = computed(...)

```

  

```vue

<div v-if="canAccessPremium">

```

  

---

  

## Prefira props específicas

  

Evite

  

```vue

<UserCard :user="user" />

```

  

Quando possível prefira

  

```vue

<UserCard

    :name="user.name"

    :avatar="user.avatar"

/>

```

  

Isso reduz acoplamento.

  

---

  

# Composables

  

## Toda lógica reutilizável deve viver em composables

  

Use composables para:

  

* gerenciamento de estado local;

* regras de negócio;

* comunicação com services;

* transformação de dados.

  

---

  

## Um composable deve possuir apenas uma responsabilidade

  

Evite

  

```

useDashboard.ts

```

  

Prefira

  

```

useDashboardMetrics.ts

  

useDashboardFilters.ts

  

useDashboardCharts.ts

```

  

---

  

# Services

  

Toda comunicação com APIs deve passar por services.

  

Nunca faça chamadas HTTP diretamente em componentes.

  

Ruim

  

```ts

await useFetch('/api/products')

```

  

Bom

  

```ts

const { products } = useProducts()

```

  

```

useProducts()

    ↓

ProductService

    ↓

useApi()

```

  

---

  

# useApi

  

Toda comunicação HTTP deve utilizar um único wrapper.

  

Responsabilidades:

  

* baseURL;

* autenticação;

* headers;

* interceptors;

* tratamento de erros;

* timeout;

* refresh de token.

  

Nenhum outro lugar deve conhecer esses detalhes.

  

---

  

# Estado

  

## Utilize Pinia apenas para estado global

  

Exemplos:

  

* usuário autenticado;

* tema;

* idioma;

* carrinho;

* notificações.

  

Evite colocar no Pinia:

  

* estado de formulários;

* filtros de uma página;

* modais locais;

* estados utilizados por apenas um componente.

  

---

  

# Regras de Negócio

  

Nunca espalhe regras de negócio pelo projeto.

  

Evite

  

```ts

if (

    order.status === 'paid' &&

    order.total > 500 &&

    order.customer.vip

)

```

  

Prefira

  

```ts

canReceiveBonus(order)

```

  

Ou

  

```ts

isPremiumOrder(order)

```

  

O nome deve explicar a regra.

  

---

  

# Computed vs Watch

  

Prefira sempre `computed`.

  

Utilize `watch` apenas quando existir efeito colateral.

  

Exemplos:

  

* salvar dados;

* navegar;

* chamar APIs;

* acessar Local Storage.

  

Não utilize `watch` para derivar estado.

  

---

  

# Tipagem

  

Sempre tipar:

  

* props;

* emits;

* refs;

* composables;

* retorno de APIs.

  

Evite

  

```ts

const user = ref()

```

  

Prefira

  

```ts

const user = ref<User | null>(null)

```

  

---

  

# Constantes

  

Nunca utilize valores mágicos.

  

Ruim

  

```ts

if (status === 3)

```

  

Bom

  

```ts

if (status === OrderStatus.Completed)

```

  

Sempre utilize constantes ou enums.

  

---

  

# Organização

  

## Estrutura sugerida

  

```

app/

components/

composables/

services/

stores/

types/

utils/

constants/

plugins/

middleware/

pages/

layouts/

assets/

public/

```

  

---

  

## Organização dos types

  

```

types/

  

    api/

  

    models/

  

    forms/

  

    enums/

```

  

---

  

## Organização dos utils

  

Evite

  

```

utils.ts

```

  

Prefira

  

```

utils/

  

    date.ts

  

    string.ts

  

    money.ts

  

    number.ts

  

    array.ts

```

  

---

  

# Nomeação

  

## Composables

  

Sempre utilizar:

  

```

useAuth()

  

useProducts()

  

useUsers()

```

  

Evite misturar padrões como:

  

```

getUsers()

  

fetchProducts()

  

usersComposable()

```

  

---

  

## Booleanos

  

Prefira nomes que expressem perguntas.

  

```

isLoading

  

isAdmin

  

hasPermission

  

canEdit

  

shouldRedirect

```

  

---

  

## Funções

  

Utilize verbos.

  

```

createUser()

  

updateProfile()

  

deleteProduct()

  

calculateTotal()

  

formatCurrency()

```

  

---

  

# DRY

  

Nunca copie lógica.

  

Sempre que perceber código repetido:

  

* mova para um composable;

* mova para um service;

* mova para um util;

* mova para uma função.

  

---

  

# Tratamento de Erros

  

Não espalhe blocos `try/catch` pelo projeto.

  

Sempre que possível centralize o tratamento dentro dos services ou do `useApi`.

  

---

  

# Legibilidade

  

Prefira

  

```ts

const isPremiumUser = ...

```

  

Ao invés de

  

```ts

const premium = ...

```

  

O código deve ser autoexplicativo.

  

---

  

# Comentários

  

Evite comentários que expliquem **o que** o código faz.

  

Ruim

  

```ts

// Soma dois números

```

  

Prefira código claro.

  

Utilize comentários apenas para explicar:

  

* decisões arquiteturais;

* limitações;

* regras de negócio incomuns.

  

---

  

# Antes de criar um novo arquivo

  

Pergunte:

  

* Essa responsabilidade já existe?

* Posso reutilizar algum composable?

* Existe algum service semelhante?

* Estou duplicando lógica?

  

---

  

# Checklist para Pull Requests

  

* [ ] Componentes possuem apenas responsabilidade de UI.

* [ ] Não existem chamadas HTTP em componentes.

* [ ] Regras de negócio estão encapsuladas.

* [ ] Não existe lógica duplicada.

* [ ] Código está completamente tipado.

* [ ] Nenhum valor mágico foi utilizado.

* [ ] `computed` foi utilizado no lugar de `watch` quando possível.

* [ ] Estado global está apenas no Pinia.

* [ ] Arquivos possuem responsabilidade única.

* [ ] Os nomes são claros e autoexplicativos.

* [ ] A arquitetura do projeto foi respeitada.