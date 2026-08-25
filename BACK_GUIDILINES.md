# Laravel Development Guidelines

## 1. Princípios gerais

O projeto deve priorizar:

* código simples e legível;
* responsabilidades bem definidas;
* controllers finos;
* regras de negócio fora dos controllers;
* baixo acoplamento;
* reutilização sem abstrações prematuras;
* tratamento explícito de erros;
* nomes semânticos;
* tipagem forte;
* early return;
* evitar complexidade desnecessária.

> **Regra principal:** não criar abstrações apenas porque elas parecem arquiteturalmente sofisticadas. Criar abstrações quando existe uma responsabilidade real que merece isolamento.

---

# 2. Arquitetura

A aplicação utilizará uma arquitetura baseada em:

```text
Request
   ↓
Controller
   ↓
Action / Service
   ↓
Model
   ↓
Database
```

Nem toda operação precisa obrigatoriamente de uma Action.

Para operações simples de CRUD, o controller pode interagir diretamente com o Model quando isso mantiver o código mais simples.

Use Actions principalmente quando existir:

* regra de negócio;
* múltiplas operações;
* processamento relevante;
* importação;
* transformação de dados;
* integração externa;
* operação que possa ser reutilizada.

---

# 3. Controllers

Controllers devem ser **finos**.

Um controller deve principalmente:

1. receber a requisição;
2. validar/receber dados já validados;
3. chamar a operação apropriada;
4. retornar a resposta.

Evitar colocar regras de negócio dentro do controller.

### Evitar

```php
public function store(Request $request)
{
    // dezenas de linhas
    // validação
    // normalização
    // regras de negócio
    // criação
    // processamento
    // etc.
}
```

### Preferir

```php
public function store(StoreLeadRequest $request)
{
    $lead = CreateLeadAction::execute($request->validated());

    return to_route('leads.index');
}
```

O controller deve responder à pergunta:

> "Qual operação precisa acontecer?"

e não:

> "Como toda essa operação funciona?"

---

# 4. Actions

Actions representam operações ou casos de uso específicos.

Exemplos:

```text
CreateLeadAction
UpdateLeadAction
UpdateLeadStatusAction
ImportLeadsAction
NormalizePhoneAction
```

Uma Action deve ter uma responsabilidade clara.

### Exemplo

```php
final class UpdateLeadStatusAction
{
    public function execute(
        Lead $lead,
        LeadStatus $status
    ): Lead {
        $lead->update([
            'status' => $status,
        ]);

        return $lead->refresh();
    }
}
```

Evitar Actions genéricas como:

```text
LeadAction
LeadService
GeneralService
CommonService
```

Prefira nomes que expressem **o que a classe faz**.

---

# 5. Quando NÃO criar uma Action

Não criar uma Action apenas para transformar:

```php
$lead->update($data);
```

em:

```php
UpdateLeadAction::execute($lead, $data);
```

se não existe nenhuma regra de negócio relevante.

Abstrações devem reduzir complexidade, não apenas deslocar código.

---

# 6. Services

Services devem ser utilizados com parcimônia.

Não criar uma pasta `Services` para colocar qualquer lógica que não coube no controller.

Se a operação representa claramente um caso de uso, preferir uma Action.

Exemplo:

```text
Actions/
    ImportLeadsAction.php
    CreateLeadAction.php
    UpdateLeadStatusAction.php
```

Evitar:

```text
Services/
    LeadService.php
    HelperService.php
    DataService.php
```

com classes que concentram responsabilidades diferentes.

---

# 7. Form Requests

Validação de entrada deve ficar em Form Requests.

### Preferir

```php
final class StoreLeadRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email'],
        ];
    }
}
```

Controller:

```php
public function store(StoreLeadRequest $request)
{
    CreateLeadAction::execute($request->validated());

    return to_route('leads.index');
}
```

Evitar colocar regras extensas de validação diretamente no controller.

---

# 8. Enums

Valores que representam estados ou categorias conhecidas devem utilizar Enums quando houver um conjunto fechado de valores.

### Evitar

```php
if ($lead->status === 'conversation') {
    //
}
```

Espalhar strings mágicas pelo projeto dificulta manutenção.

### Preferir

```php
enum LeadStatus: string
{
    case NEW = 'new';
    case CONTACTED = 'contacted';
    case CONVERSATION = 'conversation';
    case MEETING = 'meeting';
    case PROPOSAL = 'proposal';
    case WON = 'won';
    case LOST = 'lost';
}
```

Uso:

```php
if ($lead->status === LeadStatus::CONVERSATION) {
    //
}
```

