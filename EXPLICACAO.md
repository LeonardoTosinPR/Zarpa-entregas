# Zarpa — Explicação Técnica: Gerenciamento de Pedidos

---

## Apresentação da Funcionalidade

### 1 — Propósito da funcionalidade e sua importância no sistema

O **gerenciamento de pedidos** é o núcleo do negócio do Zarpa. É ele que conecta **clientes** (que precisam realizar uma entrega) a **entregadores** (que executam a entrega). Sem essa funcionalidade, a plataforma não existe — todo o restante do sistema (autenticação, notificações, painel admin) existe para suportá-la.

A funcionalidade permite que:

- **Clientes** criem, editem e cancelem pedidos de entrega.
- **Entregadores** visualizem pedidos disponíveis, aceitem, atualizem o status e marquem como entregue.
- **Administradores** gerenciem qualquer pedido do sistema.

O ciclo de vida completo de um pedido percorre os seguintes estados:

```
[cliente cria] → pendente
                    ↓ entregador aceita
                  aceito → em rota
                              ↓ entregador marca como entregue
                           entregue → [pedido removido + notificação ao cliente]
```

Quando o status chega a **"entregue"**, o sistema envia automaticamente uma notificação ao cliente e remove o pedido do banco de dados — encerrando o ciclo completo.

---

## Organização do WIKI — Informações da Entrega

### 1 — Página com descrição geral, link para PR, comando do populate e dados de acesso

A documentação completa está disponível na Wiki do repositório:

> **https://github.com/LeonardoTosinPR/Zarpa-entregas/wiki**

A página da entrega contém: descrição geral da funcionalidade, link para o Pull Request, diagrama de atividades e instruções de execução.

**Comando para popular o banco de dados:**

```bash
docker compose exec php php database/Populate/populate.php
```

**Dados de acesso após popular:**

| Perfil | E-mail | Senha |
|--------|--------|-------|
| Admin | admin@zarpa.com | admin123 |
| Cliente | joao@zarpa.com | password123 |
| Entregador | william@zarpa.com | password123 |

**Acesso ao sistema:** `http://localhost:8081`

---

## Demonstração Prática no Sistema

> Acessar o sistema logado como cliente (`joao@zarpa.com / password123`).

### 1 — Tentativa de cadastro com dados incorretos

Acessar `/orders/new`, deixar o campo **"Endereço de Coleta"** vazio e clicar em **Salvar**.

**Resultado esperado:** mensagem de erro *"Verifique os dados do pedido."* — o formulário é reexibido com os dados preenchidos mantidos.

O que acontece internamente:

```php
// app/Models/Order.php
public function validates(): void
{
    Validations::notEmpty('pickup_address', $this); // falha → addError()
    Validations::notEmpty('delivery_address', $this);
    // ...
}

// app/Controllers/OrdersController.php
if ($order->save()) { // save() retorna false por causa dos erros
    // ...
}
FlashMessage::danger('Verifique os dados do pedido.'); // exibe o erro
$this->render('orders/form', compact('order'));
```

### 2 — Tentativa de cadastro bem-sucedida

Preencher todos os campos obrigatórios (endereço de coleta, endereço de entrega, distância em km) e clicar em **Salvar**.

**Resultado esperado:** redirecionamento para a página do pedido com a mensagem *"Pedido criado com sucesso."*

O padrão usado é o **PRG (Post–Redirect–Get)**: após salvar com sucesso, o controller redireciona para evitar resubmissão do formulário ao dar F5.

```php
if ($order->save()) {
    FlashMessage::success('Pedido criado com sucesso.');
    $this->redirectTo(route('orders.show', ['id' => $order->id])); // redirect
}
```

### 3 — Tentativa de atualização com dados incorretos

Acessar a edição do pedido criado (`/orders/{id}/edit`), apagar os dois campos de endereço e clicar em **Salvar**.

**Resultado esperado:** mensagem de erro de validação — o formulário é reexibido sem salvar nada.

### 4 — Tentativa de atualização bem-sucedida

Preencher os endereços corretamente e clicar em **Salvar**.

**Resultado esperado:** redirecionamento para `/orders/{id}` com a mensagem *"Pedido atualizado com sucesso."*

Internamente, o controller atualiza apenas os campos permitidos para o perfil do usuário logado — um cliente não consegue alterar o status, mesmo que forje a requisição:

```php
// app/Controllers/OrdersController.php
foreach ($this->orderParams($request, $order) as $property => $value) {
    $order->$property = $value; // apenas campos filtrados por perfil
}
```

### 5 — Listagem de todos os registros

Acessar `/orders`.

**Resultado esperado:** lista de pedidos do cliente logado, ordenados do mais recente ao mais antigo.

A query executada varia conforme o perfil:

