# 🛵 Zarpa - Plataforma Inteligente de Entregas Urbanas

> **"Clicou, Zarpa."** — Plataforma de entregas com encaixe inteligente de rotas para reduzir custos e aumentar a eficiência logística.

![Status](https://img.shields.io/badge/Status-Em_Desenvolvimento-yellow)
![Docker](https://img.shields.io/badge/Docker-Required-blue)
![PHP](https://img.shields.io/badge/PHP-MVC-purple)

---

# 📄 Sobre o Projeto

O **Zarpa** é uma aplicação web desenvolvida como parte de um Trabalho de Conclusão de Curso na área de **Sistemas para Internet**.

O objetivo do projeto é desenvolver uma plataforma que conecta **clientes que precisam realizar entregas** a **entregadores parceiros**, permitindo que múltiplos pedidos sejam **encaixados dinamicamente em uma mesma rota**.

Diferente de plataformas tradicionais de entrega, o Zarpa propõe um modelo em que novos pedidos podem ser adicionados ao trajeto de um entregador já em deslocamento, desde que estejam dentro de uma trajetória compatível. Quando isso ocorre, o sistema recalcula o custo da rota e redistribui o valor do frete entre os clientes envolvidos.

Esse modelo busca gerar benefícios para todos os participantes:

* **Clientes** pagam menos ao compartilhar parte do trajeto.
* **Entregadores** aumentam o lucro ao realizar múltiplas entregas em uma mesma rota.
* **O sistema urbano** se beneficia com redução de deslocamentos desnecessários.

---

# 🎯 Objetivos do Projeto

O projeto busca explorar soluções tecnológicas para melhorar a eficiência da logística urbana de pequenas entregas.

Entre os objetivos estão:

* Criar uma plataforma que conecte clientes e entregadores de forma direta.
* Permitir o **encaixe dinâmico de pedidos durante o trajeto de entrega**.
* Reduzir custos logísticos através do **compartilhamento de rotas**.
* Implementar mecanismos de **segurança e confiança entre usuários**.
* Aplicar conceitos de **otimização de rotas e logística urbana**.

---

# 🧠 Conceitos e Problemas Computacionais Relacionados

O projeto se baseia em conceitos amplamente estudados nas áreas de logística e computação:

### Ride-Sharing

Modelo de compartilhamento de trajetos entre múltiplos usuários para reduzir custos e aumentar eficiência.

### Vehicle Routing Problem (VRP)

Problema clássico da pesquisa operacional que busca determinar rotas eficientes para atender múltiplos pontos de entrega.

### Traveling Salesman Problem (TSP)

Problema de otimização que busca determinar a melhor ordem para visitar diferentes pontos minimizando distância ou tempo.

A proposta do Zarpa combina esses conceitos para criar um sistema de **roteirização dinâmica com redistribuição de custo entre pedidos compatíveis**.

---

# 🔐 Segurança e Confiabilidade

Como o sistema pode operar com pagamentos diretos entre clientes e entregadores, foram consideradas diferentes estratégias para reduzir fraudes e garantir confiabilidade entre os usuários.

Algumas das soluções estudadas incluem:

* Código dinâmico de confirmação de entrega
* Sistema de reputação entre clientes e entregadores
* Registro de confirmação de entrega
* Histórico de transações e avaliações

Esses mecanismos buscam criar um ambiente de confiança sem necessidade de intermediação financeira direta.

---

# 🧱 Tecnologias Utilizadas

O projeto utiliza uma arquitetura baseada em:

* **PHP (Arquitetura MVC)**
* **MySQL**
* **Docker**
* **Docker Compose**
* **HTML / CSS / JavaScript**

A containerização permite que o ambiente de desenvolvimento seja executado de forma padronizada em qualquer máquina.

---

# 📚 Documentação e Wiki

A documentação completa do projeto, incluindo planejamento, diagramas e modelagem, está disponível na Wiki do repositório.

### 👉 https://github.com/LeonardoTosinPR/Zarpa-entregas/wiki

---

# 🚀 Como Executar o Projeto

O ambiente do projeto é totalmente containerizado utilizando Docker.

## Pré-requisitos

* Docker instalado
* Docker Compose instalado

## Passo a passo

### 1. Clone o repositório

```bash
git clone https://github.com/LeonardoTosinPR/Zarpa-entregas.git
cd Zarpa-entregas
```

### 2. Inicie os containers

```bash
docker-compose up -d
```

### 3. Acesse a aplicação

Abra o navegador e acesse:

```
http://localhost:8080
```

(A porta pode variar conforme a configuração do docker-compose.)

---

# 📌 Status do Projeto

Atualmente o projeto encontra-se em fase de **desenvolvimento e definição conceitual**, com foco em:

* Estruturação da proposta de TCC
* Definição da arquitetura do sistema
* Modelagem inicial do banco de dados
* Estudo de algoritmos de roteirização e encaixe de pedidos

---

# 👨‍💻 Autores

**Leonardo Tosin**
**João Paulo Vasconcelos**
**William Wendling Veiga**
Curso de Sistemas para Internet
