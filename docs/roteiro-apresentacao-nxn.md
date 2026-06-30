# Roteiro de apresentacao - Relacao NxN de pedidos e etiquetas

## 1. Apresentacao da funcionalidade

Objetivo: permitir que um pedido tenha varias etiquetas e que uma etiqueta possa ser usada em varios pedidos.

No sistema, isso ajuda a organizar as entregas por caracteristicas como prioridade, fragil, urgente, recorrente ou qualquer classificacao criada pela equipe. Em vez de duplicar informacoes dentro do pedido, o sistema reaproveita as etiquetas e cria uma relacao entre pedidos e tags.

Exemplo pratico:

- Pedido 1 pode ter as etiquetas "Urgente" e "Fragil".
- Pedido 2 tambem pode usar "Urgente".
- A etiqueta "Urgente" fica ligada a varios pedidos, e cada pedido pode ter varias etiquetas.

## 2. Conceito de NxN

Uma relacao NxN acontece quando varios registros de uma tabela podem se relacionar com varios registros de outra tabela.

Neste projeto:

- Um pedido pode ter varias etiquetas.
- Uma etiqueta pode pertencer a varios pedidos.
- A ligacao fica em uma tabela intermediaria chamada `order_tags`.

Referencia conceitual: em bancos relacionais, uma relacao muitos-para-muitos normalmente e representada por uma tabela associativa, tambem chamada de tabela de juncao, contendo as chaves estrangeiras das duas tabelas relacionadas.

## 3. Banco de dados

Tabelas envolvidas:

- `orders`: armazena os pedidos.
- `tags`: armazena as etiquetas.
- `order_tags`: armazena a associacao entre pedido e etiqueta.

Estrutura esperada da tabela intermediaria:

- `order_id`: chave estrangeira para `orders`.
- `tag_id`: chave estrangeira para `tags`.
- indice unico em `order_id` e `tag_id`.

O indice unico impede que a mesma etiqueta seja associada duas vezes ao mesmo pedido. Isso evita duplicidade e mantem a consistencia da relacao.

## 4. Fluxo pratico no sistema

### 4.1 Registro da relacao

Na criacao ou edicao do pedido, o usuario seleciona as etiquetas desejadas. O controller salva o pedido e sincroniza as tags na tabela `order_tags`.

Ponto para demonstrar:

- Abrir o formulario de pedido.
- Selecionar uma ou mais etiquetas.
- Salvar.
- Mostrar que o pedido aparece com as etiquetas selecionadas.

### 4.2 Visualizacao da relacao

Ao abrir ou listar o pedido, o sistema busca as etiquetas ligadas a ele usando o relacionamento NxN do model `Order`.

Ponto para demonstrar:

- Abrir um pedido salvo.
- Mostrar as etiquetas associadas.
- Explicar que elas nao ficam duplicadas dentro da tabela `orders`; ficam relacionadas pela tabela `order_tags`.

### 4.3 Remocao da relacao

Na edicao do pedido, ao desmarcar uma etiqueta e salvar, a associacao correspondente e removida da tabela `order_tags`.

Ponto para demonstrar:

- Editar o pedido.
- Remover uma etiqueta.
- Salvar.
- Mostrar que a etiqueta nao aparece mais naquele pedido.

### 4.4 Remocao com dependencias

Quando um pedido e removido, suas associacoes na tabela intermediaria tambem precisam ser removidas para nao deixar registros sem uso.

Ponto para demonstrar:

- Excluir um pedido com etiquetas.
- Explicar que os registros da relacao em `order_tags` deixam de existir junto com o pedido.

## 5. Explicacao do codigo

### 5.1 Model `Order`

Arquivo: `app/Models/Order.php`

Metodo principal:

```php
public function tags(): BelongsToMany
{
    return $this->belongsToMany(Tag::class, 'order_tags', 'order_id', 'tag_id');
}
```

Esse metodo define que `Order` se relaciona com `Tag` usando a tabela intermediaria `order_tags`.

Tambem existe o metodo:

```php
public function tagIds(): array
{
    return array_map(fn(Tag $tag) => (int) $tag->id, $this->tags()->get());
}
```

Ele retorna os IDs das etiquetas do pedido, util para preencher o formulario de edicao.

### 5.2 Model `Tag`

Arquivo: `app/Models/Tag.php`

Metodo principal:

```php
public function orders(): BelongsToMany
{
    return $this->belongsToMany(Order::class, 'order_tags', 'tag_id', 'order_id');
}
```

Esse e o outro lado da relacao: uma tag tambem consegue buscar todos os pedidos associados a ela.

### 5.3 Framework - `BelongsToMany`

Arquivo: `core/Database/ActiveRecord/BelongsToMany.php`

Esse arquivo concentra o funcionamento da relacao muitos-para-muitos no framework.

Pontos para explicar:

- `get()`: busca os registros relacionados.
- `attach()`: registra uma nova associacao.
- `detach()`: remove uma associacao.
- `exists()`: verifica se a associacao ja existe.
- `count()`: conta quantos registros relacionados existem.

## 6. Testes

### 6.1 Testes unitarios

Os testes validam os metodos dos models e o funcionamento da relacao:

- `Order::tags()`
- `Order::tagIds()`
- `Tag::orders()`
- metodos do `BelongsToMany`

Tambem foi validada a correcao dos metodos usados pela funcionalidade de fotos de entrega em `Order`, porque eles estavam quebrando a execucao da PR.

Resultado executado:

```text
OK (92 tests, 152 assertions)
```

### 6.2 Testes de aceitacao

Na apresentacao, demonstrar os fluxos de tela:

- Criar pedido com etiquetas.
- Visualizar pedido com etiquetas.
- Editar pedido removendo uma etiqueta.
- Confirmar que usuario sem permissao nao acessa rotas autenticadas.

## 7. Fechamento

A funcionalidade usa uma relacao NxN real entre pedidos e etiquetas. A tabela intermediaria `order_tags` guarda somente as associacoes, enquanto `orders` e `tags` continuam com suas responsabilidades separadas.

Isso deixa o sistema mais flexivel, evita duplicacao de dados e permite que a mesma etiqueta seja reaproveitada por varios pedidos.