```php
// app/Models/Order.php
public static function visibleFor(User $user): array
{
    if ($user->isAdmin())     return self::orderedByCreatedAt(); // todos
    if ($user->isDeliverer()) return /* pendentes + os seus aceitos */;
    return /* apenas os pedidos do cliente logado */;
}
```

### 6 — Remoção do registro

Na página do pedido (`/orders/{id}`), clicar em **"Cancelar pedido"** e confirmar o popup.

**Resultado esperado:** redirecionamento para `/orders` com a mensagem *"Pedido cancelado com sucesso."*

A mensagem varia conforme o perfil: admin vê *"Pedido excluído com sucesso."*, cliente vê *"Pedido cancelado com sucesso."*

```php
FlashMessage::success(
    $user->isAdmin() ? 'Pedido excluido com sucesso.' : 'Pedido cancelado com sucesso.'
);
```

### 7 — Paginação dos registros

Com múltiplos pedidos cadastrados, acessar `/orders`.

**Resultado esperado:** todos os pedidos aparecem na listagem, ordenados por data de criação (mais recentes primeiro). O sistema usa a classe `Paginator` em `lib/Paginator.php` para dividir os resultados em páginas quando o volume é grande.

---

## Explicação de Conceitos

> Para cada conceito é apresentada uma referência de livro.

### 1 — Definição de CRUD

**CRUD** é o acrônimo das quatro operações básicas de persistência de dados:

| Letra | Operação | SQL | HTTP (REST) | Zarpa |
|-------|----------|-----|-------------|-------|
| **C** | Create (criar) | `INSERT` | `POST` | `POST /orders` |
| **R** | Read (ler) | `SELECT` | `GET` | `GET /orders`, `GET /orders/{id}` |
| **U** | Update (atualizar) | `UPDATE` | `PUT/PATCH` | `PUT /orders/{id}` |
| **D** | Delete (deletar) | `DELETE` | `DELETE` | `DELETE /orders/{id}` |

São as operações fundamentais que qualquer sistema de gerenciamento de dados precisa oferecer. No Zarpa, o CRUD completo de pedidos é implementado no `OrdersController` com as actions `create`, `show`/`index`, `update` e `destroy`.

> 📖 WELLING, Luke; THOMSON, Laura. **PHP and MySQL Web Development**. 5. ed. Addison-Wesley, 2016. Cap. 11 — *"Accessing Your MySQL Database from the Web with PHP"*.

---

### 2 — Definição do MVC

**MVC (Model–View–Controller)** é um padrão arquitetural que separa a aplicação em três camadas com responsabilidades distintas:

```
Requisição HTTP
    → Controller recebe, valida, chama Model
    → Model aplica regras de negócio, persiste no banco
    → Controller chama render($view, $dados)
    → View exibe o HTML ao usuário
```

| Camada | Responsabilidade | Exemplo no Zarpa |
|--------|-----------------|-----------------|
| **Model** | Dados, validações, regras de negócio, queries | `app/Models/Order.php` |
| **View** | Apresentação visual (HTML/templates) | `app/views/orders/` |
| **Controller** | Orquestra Model e View, processa requisição | `app/Controllers/OrdersController.php` |

O Zarpa foi construído com um **framework MVC próprio** — sem Laravel ou Symfony — o que significa que o roteamento, o ActiveRecord e o sistema de views foram implementados do zero em `core/`.

> 📖 GAMMA, Erich et al. **Design Patterns: Elements of Reusable Object-Oriented Software**. Addison-Wesley, 1994. p. 4–6 — *"Model/View/Controller"*.

---

### 3 — Comparação: MVC × MVP × MVVM × HMVC

Todos os quatro padrões resolvem o mesmo problema: **separar a lógica de negócio da interface do usuário**. A diferença está em *quem se comunica com quem* e *como* a View é atualizada.

---

#### MVC — Model–View–Controller

O Controller é o intermediário. Ele recebe a requisição, consulta o Model e decide qual View renderizar. A View pode receber dados do Model indiretamente (via variáveis passadas pelo Controller).

```
Usuário → Controller → Model
                ↓
             View (recebe dados do Controller)
```

- **Ambiente típico:** aplicações server-side (PHP, Ruby on Rails, Django)
- **Exemplo:** o próprio Zarpa

---

#### MVP — Model–View–Presenter

A **View é completamente passiva** — ela não toma nenhuma decisão. Toda a lógica de apresentação vai para o Presenter. A View só exibe e delega eventos.

```
Usuário → View → Presenter → Model
              ←             ←
     (Presenter chama métodos da View para atualizar)
```

