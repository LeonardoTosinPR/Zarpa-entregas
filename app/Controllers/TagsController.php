<?php

namespace App\Controllers;

use App\Models\Tag;
use Core\Http\Controllers\Controller;
use Core\Http\Request;
use Lib\FlashMessage;

class TagsController extends Controller
{
    public function index(): void
    {
        $title = 'Etiquetas';
        $tags = Tag::orderedByName();

        $this->render('tags/index', compact('title', 'tags'));
    }

    public function create(Request $request): void
    {
        if (!$this->currentUser()->isAdmin()) {
            FlashMessage::danger('Apenas administradores podem gerenciar etiquetas.');
            $this->redirectTo(route('tags.index'));
        }

        $params = $request->getParam('tag', []);
        $tag = new Tag([
            'name' => trim($params['name'] ?? ''),
            'color' => $params['color'] ?? Tag::COLOR_SECONDARY
        ]);

        if ($tag->save()) {
            FlashMessage::success('Etiqueta criada com sucesso.');
        } else {
            FlashMessage::danger('Verifique os dados da etiqueta.');
        }

        $this->redirectTo(route('tags.index'));
    }

    public function destroy(Request $request): void
    {
        if (!$this->currentUser()->isAdmin()) {
            FlashMessage::danger('Apenas administradores podem gerenciar etiquetas.');
            $this->redirectTo(route('tags.index'));
        }

        $tag = Tag::findById((int) $request->getParam('id'));

        if ($tag === null) {
            FlashMessage::danger('Etiqueta nao encontrada.');
            $this->redirectTo(route('tags.index'));
        }

        if ($tag->destroy()) {
            FlashMessage::success('Etiqueta removida. Os vinculos com os pedidos tambem foram apagados.');
        } else {
            FlashMessage::danger('Nao foi possivel remover a etiqueta.');
        }

        $this->redirectTo(route('tags.index'));
    }

    /**
     * Retorna todas as tags em formato JSON para requisições Ajax
     */
    public function listAjax(): void
    {
        $tags = Tag::orderedByName();
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

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($json);
    }

    /**
     * Cria uma tag via requisição Ajax
     */
    public function createAjax(Request $request): void
    {
        if (!$this->currentUser()->isAdmin()) {
            http_response_code(403);
            $json = [
                'success' => false,
                'message' => 'Apenas administradores podem gerenciar etiquetas.'
            ];
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($json);
            return;
        }

        $params = $request->getParam('tag', []);
        $tag = new Tag([
            'name' => trim($params['name'] ?? ''),
            'color' => $params['color'] ?? Tag::COLOR_SECONDARY
        ]);

        if ($tag->save()) {
            http_response_code(201);
            $json = [
                'success' => true,
                'message' => 'Etiqueta criada com sucesso.',
                'data' => [
                    'id' => $tag->id,
                    'name' => htmlspecialchars($tag->name),
                    'color' => $tag->color,
                    'badgeClass' => $tag->badgeClass()
                ]
            ];
        } else {
            http_response_code(400);
            $json = [
                'success' => false,
                'message' => 'Verifique os dados da etiqueta.'
            ];
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($json);
    }

    /**
     * Remove uma tag via requisição Ajax
     */
    public function destroyAjax(Request $request): void
    {
        if (!$this->currentUser()->isAdmin()) {
            http_response_code(403);
            $json = [
                'success' => false,
                'message' => 'Apenas administradores podem gerenciar etiquetas.'
            ];
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($json);
            return;
        }

        $tag = Tag::findById((int) $request->getParam('id'));

        if ($tag === null) {
            http_response_code(404);
            $json = [
                'success' => false,
                'message' => 'Etiqueta nao encontrada.'
            ];
        } elseif ($tag->destroy()) {
            http_response_code(200);
            $json = [
                'success' => true,
                'message' => 'Etiqueta removida. Os vinculos com os pedidos tambem foram apagados.'
            ];
        } else {
            http_response_code(500);
            $json = [
                'success' => false,
                'message' => 'Nao foi possivel remover a etiqueta.'
            ];
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($json);
    }
}
