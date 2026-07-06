<?php
/**
 * Exemplos de Uso de Ajax no Projeto Zarpa Entregas
 * 
 * Este arquivo contém exemplos práticos de como usar Ajax
 * para fazer requisições assíncronas ao servidor.
 */

/**
 * ============================================================================
 * EXEMPLO 1: Listar Recursos (GET)
 * ============================================================================
 */

// JavaScript no cliente
?>
<script>
// Exemplo 1A: Usando Fetch API com .then()
function exemplo1_ListarTagsComThen() {
    console.log('🔄 Iniciando requisição GET /api/tags');
    
    fetch('/api/tags', {
        method: 'GET',
        headers: {
            'Accept': 'application/json'
        }
    })
    .then(response => {
        console.log('📥 Status:', response.status);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('✅ Sucesso! Tags recebidas:', data);
        if (data.success) {
            console.log('Tags:', data.data);
            // Processar tags aqui
            renderizarTags(data.data);
        }
    })
    .catch(error => {
        console.error('❌ Erro:', error.message);
        // Mostrar mensagem de erro ao usuário
        mostrarErro('Erro ao carregar tags: ' + error.message);
    });
}

// Exemplo 1B: Usando async/await (mais moderno)
async function exemplo1_ListarTagsComAsyncAwait() {
    try {
        console.log('🔄 Iniciando requisição GET /api/tags');
        
        const response = await fetch('/api/tags', {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            }
        });
        
        console.log('📥 Status:', response.status);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        console.log('✅ Sucesso! Tags:', data);
        
        if (data.success) {
            renderizarTags(data.data);
        }
    } catch (error) {
        console.error('❌ Erro:', error);
        mostrarErro('Erro ao carregar tags');
    }
}

// Executar exemplos
document.addEventListener('DOMContentLoaded', function() {
    // exemplo1_ListarTagsComThen();
    // exemplo1_ListarTagsComAsyncAwait();
});
</script>

<?php
/**
 * ============================================================================
 * EXEMPLO 2: Criar Recurso (POST)
 * ============================================================================
 */
?>
<script>
// Exemplo 2A: POST com FormData
function exemplo2_CriarTagComFormData() {
    const formData = new FormData();
    formData.append('tag[name]', 'Urgente');
    formData.append('tag[color]', 'danger');
    
    console.log('🔄 Enviando POST para /api/tags');
    
    fetch('/api/tags', {
        method: 'POST',
        headers: {
            'Accept': 'application/json'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('✅ Tag criada:', data.data);
            alert('Tag criada com ID: ' + data.data.id);
        } else {
            console.error('❌ Erro:', data.message);
            alert('Erro: ' + data.message);
        }
    });
}

// Exemplo 2B: POST com URLSearchParams
function exemplo2_CriarTagComURLSearchParams() {
    const params = new URLSearchParams();
    params.append('tag[name]', 'Frágil');
    params.append('tag[color]', 'warning');
    
    console.log('🔄 Enviando POST com URLSearchParams');
    
    fetch('/api/tags', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'Accept': 'application/json'
        },
        body: params
    })
    .then(response => response.json())
    .then(data => {
        console.log('Resposta:', data);
    });
}

// Exemplo 2C: POST com JSON
function exemplo2_CriarTagComJSON() {
    const tagData = {
        'tag[name]': 'Entrega Rápida',
        'tag[color]': 'success'
    };
    
    console.log('🔄 Enviando POST com JSON');
    
    fetch('/api/tags', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify(tagData)
    })
    .then(response => response.json())
    .then(data => {
        console.log('Resposta:', data);
    });
}

