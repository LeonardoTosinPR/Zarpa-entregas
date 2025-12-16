# 🛵 Zarpa - Logística Last Mile

> **"Clicou, Zarpa."** - Solução de logística descentralizada para pequenos negócios e entregadores autônomos.

![Status](https://img.shields.io/badge/Status-Em_Desenvolvimento-yellow)
![Docker](https://img.shields.io/badge/Docker-Required-blue)
![PHP](https://img.shields.io/badge/PHP-MVC-purple)

## 📄 Sobre o Projeto

O **Zarpa** é uma aplicação web desenvolvida como Trabalho de Conclusão de Disciplina. O objetivo é criar uma plataforma que conecta diretamente estabelecimentos comerciais (como pizzarias e lanchonetes) a entregadores parceiros, eliminando intermediários e otimizando o despacho de pedidos.

O sistema foca em resolver a ineficiência da gestão manual de entregas e a falta de rotas inteligentes para motoboys.

---

## 📚 Documentação e Wiki

Toda a documentação técnica, incluindo diagramas, modelagem de banco de dados e detalhamento das entregas, está disponível na Wiki oficial do projeto:

### [👉 Acesse a Wiki do Projeto](https://github.com/LeonardoTosinPR/Zarpa-entregas/wiki)

*(Clique no link acima para ver a Descrição do Tema, Equipe e Planejamento)*


## 🚀 Como Executar o Projeto

Conforme os requisitos da disciplina, o ambiente é totalmente containerizado. Não é necessário instalar PHP ou MySQL localmente, apenas o Docker.

### Pré-requisitos
* [Docker](https://www.docker.com/) instalado.
* [Docker Compose](https://docs.docker.com/compose/) instalado.

### Passo a Passo

1.  **Clone o repositório:**
    ```bash
    git clone [https://github.com/LeonardoTosinPR/Zarpa-entregas.git)
    cd Zarpa-entregas
    ```

2.  **Suba os containers:**
    ```bash
    docker-compose up -d
    ```

3.  **Acesse a aplicação:**
    Abra o navegador e acesse:
    `http://localhost:8080` (ou a porta definida no seu docker-compose)

---
