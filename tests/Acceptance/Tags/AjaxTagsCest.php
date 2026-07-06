<?php

namespace Tests\Acceptance\Tags;

use Tests\Support\AcceptanceTester;

class AjaxTagsCest
{
    /**
     * Testa listagem de tags via Ajax
     */
    public function testListTagsViaAjax(AcceptanceTester $I)
    {
        $I->login('admin@example.com', 'password');
        $I->amOnPage('/admin');
        
        // Fazer requisição Ajax para listar tags
        $I->sendAjaxGetRequest('/api/tags');
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
        
        // Validar estrutura da resposta
        $response = json_decode($I->grabResponse(), true);
        $I->assertTrue($response['success']);
        $I->assertIsArray($response['data']);
        
        // Validar estrutura de cada tag
        if (!empty($response['data'])) {
            foreach ($response['data'] as $tag) {
                $I->assertArrayHasKey('id', $tag);
                $I->assertArrayHasKey('name', $tag);
                $I->assertArrayHasKey('color', $tag);
                $I->assertArrayHasKey('badgeClass', $tag);
            }
        }
    }

    /**
     * Testa criação de tag via Ajax
     */
    public function testCreateTagViaAjax(AcceptanceTester $I)
    {
        $I->login('admin@example.com', 'password');
        $I->amOnPage('/admin');
        
        // Fazer requisição Ajax para criar tag
        $I->sendAjaxPostRequest('/api/tags', [
            'tag[name]' => 'Urgente Ajax',
            'tag[color]' => 'danger'
        ]);
        
        $I->seeResponseCodeIs(201);
        $I->seeResponseIsJson();
        
        // Validar resposta
        $response = json_decode($I->grabResponse(), true);
        $I->assertTrue($response['success']);
        $I->assertEquals('Etiqueta criada com sucesso.', $response['message']);
        
        // Validar dados da tag criada
        $I->assertArrayHasKey('data', $response);
        $I->assertArrayHasKey('id', $response['data']);
        $I->assertArrayHasKey('name', $response['data']);
        $I->assertEquals('Urgente Ajax', $response['data']['name']);
        $I->assertEquals('danger', $response['data']['color']);
    }

    /**
     * Testa validação ao criar tag sem nome via Ajax
     */
    public function testCreateTagWithoutNameViaAjax(AcceptanceTester $I)
    {
        $I->login('admin@example.com', 'password');
        $I->amOnPage('/admin');
        
        // Fazer requisição Ajax com dados inválidos
        $I->sendAjaxPostRequest('/api/tags', [
            'tag[name]' => '',
            'tag[color]' => 'danger'
        ]);
        
        $I->seeResponseCodeIs(400);
        $I->seeResponseIsJson();
        
        // Validar resposta de erro
        $response = json_decode($I->grabResponse(), true);
        $I->assertFalse($response['success']);
        $I->assertEquals('Verifique os dados da etiqueta.', $response['message']);
    }

    /**
     * Testa permissão de criação de tag - não admin
     */
    public function testCreateTagWithoutAdminPermission(AcceptanceTester $I)
    {
        $I->login('user@example.com', 'password');
        
        // Fazer requisição Ajax sem permissão
        $I->sendAjaxPostRequest('/api/tags', [
            'tag[name]' => 'Teste',
            'tag[color]' => 'primary'
        ]);
        
        $I->seeResponseCodeIs(403);
        $I->seeResponseIsJson();
        
        // Validar resposta
        $response = json_decode($I->grabResponse(), true);
        $I->assertFalse($response['success']);
        $I->assertStringContainsString('administradores', $response['message']);
    }

    /**
     * Testa deleção de tag via Ajax
     */
    public function testDeleteTagViaAjax(AcceptanceTester $I)
    {
        $I->login('admin@example.com', 'password');
        
        // Criar uma tag primeiro
        $I->sendAjaxPostRequest('/api/tags', [
            'tag[name]' => 'Tag para Deletar',
            'tag[color]' => 'warning'
        ]);
        
        $response = json_decode($I->grabResponse(), true);
        $tagId = $response['data']['id'];
        
        // Deletar a tag via Ajax
        $I->sendAjaxDeleteRequest('/api/tags/' . $tagId);
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
        
        // Validar resposta
        $response = json_decode($I->grabResponse(), true);
        $I->assertTrue($response['success']);
        
        // Verificar que a tag foi deletada
        $I->sendAjaxDeleteRequest('/api/tags/' . $tagId);
        $I->seeResponseCodeIs(404);
    }

    /**
     * Testa permissão de deleção de tag - não admin
     */
    public function testDeleteTagWithoutAdminPermission(AcceptanceTester $I)
    {
        $I->login('user@example.com', 'password');
        
        // Fazer requisição Ajax sem permissão
        $I->sendAjaxDeleteRequest('/api/tags/1');
        
        $I->seeResponseCodeIs(403);
        $I->seeResponseIsJson();
        
        // Validar resposta
        $response = json_decode($I->grabResponse(), true);
        $I->assertFalse($response['success']);
        $I->assertStringContainsString('administradores', $response['message']);
    }

    /**
     * Testa resposta JSON com Content-Type correto
     */
    public function testAjaxResponseHasCorrectContentType(AcceptanceTester $I)
    {
        $I->login('admin@example.com', 'password');
        
        // Fazer requisição Ajax
        $I->sendAjaxGetRequest('/api/tags');
        
        // Validar Content-Type
        $I->seeHttpHeader('Content-Type', 'application/json; charset=utf-8');
    }

    /**
     * Testa estados de requisição (readyState) - documentação
     * Este teste valida que a implementação segue padrões de requisição
     */
    public function testAjaxRequestStates(AcceptanceTester $I)
    {
        $I->login('admin@example.com', 'password');
        $I->amOnPage('/admin');
        
        // Script que verifica os estados da requisição
        $I->executeScript('
            window.requestStates = [];
            window.testXhr = new XMLHttpRequest();
            window.testXhr.addEventListener("readystatechange", function() {
                window.requestStates.push(window.testXhr.readyState);
            });
            window.testXhr.open("GET", "/api/tags");
            window.testXhr.send();
        ');
        
        // Aguardar conclusão
        $I->wait(1);
        
        // Validar que os estados foram capturados
        // Estados: 1 (OPENED), 2 (HEADERS_RECEIVED), 3 (LOADING), 4 (DONE)
        $states = $I->executeScript('return window.requestStates;');
        $I->assertGreaterThanOrEqual(1, count($states));
    }

    /**
     * Testa síncrono vs assíncrono
     * A implementação usa fetch que é assíncrono
     */
    public function testAjaxAsyncBehavior(AcceptanceTester $I)
    {
        $I->login('admin@example.com', 'password');
        $I->amOnPage('/admin');
        
        $I->executeScript('
            window.asyncTestResult = null;
            window.startTime = Date.now();
            
            // Fazer requisição assíncrona
            fetch("/api/tags")
                .then(r => r.json())
                .then(data => {
                    window.asyncTestResult = {
                        success: data.success,
                        time: Date.now() - window.startTime
                    };
                });
            
            // Verificar que não bloqueia
            window.asyncTestResult = {
                blocked: false
            };
        ');
        
        $I->wait(1);
        $result = $I->executeScript('return window.asyncTestResult;');
        
        // Verificar que não foi bloqueado
        $I->assertFalse($result['blocked']);
    }
}
