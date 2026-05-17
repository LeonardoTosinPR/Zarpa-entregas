<?php

namespace Tests\Unit\Models;

use App\Models\Order;
use App\Models\User;
use Tests\TestCase;

class OrderTest extends TestCase
{
    private function makeClient(array $overrides = []): User
    {
        $user = new User(array_merge([
            'name'                  => 'Cliente Teste',
            'email'                 => 'cliente@example.com',
            'cpf'                   => '52998224725',
            'password'              => '123456',
            'password_confirmation' => '123456',
            'user_type'             => User::USER_TYPE_CLIENT,
        ], $overrides));
        $user->save();
        return $user;
    }

    private function makeDeliverer(array $overrides = []): User
    {
        $user = new User(array_merge([
            'name'                  => 'Entregador Teste',
            'email'                 => 'entregador@example.com',
            'cpf'                   => '11144477735',
            'password'              => '123456',
            'password_confirmation' => '123456',
            'user_type'             => User::USER_TYPE_DELIVERER,
        ], $overrides));
        $user->save();
        return $user;
    }

    private function makeAdmin(): User
    {
        $user = new User([
            'name'                  => 'Admin Teste',
            'email'                 => 'admin@example.com',
            'cpf'                   => '11144477735',
            'password'              => '123456',
            'password_confirmation' => '123456',
            'user_type'             => User::USER_TYPE_CLIENT,
            'is_admin'              => 1,
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

    // 3.1 - validates()

    public function testSaveFailsWithoutRequiredFields(): void
    {
        $order = new Order();

        $this->assertFalse($order->save());
        $this->assertNotEmpty($order->errors('client_id'));
        $this->assertNotEmpty($order->errors('pickup_address'));
        $this->assertNotEmpty($order->errors('delivery_address'));
    }

    public function testSaveSucceedsWithValidData(): void
    {
        $client = $this->makeClient();
        $order = new Order([
            'client_id'        => $client->id,
            'pickup_address'   => 'Rua de Coleta, 123',
            'delivery_address' => 'Rua de Entrega, 456',
            'distance_km'      => '1.00',
        ]);

        $this->assertTrue($order->save());
        $this->assertNotNull($order->id);
    }

    public function testSaveFailsWithNegativeDistance(): void
    {
        $client = $this->makeClient();
        $order = new Order([
            'client_id'        => $client->id,
            'pickup_address'   => 'Rua de Coleta, 123',
            'delivery_address' => 'Rua de Entrega, 456',
            'distance_km'      => '-5',
        ]);

        $this->assertFalse($order->save());
        $this->assertNotEmpty($order->errors('distance_km'));
    }

    public function testSaveFailsWithZeroDistance(): void
    {
        $client = $this->makeClient();
        $order = new Order([
            'client_id'        => $client->id,
            'pickup_address'   => 'Rua de Coleta, 123',
            'delivery_address' => 'Rua de Entrega, 456',
            'distance_km'      => '0',
        ]);

        $this->assertFalse($order->save());
        $this->assertNotEmpty($order->errors('distance_km'));
    }

    public function testSaveFailsWithInvalidStatus(): void
    {
        $client = $this->makeClient();
        $order = new Order([
            'client_id'        => $client->id,
            'pickup_address'   => 'Rua de Coleta, 123',
            'delivery_address' => 'Rua de Entrega, 456',
            'status'           => 'invalido',
        ]);

        $this->assertFalse($order->save());
        $this->assertNotEmpty($order->errors('status'));
    }

    public function testSaveFailsWhenAcceptedStatusHasNoCourier(): void
    {
        $client = $this->makeClient();
        $order = new Order([
            'client_id'        => $client->id,
            'pickup_address'   => 'Rua de Coleta, 123',
            'delivery_address' => 'Rua de Entrega, 456',
            'status'           => Order::STATUS_ACCEPTED,
            'courier_id'       => null,
        ]);

        $this->assertFalse($order->save());
        $this->assertNotEmpty($order->errors('status'));
    }

    // 3.1 - calculateShippingFee()

    public function testShippingFeeBaseForSmallPackage(): void
    {
        $client = $this->makeClient();
        $order = new Order([
            'client_id'        => $client->id,
            'pickup_address'   => 'X',
            'delivery_address' => 'Y',
            'package_size'     => Order::PACKAGE_SMALL,
            'is_fragile'       => 0,
            'distance_km'      => '0.00',
        ]);

        $this->assertEquals('10.00', $order->calculateShippingFee());
    }

    public function testShippingFeeBaseForMediumPackage(): void
    {
        $client = $this->makeClient();
        $order = new Order([
            'client_id'        => $client->id,
            'pickup_address'   => 'X',
            'delivery_address' => 'Y',
            'package_size'     => Order::PACKAGE_MEDIUM,
            'is_fragile'       => 0,
            'distance_km'      => '0.00',
        ]);

        $this->assertEquals('15.00', $order->calculateShippingFee());
    }

    public function testShippingFeeAddsFragileSurcharge(): void
    {
        $client = $this->makeClient();
        $order = new Order([
            'client_id'        => $client->id,
            'pickup_address'   => 'X',
            'delivery_address' => 'Y',
            'package_size'     => Order::PACKAGE_SMALL,
            'is_fragile'       => 1,
            'distance_km'      => '0.00',
        ]);

        $this->assertEquals('15.00', $order->calculateShippingFee());
    }

    public function testShippingFeeAddsDistanceSurcharge(): void
    {
        $client = $this->makeClient();
        $order = new Order([
            'client_id'        => $client->id,
            'pickup_address'   => 'X',
            'delivery_address' => 'Y',
            'package_size'     => Order::PACKAGE_SMALL,
            'is_fragile'       => 0,
            'distance_km'      => '10.00',
        ]);

        // 10.00 base + 10km * 0.10/km = 11.00
        $this->assertEquals('11.00', $order->calculateShippingFee());
    }

    public function testShippingFeeCombinesAllSurcharges(): void
    {
        $client = $this->makeClient();
        $order = new Order([
            'client_id'        => $client->id,
            'pickup_address'   => 'X',
            'delivery_address' => 'Y',
            'package_size'     => Order::PACKAGE_LARGE,
            'is_fragile'       => 1,
            'distance_km'      => '20.00',
        ]);

        // 20.00 base + 5.00 fragile + 20km * 0.10 = 27.00
        $this->assertEquals('27.00', $order->calculateShippingFee());
    }

    // 3.1 - canBeViewedBy()

    public function testAdminCanViewAnyOrder(): void
    {
        $admin = $this->makeAdmin();
        $client = $this->makeClient();
        $order = $this->makeOrder($client);

        $this->assertTrue($order->canBeViewedBy($admin));
    }

    public function testClientCanViewOwnOrder(): void
    {
        $client = $this->makeClient();
        $order = $this->makeOrder($client);

        $this->assertTrue($order->canBeViewedBy($client));
    }

    public function testClientCannotViewOtherClientsOrder(): void
    {
        $owner = $this->makeClient();
        $other = $this->makeClient([
            'email' => 'outro@example.com',
            'cpf'   => '11144477735',
        ]);
        $order = $this->makeOrder($owner);

        $this->assertFalse($order->canBeViewedBy($other));
    }

    public function testDelivererCanViewPendingOrder(): void
    {
        $client = $this->makeClient();
        $deliverer = $this->makeDeliverer();
        $order = $this->makeOrder($client, ['status' => Order::STATUS_PENDING]);

        $this->assertTrue($order->canBeViewedBy($deliverer));
    }

    public function testDelivererCanViewTheirOwnAcceptedOrder(): void
    {
        $client = $this->makeClient();
        $deliverer = $this->makeDeliverer();
        $order = $this->makeOrder($client, [
            'status'     => Order::STATUS_ACCEPTED,
            'courier_id' => $deliverer->id,
        ]);

        $this->assertTrue($order->canBeViewedBy($deliverer));
    }

    // 3.1 - canBeEditedBy()

    public function testAdminCanEditAnyOrder(): void
    {
        $admin = $this->makeAdmin();
        $client = $this->makeClient();
        $order = $this->makeOrder($client);

        $this->assertTrue($order->canBeEditedBy($admin));
    }

    public function testClientCanEditOwnPendingOrder(): void
    {
        $client = $this->makeClient();
        $order = $this->makeOrder($client, ['status' => Order::STATUS_PENDING]);

        $this->assertTrue($order->canBeEditedBy($client));
    }

    public function testClientCannotEditOwnCanceledOrder(): void
    {
        $client = $this->makeClient();
        $order = $this->makeOrder($client, ['status' => Order::STATUS_CANCELED]);

        $this->assertFalse($order->canBeEditedBy($client));
    }

    public function testDelivererCanEditTheirAssignedOrder(): void
    {
        $client = $this->makeClient();
        $deliverer = $this->makeDeliverer();
        $order = $this->makeOrder($client, [
            'status'     => Order::STATUS_ACCEPTED,
            'courier_id' => $deliverer->id,
        ]);

        $this->assertTrue($order->canBeEditedBy($deliverer));
    }

    // 3.1 - canBeAcceptedBy()

    public function testDelivererCanAcceptPendingOrderWithNoCourier(): void
    {
        $client = $this->makeClient();
        $deliverer = $this->makeDeliverer();
        $order = $this->makeOrder($client, ['status' => Order::STATUS_PENDING, 'courier_id' => null]);

        $this->assertTrue($order->canBeAcceptedBy($deliverer));
    }

    public function testDelivererCannotAcceptAlreadyAcceptedOrder(): void
    {
        $client = $this->makeClient();
        $deliverer = $this->makeDeliverer();
        $order = $this->makeOrder($client, [
            'status'     => Order::STATUS_ACCEPTED,
            'courier_id' => $deliverer->id,
        ]);

        $this->assertFalse($order->canBeAcceptedBy($deliverer));
    }

    // 3.1 - canBeDeletedBy()

    public function testAdminCanDeleteAnyOrder(): void
    {
        $admin = $this->makeAdmin();
        $client = $this->makeClient();
        $order = $this->makeOrder($client);

        $this->assertTrue($order->canBeDeletedBy($admin));
    }

    public function testClientCanDeleteOwnOrder(): void
    {
        $client = $this->makeClient();
        $order = $this->makeOrder($client);

        $this->assertTrue($order->canBeDeletedBy($client));
    }

    // 3.1 - canBeRefusedBy()

    public function testDelivererCanRefuseTheirAcceptedOrder(): void
    {
        $client = $this->makeClient();
        $deliverer = $this->makeDeliverer();
        $order = $this->makeOrder($client, [
            'status'     => Order::STATUS_ACCEPTED,
            'courier_id' => $deliverer->id,
        ]);

        $this->assertTrue($order->canBeRefusedBy($deliverer));
    }

    public function testDelivererCanRefuseTheirOnRouteOrder(): void
    {
        $client = $this->makeClient();
        $deliverer = $this->makeDeliverer();
        $order = $this->makeOrder($client, [
            'status'     => Order::STATUS_ON_ROUTE,
            'courier_id' => $deliverer->id,
        ]);

        $this->assertTrue($order->canBeRefusedBy($deliverer));
    }

    public function testDelivererCannotRefusePendingOrder(): void
    {
        $client = $this->makeClient();
        $deliverer = $this->makeDeliverer();
        $order = $this->makeOrder($client, ['status' => Order::STATUS_PENDING]);

        $this->assertFalse($order->canBeRefusedBy($deliverer));
    }

    // 3.1 - isFragile()

    public function testIsFragileReturnsTrueWhenSet(): void
    {
        $client = $this->makeClient();
        $order = $this->makeOrder($client, ['is_fragile' => 1]);

        $this->assertTrue($order->isFragile());
    }

    public function testIsFragileReturnsFalseWhenNotSet(): void
    {
        $client = $this->makeClient();
        $order = $this->makeOrder($client, ['is_fragile' => 0]);

        $this->assertFalse($order->isFragile());
    }

    // 3.1 - visibleFor()

    public function testVisibleForAdminReturnsAllOrders(): void
    {
        $admin = $this->makeAdmin();
        $client = $this->makeClient();
        $this->makeOrder($client);
        $this->makeOrder($client, ['confirmation_code' => 'DEF456']);

        $orders = Order::visibleFor($admin);

        $this->assertCount(2, $orders);
    }

    public function testVisibleForClientReturnsOnlyOwnOrders(): void
    {
        $client = $this->makeClient();
        $other = $this->makeClient([
            'email' => 'outro@example.com',
            'cpf'   => '11144477735',
        ]);
        $this->makeOrder($client);
        $this->makeOrder($other, ['confirmation_code' => 'DEF456']);

        $ownOrders = Order::visibleFor($client);

        $this->assertCount(1, $ownOrders);
        $this->assertEquals($client->id, $ownOrders[0]->client_id);
    }

    public function testVisibleForDelivererReturnsPendingAndAssignedOrders(): void
    {
        $client = $this->makeClient();
        $deliverer = $this->makeDeliverer();

        $pending = $this->makeOrder($client, ['confirmation_code' => 'PND001']);
        $assigned = $this->makeOrder($client, [
            'status'            => Order::STATUS_ACCEPTED,
            'courier_id'        => $deliverer->id,
            'confirmation_code' => 'ACC001',
        ]);

        $visible = Order::visibleFor($deliverer);

        $ids = array_map(fn($o) => $o->id, $visible);
        $this->assertContains($pending->id, $ids);
        $this->assertContains($assigned->id, $ids);
    }
}