// Exemplo 2D: Integração com formulário HTML
document.addEventListener('DOMContentLoaded', function() {
    // Encontrar formulário com atributo data-ajax
    const form = document.querySelector('form[data-ajax="true"]');
    if (!form) return;
    
    form.addEventListener('submit', async function(event) {
        event.preventDefault(); // Impedir envio tradicional
        
        console.log('🔄 Enviando formulário via Ajax');
        
        // Coletar dados do formulário
        const formData = new FormData(form);
        
        try {
            const response = await fetch(form.action, {
                method: form.method.toUpperCase(),
                body: formData,
                headers: {
                    'Accept': 'application/json'
                }
            });
            
            const data = await response.json();
            
            if (data.success) {
                console.log('✅ Sucesso:', data.message);
                form.reset(); // Limpar formulário
                // Recarregar lista ou fazer outra ação
            } else {
                console.error('❌ Erro:', data.message);
            }
        } catch (error) {
            console.error('❌ Erro de rede:', error);
        }
    });
});
</script>

<?php
/**
 * ============================================================================
 * EXEMPLO 3: Deletar Recurso (DELETE)
 * ============================================================================
 */
?>
<script>
// Exemplo 3: DELETE
function exemplo3_DeletarTag(tagId) {
    console.log('🔄 Enviando DELETE para /api/tags/' + tagId);
    
    if (!confirm('Confirma deleção?')) {
        console.log('❌ Deleção cancelada');
        return;
    }
    
    fetch('/api/tags/' + tagId, {
        method: 'DELETE',
        headers: {
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('✅ Deletado:', data.message);
            // Remover elemento do DOM
            const elemento = document.getElementById('tag-' + tagId);
            if (elemento) {
                elemento.remove();
            }
        } else {
            console.error('❌ Erro:', data.message);
            alert('Erro: ' + data.message);
        }
    });
}
</script>

<?php
/**
 * ============================================================================
 * EXEMPLO 4: Tratamento de Erros
 * ============================================================================
 */
?>
<script>
// Exemplo 4: Tratamento de Erros
async function exemplo4_TratamentoDeErros() {
    try {
        const response = await fetch('/api/tags');
        
        // Verificar status HTTP
        if (response.status === 403) {
            throw new Error('Você não tem permissão para fazer esta operação');
        }
        if (response.status === 404) {
            throw new Error('Recurso não encontrado');
        }
        if (!response.ok) {
            throw new Error(`Erro HTTP: ${response.status}`);
        }
        
        const data = await response.json();
        
        // Verificar sucesso da aplicação
        if (!data.success) {
            throw new Error(data.message || 'Erro na operação');
        }
        
        console.log('✅ Tudo bem:', data.data);
        
    } catch (error) {
        console.error('❌ Erro tratado:', error.message);
        
        // Mostrar mensagem amigável ao usuário
        mostrarErro(error.message);
    }
}

