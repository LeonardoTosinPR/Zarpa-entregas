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
}