```php
// Como seria o Zarpa com MVP:

// View só delega — sem lógica
class OrderFormView {
    public function onSaveClicked(): void {
        $this->presenter->handleSave($this->getFormData());
    }
    public function showError(string $msg): void { /* exibe */ }
}

// Presenter contém toda a lógica de apresentação
class OrderFormPresenter {
    public function handleSave(array $data): void {
        $order = new Order($data);
        if ($order->save()) {
            $this->view->redirectTo('/orders/' . $order->id);
        } else {
            $this->view->showError('Verifique os dados do pedido.');
        }
    }
}
```

- **Diferença-chave do MVC:** a View nunca conhece o Model; o Presenter é totalmente testável com um mock da View
- **Ambiente típico:** Android, aplicações desktop (WinForms)

---

#### MVVM — Model–View–ViewModel

O ViewModel expõe **propriedades observáveis** e a View se atualiza automaticamente via *data binding* bidirecional — sem que o ViewModel conheça a View.

```
View ←[binding bidirecional]→ ViewModel ←→ Model
  (atualiza automaticamente)    (sem referência à View)
```

```javascript
// Como seria o formulário de pedido em Vue.js (MVVM):

const orderViewModel = reactive({
    pickupAddress: '',
    deliveryAddress: '',
    distanceKm: '',
    errors: {},
    async save() {
        const res = await api.post('/orders', this.$data);
        if (!res.ok) this.errors = res.errors;
    }
});
```

```html
<!-- View — sem lógica, só bindings -->
<input v-model="orderViewModel.pickupAddress" />
<input v-model="orderViewModel.deliveryAddress" />
<button @click="orderViewModel.save()">Salvar</button>
```

Qualquer mudança no campo de input atualiza o ViewModel instantaneamente — sem POST, sem reload de página.

- **Diferença-chave do MVP:** o ViewModel não conhece a View (zero acoplamento)
- **Ambiente típico:** front-end reativo (Vue.js, Angular, WPF, SwiftUI)

---

#### HMVC — Hierarchical Model–View–Controller

Estende o MVC permitindo que **Controllers chamem outros Controllers internamente**, formando uma hierarquia de módulos MVC independentes. Cada módulo encapsula seu próprio Model, View e Controller.

```
Controller Principal
    ├→ [request interno] → Controller de Pedidos   → View parcial
    ├→ [request interno] → Controller de Notificações → View parcial
    └→ [request interno] → Controller de Usuário   → View parcial
          ↓
    monta a resposta final
```

```php
// Como seria o Zarpa com HMVC:

class OrdersController {
    public function show(Request $request): void {
        $orderHtml         = $this->dispatch('orders/detail', $request);
        $notificationsHtml = $this->dispatch('notifications/panel', $request);
        $userHtml          = $this->dispatch('users/mini-profile', $request);

        $this->render('layout', compact('orderHtml', 'notificationsHtml', 'userHtml'));
    }
}
```

- **Diferença-chave do MVC:** Controllers chamam outros Controllers; cada módulo é completamente independente e reutilizável
- **Ambiente típico:** aplicações PHP grandes e modulares (ex: Kohana Framework)

---

#### Tabela Comparativa

| | MVC | MVP | MVVM | HMVC |
|---|---|---|---|---|
| View conhece Model? | Às vezes | Nunca | Nunca | Às vezes |
| Quem atualiza a View? | Controller | Presenter (explícito) | Binding automático | Controller |
| Testabilidade da lógica de apresentação | Média | Alta | Alta | Média |
| Módulos hierárquicos? | Não | Não | Não | Sim |
| Ambiente típico | Server-side web | Desktop / Android | Front-end reativo | Apps modulares |
| Exemplo | Laravel, Rails, **Zarpa** | Android, WinForms | Vue, Angular, WPF | Kohana |

> 📖 FOWLER, Martin. **Patterns of Enterprise Application Architecture**. Addison-Wesley, 2002. p. 330–344 — *"Model View Controller"*, *"Model View Presenter"* e *"Presentation Model"* (base do MVVM).

> 📖 CAI, Jason; KAPILA, Ranjit; PAL, Gaurav. **HMVC: The Layered Pattern for Developing Strong Client Tiers**. JavaWorld, julho de 2000 — artigo original do HMVC, acesso livre.

---

### 4 — Definição de Rotas RESTful

**REST (Representational State Transfer)** é um estilo arquitetural para APIs e aplicações web que usa os verbos HTTP para operar sobre **recursos** identificados por URLs.

No Zarpa, as rotas de pedidos seguem exatamente esse padrão:

