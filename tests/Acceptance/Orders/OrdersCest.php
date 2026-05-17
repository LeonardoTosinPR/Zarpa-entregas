<?php

namespace Tests\Acceptance\Orders;

use App\Models\Order;
use App\Models\User;
use Tests\Acceptance\BaseAcceptanceCest;
use Tests\Support\AcceptanceTester;

class OrdersCest extends BaseAcceptanceCest
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

    private function makeOrder(User $client, array $overrides = []): Order
    {
        $order = new Order(array_merge([
            'client_id'         => $client->id,
            'pickup_address'    => 'Rua de Coleta, 123',
            'delivery_address'  => 'Rua de Entrega, 456',
            'package_size'      => Order::PACKAGE_SMALL,
            'payment_method'    => Order::PAYMENT_PIX,
            'distance_km'       => '1.00',
            'confirmation_code' => 'ABC123',
        ], $overrides));
        $order->save();
        return $order;
    }

    // 1.1 - Tentativa de cadastro com dados incorretos
    public function failsToCreateOrderWithMissingPickupAddress(AcceptanceTester $page): void
    {
        $client = $this->makeClient();
        $page->login($client->email, '123456');

        $page->amOnPage('/orders/new');
        $page->fillField('order[delivery_address]', 'Rua de Entrega, 456');
        $page->click('Salvar');

        $page->see('Verifique os dados do pedido.');
    }

    // 1.2 - Tentativa de cadastro bem-sucedida
    public function createsOrderSuccessfully(AcceptanceTester $page): void
    {
        $client = $this->makeClient();
        $page->login($client->email, '123456');

        $page->amOnPage('/orders/new');
        $page->fillField('order[pickup_address]', 'Rua de Coleta, 100');
        $page->fillField('order[delivery_address]', 'Rua de Entrega, 200');
        $page->fillField('order[distance_km]', '3.5');
        $page->click('Salvar');

        $page->seeInCurrentUrl('/orders/');
        $page->see('Pedido criado com sucesso.');
    }

    // 1.3 - Tentativa de atualização com dados incorretos
    public function failsToUpdateOrderWithMissingAddresses(AcceptanceTester $page): void
    {
        $client = $this->makeClient();
        $order = $this->makeOrder($client);
        $page->login($client->email, '123456');

        $page->amOnPage('/orders/' . $order->id . '/edit');
        $page->fillField('order[pickup_address]', '');
        $page->fillField('order[delivery_address]', '');
        $page->click('Salvar');

        $page->see('Verifique os dados do pedido.');
    }

    // 1.4 - Tentativa de atualização bem-sucedida
    public function updatesOrderSuccessfully(AcceptanceTester $page): void
    {
        $client = $this->makeClient();
        $order = $this->makeOrder($client);
        $page->login($client->email, '123456');

        $page->amOnPage('/orders/' . $order->id . '/edit');
        $page->fillField('order[pickup_address]', 'Novo Endereco de Coleta, 999');
        $page->fillField('order[delivery_address]', 'Novo Endereco de Entrega, 888');
        $page->click('Salvar');

        $page->seeInCurrentUrl('/orders/' . $order->id);
        $page->see('Pedido atualizado com sucesso.');
    }

    // 1.5 - Listagem de todos os registros
    public function listsClientOrdersOnIndexPage(AcceptanceTester $page): void
    {
        $client = $this->makeClient();
        $this->makeOrder($client);
        $page->login($client->email, '123456');

        $page->amOnPage('/orders');

        $page->seeInCurrentUrl('/orders');
        $page->see('Gerenciar pedidos');
        $page->see('Rua de Coleta, 123');
    }

    // 1.6 - Remoção do registro
    public function cancelsOrderSuccessfully(AcceptanceTester $page): void
    {
        $client = $this->makeClient();
        $order = $this->makeOrder($client);
        $page->login($client->email, '123456');

        $page->amOnPage('/orders/' . $order->id);
        $page->click('Cancelar pedido');
        $page->acceptPopup();

        $page->seeInCurrentUrl('/orders');
        $page->see('Pedido cancelado com sucesso.');
    }

    // 1.7 - Tentativa de cadastro com km inválido
    public function failsToCreateOrderWithInvalidDistanceKm(AcceptanceTester $page): void
    {
        $client = $this->makeClient();
        $page->login($client->email, '123456');

        $page->amOnPage('/orders/new');
        $page->fillField('order[pickup_address]', 'Rua de Coleta, 100');
        $page->fillField('order[delivery_address]', 'Rua de Entrega, 200');
        $page->fillField('order[distance_km]', '-1');
        $page->click('Salvar');

        $page->see('Verifique os dados do pedido.');
    }

    // 1.8 - Paginação dos registros (múltiplos pedidos aparecem na listagem)
    public function listsMultipleOrdersOnIndexPage(AcceptanceTester $page): void
    {
        $client = $this->makeClient();

        $this->makeOrder($client, [
            'pickup_address'    => 'Coleta Pedido Um, 1',
            'delivery_address'  => 'Entrega Pedido Um, 1',
            'confirmation_code' => 'AAA001',
        ]);

        $this->makeOrder($client, [
            'pickup_address'    => 'Coleta Pedido Dois, 2',
            'delivery_address'  => 'Entrega Pedido Dois, 2',
            'package_size'      => Order::PACKAGE_MEDIUM,
            'payment_method'    => Order::PAYMENT_CASH,
            'confirmation_code' => 'BBB002',
        ]);

        $page->login($client->email, '123456');
        $page->amOnPage('/orders');

        $page->see('Coleta Pedido Um, 1');
        $page->see('Coleta Pedido Dois, 2');
    }
}
