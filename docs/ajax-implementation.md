# Implementação de Ajax - Entrega 4.5

## 1. Apresentação da Funcionalidade

### Proposição

Esta implementação introduz suporte a requisições **Ajax (Asynchronous JavaScript and XML)** no sistema Zarpa Entregas. O Ajax permite que a aplicação comunique-se com o servidor sem necessidade de recarregar a página, proporcionando uma experiência do usuário mais fluida e responsiva.

### Importância no Sistema

- **Melhor experiência do usuário**: Operações assíncronas sem bloqueio da interface
- **Redução de tráfego**: Apenas dados necessários são transferidos, não a página inteira
- **Interface mais responsiva**: Feedback imediato para o usuário durante operações

## 2. Conceitos Fundamentais

### 2.1 O que é Ajax?

Ajax é uma técnica que permite que páginas web façam requisições HTTP de forma **assíncrona** em background, sem necessidade de recarregar a página. Combina:

- **HTML/CSS**: Para apresentação
- **JavaScript**: Para lógica assíncrona
- **XML/JSON**: Para transporte de dados
- **XMLHttpRequest/Fetch**: Para comunicação HTTP

**Exemplo básico com Fetch API:**

```javascript
fetch("/api/tags", {
  method: "GET",
  headers: {
    Accept: "application/json",
  },
})
  .then((response) => response.json())
  .then((data) => console.log(data))
  .catch((error) => console.error(error));
```

### 2.2 Diferença entre Requisição Síncrona e Assíncrona

#### **Requisição Síncrona**

- O navegador **bloqueia** enquanto aguarda resposta
- Interface fica "congelada"
- Pode causar timeout para operações longas
- Experiência ruim do usuário

```javascript
// ❌ NÃO USE - Bloqueador
let xhr = new XMLHttpRequest();
xhr.open("GET", "/api/tags", false); // false = síncrono
xhr.send();
console.log(xhr.responseText);
// Tudo fica congelado até responder
```

#### **Requisição Assíncrona**

- O navegador **continua respondendo**
- A resposta é processada via callback/promise
- Interface permanece responsiva
- Experiência melhor do usuário

```javascript
// ✅ USE ISSO - Não bloqueia
fetch("/api/tags")
  .then((response) => response.json())
  .then((data) => console.log(data));
// Continua executando outras coisas
```

### 2.3 HTTP Content-Type e sua Importância

O header `Content-Type` informa ao cliente qual é o formato dos dados sendo transferido.

**Content-Types comuns:**

| Content-Type                        | Descrição             | Uso                      |
| ----------------------------------- | --------------------- | ------------------------ |
| `application/json`                  | Dados em formato JSON | APIs RESTful             |
| `application/x-www-form-urlencoded` | Dados de formulário   | Envio via HTML form      |
| `application/xml`                   | Dados em XML          | (menos usado atualmente) |
| `text/html`                         | Documento HTML        | Páginas web              |
| `text/plain`                        | Texto puro            | Logs, dados simples      |

**Exemplo na nossa implementação:**

No `TagsController.php`:

```php
header('Content-Type: application/json; charset=utf-8');
echo json_encode($json);
```

No `application.js`:

```javascript
headers: {
    'Accept': 'application/json',  // Diz que queremos JSON
    'Content-Type': 'application/json'  // Enviamos JSON
}
```

### 2.4 Definição e Estrutura do JSON

**JSON (JavaScript Object Notation)** é um formato leve de troca de dados baseado em estruturas conhecidas.

**Estrutura:**

- Objetos: `{chave: valor}`
- Arrays: `[item1, item2]`
- Tipos: string, number, boolean, null, object, array

**Exemplo de resposta JSON da nossa API:**

```json
{
  "success": true,
  "message": "Etiquetas carregadas com sucesso.",
  "data": [
    {
      "id": 1,
      "name": "Urgente",
      "color": "danger",
      "badgeClass": "badge bg-danger"
    },
    {
      "id": 2,
      "name": "Frágil",
      "color": "warning",
      "badgeClass": "badge bg-warning"
    }
  ]
}
```

### 2.5 Estados da Requisição (readyState)

O `XMLHttpRequest` passa por 5 estados durante seu ciclo de vida:

| readyState           | Valor | Descrição                          |
| -------------------- | ----- | ---------------------------------- |
| **UNSENT**           | 0     | Requisição criada mas não iniciada |
| **OPENED**           | 1     | `open()` foi chamado               |
| **HEADERS_RECEIVED** | 2     | Headers recebidos                  |
| **LOADING**          | 3     | Corpo está sendo recebido          |
| **DONE**             | 4     | Operação completada                |

**Exemplo com XMLHttpRequest:**

```javascript
const xhr = new XMLHttpRequest();

xhr.addEventListener("readystatechange", function () {
  console.log("Estado:", xhr.readyState);

  if (xhr.readyState === 4) {
    // DONE
    if (xhr.status === 200) {
      console.log("Sucesso:", xhr.responseText);
    } else {
      console.error("Erro:", xhr.status);
    }
  }
});

xhr.open("GET", "/api/tags");
xhr.send();
```

### 2.6 Async, Promise e Await

Estes são padrões modernos para trabalhar com código assíncrono em JavaScript.

#### **Promise**

Uma Promise representa um valor que pode estar disponível agora, no futuro, ou nunca.

```javascript
// Promise com .then() e .catch()
fetch("/api/tags")
  .then((response) => response.json()) // Quando responde
  .then((data) => console.log(data)) // Processa dados
  .catch((error) => console.error(error)); // Trata erro
```

#### **Async/Await**

Sintaxe moderna que faz o código assíncrono parecer síncrono.

```javascript
// Async/await - mais legível
async function loadTags() {
  try {
    const response = await fetch("/api/tags");
    const data = await response.json();
    console.log(data);
  } catch (error) {
    console.error(error);
  }
}

loadTags();
```

## 3. Explicação de Código

### 3.1 Fluxo de uma Requisição Ajax

```
┌──────────────┐
│  Usuário     │
│  Clica no    │
│  botão       │
└────────┬─────┘
         │
         ▼
┌──────────────────────────────────┐
│ JavaScript intercepta evento     │
│ (addEventListener)               │
│ event.preventDefault()           │
└────────┬─────────────────────────┘
         │
         ▼
┌──────────────────────────────────┐
│ fetch() faz requisição HTTP      │
│ POST /api/tags                   │
│ headers + body com dados         │
└────────┬─────────────────────────┘
         │
         ▼
┌──────────────────────────────────┐
│ Servidor recebe em TagsController│
│ createAjax()                     │
└────────┬─────────────────────────┘
         │
         ▼
┌──────────────────────────────────┐
│ Valida dados da tag              │
│ Salva no banco de dados          │
└────────┬─────────────────────────┘
         │
         ▼
┌──────────────────────────────────┐
│ Retorna JSON com resposta        │
│ header('Content-Type: app/json') │
│ echo json_encode($json)          │
└────────┬─────────────────────────┘
         │
         ▼
┌──────────────────────────────────┐
│ JavaScript recebe resposta       │
│ .then(response => response.json())│
│ .then(data => onSuccess(data))   │
└────────┬─────────────────────────┘
         │
         ▼
┌──────────────────────────────────┐
│ Atualiza DOM sem recarregar      │
│ createTagElement()               │
│ appendChild() na tabela           │
└────────┬─────────────────────────┘
         │
         ▼
┌──────────────┐
│ Usuário vê   │
│ resultado    │
│ imediatamente│
└──────────────┘
```

### 3.2 Explicação do Funcionamento do Framework

#### **Backend - TagsController (PHP)**

```php
/**
 * Retorna todas as tags em formato JSON
 */
public function listAjax(): void
{
    // Busca tags do banco
    $tags = Tag::orderedByName();

    // Monta resposta estruturada
    $json = [
        'success' => true,
        'message' => 'Etiquetas carregadas com sucesso.',
        'data' => array_map(function (Tag $tag) {
            return [
                'id' => $tag->id,
                'name' => htmlspecialchars($tag->name),
                'color' => $tag->color,
                'badgeClass' => $tag->badgeClass()
            ];
        }, $tags)
    ];

    // Define header JSON
    header('Content-Type: application/json; charset=utf-8');

    // Converte para JSON e envia
    echo json_encode($json);
}
```

#### **Frontend - JavaScript (application.js)**