```php
// config/routes.php
Route::get    ('/orders',             'index');   // listar todos
Route::get    ('/orders/new',         'new');     // exibir formulário de criação
Route::post   ('/orders',             'create');  // salvar novo pedido
Route::get    ('/orders/{id}',        'show');    // exibir um pedido
Route::get    ('/orders/{id}/edit',   'edit');    // exibir formulário de edição
Route::put    ('/orders/{id}',        'update');  // salvar edição
Route::delete ('/orders/{id}',        'destroy'); // cancelar/excluir
Route::post   ('/orders/{id}/accept', 'accept');  // entregador aceita
Route::post   ('/orders/{id}/refuse', 'refuse');  // entregador recusa
```

| Verbo HTTP | Ação | Semântica |
|------------|------|-----------|
| `GET` | Leitura | Nunca modifica dados |
| `POST` | Criação | Cria novo recurso |
| `PUT` | Atualização completa | Substitui o recurso |
| `DELETE` | Remoção | Remove o recurso |

Todas as rotas de pedidos ficam dentro de um grupo `middleware('auth')` — qualquer usuário não autenticado é redirecionado automaticamente para `/login`.

> 📖 RICHARDSON, Leonard; RUBY, Sam. **RESTful Web Services**. O'Reilly Media, 2007. Cap. 4 — *"The Resource-Oriented Architecture"*.

---

## Demonstração do Banco de Dados

### 1 — Banco de dados da funcionalidade de pedidos

A funcionalidade de pedidos utiliza principalmente a tabela `orders`, com relacionamentos para a tabela `users`.

**Tabela `orders`:**

| Coluna | Tipo | Descrição |
|--------|------|-----------|
| `id` | INT PK | Identificador único do pedido |
| `client_id` | INT FK | Referência ao usuário cliente (`users.id`) |
| `courier_id` | INT FK | Referência ao entregador (`users.id`), nulo se pendente |
| `pickup_address` | VARCHAR | Endereço de coleta |
| `delivery_address` | VARCHAR | Endereço de entrega |
| `package_size` | ENUM | `pequeno`, `medio`, `grande` |
| `is_fragile` | TINYINT | 1 = frágil, 0 = normal |
| `distance_km` | DECIMAL | Distância em quilômetros |
| `status` | ENUM | `pendente`, `aceito`, `em rota`, `entregue`, `cancelado` |
| `payment_method` | ENUM | `pix`, `dinheiro`, `cartao` |
| `shipping_fee` | DECIMAL | Valor do frete (calculado automaticamente) |
| `confirmation_code` | VARCHAR | Código de 6 caracteres gerado no cadastro |
| `created_at` | DATETIME | Data/hora de criação |

**Relacionamentos:**

```
users (id) ←── orders.client_id   (um cliente tem muitos pedidos)
users (id) ←── orders.courier_id  (um entregador tem muitos pedidos aceitos)
```

**Cálculo automático do frete:**

O frete é calculado pelo Model antes de cada `save()`, sem intervenção do usuário:

```
frete = preço_base(tamanho) + taxa_frágil + (distância_km × R$ 0,10)
```

| Tamanho | Base | + Frágil | + Distância |
|---------|------|----------|-------------|
| Pequeno | R$ 10,00 | R$ 5,00 | R$ 0,10/km |
| Médio | R$ 15,00 | R$ 5,00 | R$ 0,10/km |
| Grande | R$ 20,00 | R$ 5,00 | R$ 0,10/km |

**Exemplo:** pacote médio, frágil, 10 km → R$ 15,00 + R$ 5,00 + R$ 1,00 = **R$ 21,00**

**Query de visibilidade por perfil:**

```sql
-- Admin: todos os pedidos
SELECT * FROM orders ORDER BY created_at DESC;

-- Entregador: pendentes (disponíveis para aceitar) + os que já aceitou
SELECT * FROM orders
WHERE status = 'pendente' OR courier_id = :courier_id
ORDER BY created_at DESC;

-- Cliente: apenas os seus próprios pedidos
SELECT * FROM orders
WHERE client_id = :client_id
ORDER BY created_at DESC;
```

---

## Referências

- WELLING, Luke; THOMSON, Laura. **PHP and MySQL Web Development**. 5. ed. Addison-Wesley, 2016. Cap. 11.
- GAMMA, Erich et al. **Design Patterns: Elements of Reusable Object-Oriented Software**. Addison-Wesley, 1994. p. 4–6.
- FOWLER, Martin. **Patterns of Enterprise Application Architecture**. Addison-Wesley, 2002. p. 330–344.
- RICHARDSON, Leonard; RUBY, Sam. **RESTful Web Services**. O'Reilly Media, 2007. Cap. 4.
- CAI, Jason; KAPILA, Ranjit; PAL, Gaurav. **HMVC: The Layered Pattern for Developing Strong Client Tiers**. JavaWorld, julho de 2000.
- GOSSMAN, John. **Introduction to Model/View/ViewModel pattern for building WPF Apps**. Microsoft MSDN, 2005.
