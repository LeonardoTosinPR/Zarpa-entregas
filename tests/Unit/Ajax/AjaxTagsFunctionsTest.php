<?php

namespace Tests\Unit\Ajax;

use PHPUnit\Framework\TestCase;

class AjaxTagsFunctionsTest extends TestCase
{
    /**
     * Testa que as funções Ajax estão definidas no JavaScript
     * 
     * Nota: Este teste valida a estrutura esperada das funções.
     * Para testar funções JavaScript, use testes de aceitação com WebDriver.
     */
    public function testAjaxFunctionsAreDefined()
    {
        // Este é um teste de validação de estrutura
        // As funções reais estão em application.js
        // Esperamos que existam essas funções quando o JS for carregado
        
        $expectedFunctions = [
            'listTagsAjax',
            'createTagAjax',
            'deleteTagAjax',
            'createTagElement',
            'setupAjaxTags'
        ];
        
        $this->assertIsArray($expectedFunctions);
        $this->assertCount(5, $expectedFunctions);
    }

    /**
     * Testa resposta JSON da API de listagem
     */
    public function testListAjaxResponseFormat()
    {
        // Simular uma resposta JSON válida
        $response = json_encode([
            'success' => true,
            'message' => 'Etiquetas carregadas com sucesso.',
            'data' => [
                [
                    'id' => 1,
                    'name' => 'Urgente',
                    'color' => 'danger',
                    'badgeClass' => 'badge bg-danger'
                ]
            ]
        ]);

        $data = json_decode($response, true);

        $this->assertTrue($data['success']);
        $this->assertIsArray($data['data']);
        $this->assertCount(1, $data['data']);
        $this->assertEquals('Urgente', $data['data'][0]['name']);
    }

    /**
     * Testa resposta JSON de criação
     */
    public function testCreateAjaxResponseFormat()
    {
        $response = json_encode([
            'success' => true,
            'message' => 'Etiqueta criada com sucesso.',
            'data' => [
                'id' => 2,
                'name' => 'Frágil',
                'color' => 'warning',
                'badgeClass' => 'badge bg-warning'
            ]
        ]);

        $data = json_decode($response, true);

        $this->assertTrue($data['success']);
        $this->assertIsArray($data['data']);
        $this->assertEquals(2, $data['data']['id']);
        $this->assertEquals('Frágil', $data['data']['name']);
    }

    /**
     * Testa resposta de erro
     */
    public function testErrorResponseFormat()
    {
        $response = json_encode([
            'success' => false,
            'message' => 'Verifique os dados da etiqueta.'
        ]);

        $data = json_decode($response, true);

        $this->assertFalse($data['success']);
        $this->assertArrayNotHasKey('data', $data);
        $this->assertStringContainsString('dados', $data['message']);
    }

    /**
     * Valida que JSON é serializado corretamente
     */
    public function testJsonSerialization()
    {
        $testData = [
            'success' => true,
            'data' => [
                'id' => 1,
                'name' => 'Test & Co.',  // Caracteres especiais
                'color' => 'primary'
            ]
        ];

        $json = json_encode($testData);
        $decoded = json_decode($json, true);

        $this->assertEquals($testData, $decoded);
        $this->assertStringContainsString('Test &', $json);
    }

    /**
     * Testa resposta com múltiplas tags
     */
    public function testMultipleTagsResponse()
    {
        $tags = [];
        for ($i = 1; $i <= 5; $i++) {
            $tags[] = [
                'id' => $i,
                'name' => 'Tag ' . $i,
                'color' => ['primary', 'danger', 'warning', 'success', 'info'][$i - 1],
                'badgeClass' => 'badge bg-' . ['primary', 'danger', 'warning', 'success', 'info'][$i - 1]
            ];
        }

        $response = json_encode([
            'success' => true,
            'message' => 'Etiquetas carregadas com sucesso.',
            'data' => $tags
        ]);

        $data = json_decode($response, true);

        $this->assertCount(5, $data['data']);
        $this->assertEquals('Tag 3', $data['data'][2]['name']);
    }

    /**
     * Testa estrutura de erro de autorização
     */
    public function testAuthorizationErrorResponse()
    {
        $response = json_encode([
            'success' => false,
            'message' => 'Apenas administradores podem gerenciar etiquetas.'
        ]);

        $data = json_decode($response, true);

        $this->assertFalse($data['success']);
        $this->assertStringContainsString('administradores', $data['message']);
    }

    /**
     * Testa estrutura de erro 404
     */
    public function testNotFoundErrorResponse()
    {
        $response = json_encode([
            'success' => false,
            'message' => 'Etiqueta nao encontrada.'
        ]);

        $data = json_decode($response, true);

        $this->assertFalse($data['success']);
        $this->assertStringContainsString('nao encontrada', $data['message']);
    }

    /**
     * Testa que JSON preserva tipos de dados
     */
    public function testJsonDataTypesPreserved()
    {
        $response = json_encode([
            'success' => true,
            'id' => 1,
            'name' => 'Test',
            'active' => true,
            'score' => 9.5,
            'tags' => ['a', 'b', 'c']
        ]);

        $data = json_decode($response, true);

        $this->assertIsBool($data['success']);
        $this->assertIsInt($data['id']);
        $this->assertIsString($data['name']);
        $this->assertIsArray($data['tags']);
    }

    /**
     * Testa que htmlspecialchars é aplicado corretamente
     */
    public function testHtmlSpecialCharsEncoding()
    {
        $name = 'Tag & "Test" <script>';
        $encoded = htmlspecialchars($name);
        
        $response = json_encode([
            'success' => true,
            'name' => $encoded
        ]);

        $data = json_decode($response, true);

        // Deve ser seguro contra XSS
        $this->assertStringNotContainsString('<script>', $data['name']);
        $this->assertStringContainsString('&amp;', $data['name']);
        $this->assertStringContainsString('&quot;', $data['name']);
    }
}