```javascript
/**
 * Faz requisição Ajax para listar tags
 */
function listTagsAjax(onSuccess, onError) {
  // Cria requisição
  fetch("/api/tags", {
    method: "GET",
    headers: {
      Accept: "application/json",
    },
  })
    // Converte resposta para JSON
    .then((response) => {
      if (!response.ok) {
        throw new Error("Erro: " + response.status);
      }
      return response.json();
    })
    // Processa dados
    .then((data) => {
      if (data.success) {
        onSuccess(data.data);
      } else {
        throw new Error(data.message);
      }
    })
    // Trata erros
    .catch((error) => {
      console.error("Erro:", error);
      if (onError) {
        onError(error.message);
      }
    });
}
```

#### **Rota - routes.php**

```php
// Rota que mapeia URL para controller
Route::get('/api/tags', [TagsController::class, 'listAjax'])->name('api.tags.list');
```

#### **Ciclo Completo**

```
Usuário em /tags
        ↓
HTML carrega
        ↓
JavaScript setupAjaxTags() executa
        ↓
Evento "submit" interceptado
        ↓
createTagAjax() chamada
        ↓
fetch('/api/tags') envia POST
        ↓
TagsController::createAjax() processa
        ↓
json_encode() retorna resposta
        ↓
.then() processa sucesso
        ↓
createTagElement() renderiza
        ↓
appendChild() adiciona ao DOM
        ↓
Usuário vê nova tag SEM recarregar página
```

## 4. Rotas Implementadas

### Endpoints REST para Tags

```
GET    /api/tags          → Lista todas as tags
POST   /api/tags          → Cria nova tag
DELETE /api/tags/{id}     → Deleta tag por ID
```

### Exemplo de Uso cURL

```bash
# Listar tags
curl http://localhost/api/tags

# Criar tag
curl -X POST http://localhost/api/tags \
  -d "tag[name]=Urgente&tag[color]=danger"

# Deletar tag
curl -X DELETE http://localhost/api/tags/1
```

## 5. Recursos Incluídos

### Backend

- ✅ `TagsController::listAjax()` - Listar tags
- ✅ `TagsController::createAjax()` - Criar tag
- ✅ `TagsController::destroyAjax()` - Deletar tag
- ✅ Validações de autorização (admin only)
- ✅ HTTP Status Codes apropriados (200, 201, 400, 403, 404)

### Frontend

- ✅ `listTagsAjax(onSuccess, onError)` - Requisição GET
- ✅ `createTagAjax(tagData, onSuccess, onError)` - Requisição POST
- ✅ `deleteTagAjax(tagId, onSuccess, onError)` - Requisição DELETE
- ✅ `createTagElement(tag)` - Renderizar tag no HTML
- ✅ `setupAjaxTags()` - Inicializar handlers

### Testes

- ✅ Testes de aceitação em `tests/Acceptance/Tags/AjaxTagsCest.php`
- ✅ Validação de requisições assíncronas
- ✅ Validação de estados HTTP
- ✅ Validação de autorização
- ✅ Testes de Content-Type

## 6. Como Usar

### No HTML (tags/index.phtml)

```html
<!-- O formulário agora usa Ajax via setupAjaxTags() -->
<form class="row g-2" action="<?= route('tags.create') ?>" method="POST">
  <input type="text" name="tag[name]" required />
  <select name="tag[color]">
    <option value="primary">Primary</option>
    <option value="danger">Danger</option>
  </select>
  <button type="submit">Criar</button>
</form>

<!-- JavaScript carrega automaticamente -->
<script src="/assets/js/application.js"></script>
```

### Em JavaScript

```javascript
// Listar tags
listTagsAjax(
  function (tags) {
    console.log("Tags carregadas:", tags);
  },
  function (error) {
    console.error("Erro:", error);
  },
);

// Criar tag
createTagAjax(
  { name: "Nova Tag", color: "success" },
  function (tag) {
    console.log("Tag criada:", tag);
  },
  function (error) {
    console.error("Erro:", error);
  },
);
```

## 7. Estrutura de Resposta

### Sucesso

```json
{
  "success": true,
  "message": "Operação realizada com sucesso.",
  "data": {}
}
```

### Erro

```json
{
  "success": false,
  "message": "Descrição do erro"
}
```

## 8. Status HTTP Utilizados

- `200 OK` - Requisição bem-sucedida
- `201 Created` - Recurso criado com sucesso
- `400 Bad Request` - Dados inválidos
- `403 Forbidden` - Sem permissão
- `404 Not Found` - Recurso não encontrado
- `500 Internal Server Error` - Erro no servidor
