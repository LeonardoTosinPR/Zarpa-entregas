<?php

namespace Tests\Acceptance\Tags;

use App\Models\Order;
use App\Models\Tag;
use App\Models\User;
use Tests\Acceptance\BaseAcceptanceCest;
use Tests\Support\AcceptanceTester;

class TagsCest extends BaseAcceptanceCest
{
    private function makeAdmin(): User
    {
        $user = new User([
            'name'                  => 'Admin Teste',
            'email'                 => 'admin@example.com',
            'cpf'                   => '39053344705',
            'password'              => '123456',
            'password_confirmation' => '123456',
            'user_type'             => User::USER_TYPE_CLIENT,
            'is_admin'              => 1,
        ]);
        $user->save();
        return $user;
    }

    private function makeOrderFor(User $client): Order
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

    // 1 - Registro do recurso
    public function adminCreatesTag(AcceptanceTester $page): void
    {
        $admin = $this->makeAdmin();
        $page->login($admin->email, '123456');

        $page->amOnPage('/tags');
        $page->fillField('tag[name]', 'Prioritario');
        $page->click('Criar');

        $page->see('Etiqueta criada com sucesso.');
        $page->see('Prioritario');
    }

    // 4 - Remover registros com dependências
    public function adminRemovesTagWithDependencies(AcceptanceTester $page): void
    {
        $admin = $this->makeAdmin();
        $order = $this->makeOrderFor($admin);
        $tag = new Tag(['name' => 'Urgente', 'color' => Tag::COLOR_DANGER]);
        $tag->save();
        $order->tags()->attach((int) $tag->id);

        $page->login($admin->email, '123456');
        $page->amOnPage('/tags');
        $page->click("button[title='Remover etiqueta']");
        $page->acceptPopup();

        $page->see('Etiqueta removida. Os vinculos com os pedidos tambem foram apagados.');
    }
}