Eloquent deverá utilizar casts:

```php
protected function casts(): array
{
    return [
        'status' => LeadStatus::class,
    ];
}
```

---

# 9. Evitar magic strings

Evitar:

```php
$status = 'conversation';
```

quando o valor representa uma entidade ou estado conhecido.

Preferir:

```php
$status = LeadStatus::CONVERSATION;
```

O mesmo princípio pode ser aplicado a:

* status;
* tipos;
* fontes;
* categorias fechadas;
* permissões;
* outros valores controlados.

Não criar Enum para qualquer string livre.

---

# 10. Early Return

Preferir early return para reduzir nesting.

### Evitar

```php
if ($lead) {
    if ($lead->phone) {
        if ($lead->status === LeadStatus::NEW) {
            // ...
        }
    }
}
```

### Preferir

```php
if (! $lead) {
    return;
}

if (! $lead->phone) {
    return;
}

if ($lead->status !== LeadStatus::NEW) {
    return;
}

// lógica principal
```

A regra é:

> **Falhas e condições de saída devem ser tratadas cedo. O caminho principal deve permanecer fácil de ler.**

---

# 11. Exceptions

Exceptions devem representar situações excepcionais ou condições de erro que interrompem a operação atual. Não devem ser utilizadas como mecanismo de controle de fluxo normal.

Não utilizar exceptions como fluxo normal da aplicação.

### Evitar

```php
try {
    $lead = Lead::findOrFail($id);
} catch (ModelNotFoundException $e) {
    return back();
}
```

O Laravel já possui mecanismos apropriados para tratar isso.

Preferir:

```php
$lead = Lead::findOrFail($id);
```

ou, quando apropriado:

```php
$lead = Lead::find($id);

if (! $lead) {
    return back()->with('error', 'Lead não encontrado.');
}
```

---

# 12. Try/Catch

**Não utilizar `try/catch` por padrão.**

Não envolver toda Action em:

```php
try {
    // ...
} catch (\Throwable $e) {
    //
}
```

Isso apenas esconde problemas e produz código verboso.

### Usar `try/catch` quando:

* existe recuperação possível;
* existe fallback;
* uma exceção precisa ser transformada em outro comportamento;
* uma integração externa pode falhar e isso precisa ser tratado;
* uma operação específica exige tratamento diferente;
* existe necessidade real de adicionar contexto ao erro.

### Exemplo válido

```php
try {
    $result = $externalService->import($file);
} catch (ExternalServiceException $exception) {
    report($exception);

    throw new LeadImportException(
        'Não foi possível importar os leads.',
        previous: $exception,
    );
}
```

A regra:

> **Não capture uma exception se você não sabe o que fazer com ela.**

Se o erro deve subir para o handler global do Laravel, deixe-o subir.

---

# 13. Error Handling

Erros inesperados devem ser tratados pelo mecanismo global do Laravel.

Não fazer:

```php
catch (Throwable $e) {
    return response()->json([
        'error' => 'Algo deu errado'
    ]);
}
```

em todos os controllers.

Isso pode esconder bugs e dificultar debugging.


---

# 14. Database Transactions

Utilizar transações quando uma operação possuir múltiplas alterações que precisam ser atômicas.

Exemplo:

```php
DB::transaction(function () use ($lead, $data) {
    $lead->update($data);

    // outras alterações dependentes
});
```

Não utilizar transactions indiscriminadamente para operações simples.

---

# 15. Importação de leads

A importação é uma operação de negócio relevante e deve ser isolada.

Preferir:

```text
ImportLeadsAction
        ↓
LeadImportService / Importer
        ↓
Normalizer
        ↓
Duplicate detection
        ↓
Persistência
```

A importação deve:

1. ler o arquivo;
2. validar colunas;
3. normalizar dados;
4. detectar duplicados;
5. preservar dados comerciais existentes;
6. criar ou atualizar dados de origem;
7. retornar um resumo da operação.

Exemplo de resultado:

```php
[
    'total' => 150,
    'created' => 130,
    'duplicated' => 15,
    'invalid' => 5,
]
```

---

# 16. Normalização

Dados externos devem ser normalizados antes da persistência.

Especialmente:

* telefone;
* e-mail;
* URLs;
* strings vazias;
* valores booleanos;
* datas;
* números provenientes da planilha.

A normalização deve ser feita em um local centralizado e não repetida em controllers.

---

# 17. Models

Models devem representar o domínio e suas relações.

Evitar transformar Models em grandes classes contendo toda a lógica da aplicação.

