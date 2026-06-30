<?php

namespace Tests\Acceptance\Tags;

use App\Models\Order;
use App\Models\Tag;
use App\Models\User;
use Tests\Acceptance\BaseAcceptanceCest;
use Tests\Support\AcceptanceTester;

class OrderTagsCest extends BaseAcceptanceCest
{
    private function makeClient(): User
    {
        $user = new User([
            'name'                  => 'Cliente Teste',
            'email'                 => 'cliente@example.com',
            'cpf'                   => '52998224725',
            'password'              => '123456',
            'password_confirmation' => '123456',
            'user_type'             => User::USER_TYPE_CLIENT,
        ]);
        $user->save();
        return $user;
    }

    private function makeOrder(User $client): Order
    {
        $order = new Order([
            'client_id'         => $client->id,
            'pickup_address'    => 'Rua de Coleta, 123',
            'delivery_address'  => 'Rua de Entrega, 456',
            'distance_km'       => '1.00',
            'confirmation_code' => 'ABC123',
        ]);
        $order->save();
        return $order;
    }

    private function makeTag(string $name = 'Urgente'): Tag
    {
        $tag = new Tag(['name' => $name, 'color' => Tag::COLOR_DANGER]);
        $tag->save();
        return $tag;
    }

    // 1.1 - Registro da relação
    public function attachesTagToOrderSuccessfully(AcceptanceTester $page): void
    {
        $client = $this->makeClient();
        $order = $this->makeOrder($client);
        $this->makeTag('Urgente');
        $page->login($client->email, '123456');

        $page->amOnPage('/orders/' . $order->id);
        $page->selectOption('tag_id', 'Urgente');
        $page->click('Adicionar');

        $page->see('Etiqueta vinculada ao pedido.');
        $page->see('Urgente');
    }

    // 1.2 - Visualização da relação
    public function viewsOrderTags(AcceptanceTester $page): void
    {
        $client = $this->makeClient();
        $order = $this->makeOrder($client);
        $tag = $this->makeTag('Refrigerado');
        $order->tags()->attach((int) $tag->id);
        $page->login($client->email, '123456');

        $page->amOnPage('/orders/' . $order->id);

        $page->see('Etiquetas');
        $page->see('Refrigerado');
    }

    // 1.3 - Remoção da relação
    public function detachesTagFromOrder(AcceptanceTester $page): void
    {
        $client = $this->makeClient();
        $order = $this->makeOrder($client);
        $tag = $this->makeTag('Fragil');
        $order->tags()->attach((int) $tag->id);
        $page->login($client->email, '123456');

        $page->amOnPage('/orders/' . $order->id);
        $page->click("button[title='Remover etiqueta']");
        $page->acceptPopup();

        $page->see('Etiqueta removida do pedido.');
    }
}