function mostrarErro(mensagem) {
    const alerta = document.createElement('div');
    alerta.className = 'alert alert-danger alert-dismissible fade show';
    alerta.innerHTML = `
        ${mensagem}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.body.insertBefore(alerta, document.body.firstChild);
}
</script>

<?php
/**
 * ============================================================================
 * EXEMPLO 5: Estados de Requisição (readyState)
 * ============================================================================
 */
?>
<script>
// Exemplo 5: Monitorar estados com XMLHttpRequest
function exemplo5_MonitorarEstados() {
    const xhr = new XMLHttpRequest();
    
    xhr.addEventListener('readystatechange', function() {
        const estados = {
            0: 'UNSENT',
            1: 'OPENED',
            2: 'HEADERS_RECEIVED',
            3: 'LOADING',
            4: 'DONE'
        };
        
        console.log('Estado:', xhr.readyState, estados[xhr.readyState]);
        
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                console.log('✅ Completo! Resposta:', xhr.responseText);
            } else {
                console.error('❌ Erro:', xhr.status);
            }
        }
    });
    
    console.log('🔄 Abrindo conexão');
    xhr.open('GET', '/api/tags');
    console.log('Estado após open:', xhr.readyState); // 1 - OPENED
    
    console.log('🔄 Enviando requisição');
    xhr.send();
    console.log('Estado após send:', xhr.readyState); // 1 - OPENED (ainda)
}

// Exemplo 5B: Monitorar progresso
function exemplo5_MonitorarProgresso() {
    const xhr = new XMLHttpRequest();
    
    xhr.addEventListener('loadstart', function() {
        console.log('🔄 Iniciou carregamento');
    });
    
    xhr.addEventListener('progress', function(event) {
        if (event.lengthComputable) {
            const percentual = (event.loaded / event.total) * 100;
            console.log('Progresso:', percentual.toFixed(2) + '%');
        }
    });
    
    xhr.addEventListener('loadend', function() {
        console.log('✅ Carregamento terminado');
    });
    
    xhr.open('GET', '/api/tags');
    xhr.send();
}
</script>

<?php
/**
 * ============================================================================
 * EXEMPLO 6: Requisições Paralelas
 * ============================================================================
 */
?>
<script>
// Exemplo 6: Fazer múltiplas requisições em paralelo
async function exemplo6_RequisicoesParlalelas() {
    console.log('🔄 Iniciando 3 requisições em paralelo');
    
    try {
        // Promise.all aguarda TODAS as requisições
        const [tagsResp, ordersResp, usersResp] = await Promise.all([
            fetch('/api/tags').then(r => r.json()),
            fetch('/api/orders').then(r => r.json()),
            fetch('/api/users').then(r => r.json())
        ]);
        
        console.log('✅ Todas as requisições completaram:');
        console.log('Tags:', tagsResp);
        console.log('Orders:', ordersResp);
        console.log('Users:', usersResp);
        
    } catch (error) {
        console.error('❌ Uma ou mais requisições falharam:', error);
    }
}

// Exemplo 6B: Promise.race (primeira a terminar)
async function exemplo6_PrimeiraATerminar() {
    try {
        const resultado = await Promise.race([
            fetch('/api/tags').then(r => r.json()),
            fetch('/api/orders').then(r => r.json())
        ]);
        
        console.log('✅ Primeira requisição a terminar:', resultado);
        
    } catch (error) {
        console.error('❌ Erro:', error);
    }
}
</script>

<?php
/**
 * ============================================================================
 * EXEMPLO 7: Validação de Requisição Síncrona vs Assíncrona
 * ============================================================================
 */
?>
<script>
// ❌ EVITAR: Requisição Síncrona (bloqueia interface)
function exemplo7_SincronoNAOUSE() {
    const xhr = new XMLHttpRequest();
    xhr.open('GET', '/api/tags', false); // false = síncrono
    xhr.send();
    
    // Interface fica travada até aqui!
    const data = JSON.parse(xhr.responseText);
    console.log('Dados:', data); // Pode demorar muito tempo
    
    // Enquanto isso, página fica congelada
}

// ✅ USAR: Requisição Assíncrona
async function exemplo7_AssincronoUSE() {
    // Interface continua responsiva
    console.log('🔄 Iniciando requisição...');
    
    const response = await fetch('/api/tags');
    const data = await response.json();
    
    console.log('✅ Dados recebidos:', data);
    // Interface nunca ficou bloqueada!
}

// Demonstração: verificar que async não bloqueia
function exemplo7_Demonstracao() {
    console.log('1️⃣ Antes da requisição');
    
    fetch('/api/tags')
        .then(r => r.json())
        .then(data => console.log('3️⃣ Depois de receber'));
    
    console.log('2️⃣ Após iniciar requisição (não bloqueou!)');
}
</script>

<?php
/**
 * ============================================================================
 * EXEMPLO 8: Content-Type e Headers
 * ============================================================================
 */
?>
<script>
// Exemplo 8: Diferentes Content-Types
function exemplo8_ContentTypes() {
    // 8A: Form-urlencoded (padrão)
    fetch('/api/tags', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: 'tag[name]=Urgente&tag[color]=danger'
    });
    
    // 8B: JSON
    fetch('/api/tags', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            'tag[name]': 'Urgente',
            'tag[color]': 'danger'
        })
    });
    
    // 8C: FormData (multipart/form-data - para arquivos)
    const formData = new FormData();
    formData.append('tag[name]', 'Urgente');
    formData.append('arquivo', document.getElementById('file').files[0]);
    
    fetch('/api/tags', {
        method: 'POST',
        body: formData
        // Não definir Content-Type - o navegador configura automaticamente
    });
}

// Exemplo 8D: Validar Content-Type da resposta
async function exemplo8_ValidarResposta() {
    const response = await fetch('/api/tags');
    
    const contentType = response.headers.get('content-type');
    console.log('Content-Type:', contentType);
    
    if (contentType && contentType.includes('application/json')) {
        const data = await response.json();
        console.log('✅ JSON válido:', data);
    } else {
        const text = await response.text();
        console.log('❌ Não é JSON:', text);
    }
}
</script>

<?php
/**
 * ============================================================================
 * EXEMPLO 9: Funções Auxiliares Úteis
 * ============================================================================
 */
?>
<script>
// Função auxiliar: Fazer requisição com tratamento padrão
async function fazerRequisicao(url, opcoes = {}) {
    const defaultOpcoes = {
        method: 'GET',
        headers: {
            'Accept': 'application/json'
        }
    };
    
    const configFinal = { ...defaultOpcoes, ...opcoes };
    
    try {
        console.log(`🔄 ${configFinal.method} ${url}`);
        
        const response = await fetch(url, configFinal);
        const data = await response.json();
        
        if (!response.ok) {
            throw new Error(data.message || `HTTP ${response.status}`);
        }
        
        if (!data.success) {
            throw new Error(data.message || 'Operação falhou');
        }
        
        console.log('✅ Sucesso:', data);
        return data;
        
    } catch (error) {
        console.error('❌ Erro:', error);
        throw error;
    }
}

// Usar função auxiliar
async function exemplo9_UsarAuxiliar() {
    try {
        // GET
        const tags = await fazerRequisicao('/api/tags');
        console.log('Tags:', tags.data);
        
        // POST
        const novaTag = await fazerRequisicao('/api/tags', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'Accept': 'application/json'
            },
            body: new URLSearchParams({
                'tag[name]': 'Teste',
                'tag[color]': 'primary'
            })
        });
        console.log('Tag criada:', novaTag.data);
        
    } catch (error) {
        console.error('Falha:', error.message);
    }
}
</script>

<?php
/**
 * ============================================================================
 * RESUMO DOS CONCEITOS
 * ============================================================================
 * 
 * 1. FETCH API (moderno):
 *    - Retorna uma Promise
 *    - Suporta async/await
 *    - Sintaxe mais limpa
 * 
 * 2. XMLHttpRequest (legado):
 *    - Callback baseado em eventos
 *    - Mais controle granular
 *    - Suporta progresso de upload/download
 * 
 * 3. MÉTODOS HTTP:
 *    - GET: Ler dados
 *    - POST: Criar dados
 *    - PUT/PATCH: Atualizar dados
 *    - DELETE: Deletar dados
 * 
 * 4. ESTADOS (readyState):
 *    - 0: UNSENT (não iniciado)
 *    - 1: OPENED (conexão aberta)
 *    - 2: HEADERS_RECEIVED (headers recebidos)
 *    - 3: LOADING (corpo sendo baixado)
 *    - 4: DONE (completo)
 * 
 * 5. STATUS HTTP:
 *    - 2xx: Sucesso
 *    - 3xx: Redirecionamento
 *    - 4xx: Erro do cliente
 *    - 5xx: Erro do servidor
 * 
 * 6. SÍNCRONO vs ASSÍNCRONO:
 *    - Síncrono: Bloqueia até responder (❌ evitar)
 *    - Assíncrono: Interface continua responsiva (✅ usar)
 * 
 * 7. CONTENT-TYPE:
 *    - application/json: Dados JSON
 *    - application/x-www-form-urlencoded: Formulário
 *    - multipart/form-data: Arquivos
 * 
 * 8. JSON:
 *    - Formato lightweight
 *    - JSON.stringify(): Converter para string
 *    - JSON.parse(): Converter para objeto
 */
?>