Relacionamentos devem ser definidos no Model:

```php
public function interactions(): HasMany
{
    return $this->hasMany(Interaction::class);
}
```

Scopes podem ser utilizados para consultas recorrentes e simples.

---

# 18. Querying

Preferir consultas expressivas do Eloquent:

```php
Lead::query()
    ->where('status', LeadStatus::NEW)
    ->latest()
    ->get();
```

Evitar SQL bruto sem necessidade.

SQL raw pode ser utilizado quando houver uma necessidade real de performance ou uma consulta que o Eloquent não represente bem.

---

# 19. Mass Assignment

Utilizar `$fillable` ou `$guarded` conscientemente.

Não permitir entrada arbitrária do usuário diretamente no Model.

Preferir:

```php
$lead->update($request->validated());
```

em vez de:

```php
$lead->update($request->all());
```

Nunca utilizar `$request->all()` para persistência sem uma razão explícita.

---

# 20. Rotas

As rotas devem ser simples e sem lógica de negócio.

### Preferir

```php
Route::patch(
    '/leads/{lead}/status',
    [LeadController::class, 'updateStatus']
);
```

### Evitar

Closures contendo regras de negócio extensas.

---

# 21. Naming

Os nomes devem expressar intenção.

Preferir:

```text
UpdateLeadStatusAction
ImportLeadsAction
NormalizePhoneNumber
LeadStatus
StoreLeadRequest
```

Evitar:

```text
ProcessData
HandleStuff
CommonService
Helper
Utils
Manager
```

Se o nome da classe não deixa claro o que ela faz, provavelmente a responsabilidade está ampla demais.

---

# 22. Código duplicado

Não abstrair código cedo demais.

Primeiro identifique duplicação real.

Quando a mesma lógica existir em múltiplos lugares e possuir uma responsabilidade clara, extraí-la para:

* Action;
* Value Object;
* Enum;
* Service específico;
* método do Model;
* componente Vue;
* composable.

A abstração deve surgir de uma necessidade real.

---

# 23. Frontend com Inertia + Vue

A lógica de apresentação pertence ao Vue.

A regra de negócio pertence ao Laravel.

Exemplo:

```text
Vue
├── tabela
├── modal
├── formulário
├── Kanban
└── interação drag & drop

Laravel
├── validação
├── autorização
├── regras de negócio
├── persistência
└── importação
```

Não duplicar regras importantes entre frontend e backend.

O frontend pode melhorar UX, mas o backend deve ser a autoridade.

---

# 24. Drag & Drop

O drag & drop do Kanban é responsabilidade da interface.

Ao mover um lead:

```text
Vue
 ↓
PATCH
 ↓
Laravel
 ↓
UpdateLeadStatusAction
 ↓
Database
```

O backend deve validar se o status recebido pertence ao Enum permitido.

Nunca confiar apenas no frontend.

---

# 25. WhatsApp

A geração do link do WhatsApp é uma responsabilidade simples da interface.

O backend não precisa gerar a URL.

Frontend:

```ts
const url = `https://wa.me/${phone}?text=${encodeURIComponent(message)}`
```

O backend é responsável por fornecer o telefone correto e persistido.

A V1 não terá integração com a API do WhatsApp.

---

# 26. Simplicidade primeiro

Não implementar antecipadamente:

* Repository Pattern;
* Service Layer genérica;
* DTO para toda operação;
* interfaces para cada classe;
* factories desnecessárias;
* abstrações genéricas;
* eventos para operações triviais;
* filas para operações que não precisam delas.

A arquitetura deve acompanhar a complexidade real do sistema.

> **Não construir uma arquitetura para o sistema que talvez exista. Construir uma arquitetura adequada ao sistema que existe hoje e que permita evolução saudável.**

---

# 27. Checklist antes de criar uma abstração

Antes de criar uma nova classe, perguntar:

1. Existe uma responsabilidade claramente definida?
2. Essa lógica possui regras próprias?
3. Existe reutilização real?
4. O código ficará mais fácil de testar?
5. A abstração reduz complexidade?
6. Ou estou apenas criando uma camada porque "arquiteturalmente parece certo"?

Se a resposta for principalmente a última, não criar.

---

# 28. Princípio final

O código deve ser:

```text
Simples
↓
Explícito
↓
Legível
↓
Testável
↓
Extensível quando necessário
```

E não:

```text
Abstraído
↓
Genérico
↓
Indireto
↓
Complexo
```

**A melhor arquitetura para este projeto é aquela que torna o fluxo comercial fácil de entender no código.**
