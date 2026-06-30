<?php

namespace Tests\Unit\Models;

use App\Models\Order;
use App\Models\OrderDeliveryPhoto;
use App\Models\User;
use Tests\TestCase;

class OrderDeliveryPhotoTest extends TestCase
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

    private function makeOrder(): Order
    {
        $order = new Order([
            'client_id' => $this->makeClient()->id,
            'pickup_address' => 'Rua de Coleta, 123',
            'delivery_address' => 'Rua de Entrega, 456',
            'distance_km' => '1.00',
        ]);
        $order->save();
        return $order;
    }

    public function testSaveFailsWithoutRequiredFields(): void
    {
        $photo = new OrderDeliveryPhoto();

        $this->assertFalse($photo->save());
        $this->assertNotEmpty($photo->errors('order_id'));
        $this->assertNotEmpty($photo->errors('file_name'));
        $this->assertNotEmpty($photo->errors('mime_type'));
    }

    public function testSaveFailsWithInvalidMimeType(): void
    {
        $order = $this->makeOrder();
        $photo = new OrderDeliveryPhoto([
            'order_id' => $order->id,
            'file_name' => 'comprovante.txt',
            'original_name' => 'comprovante.txt',
            'mime_type' => 'text/plain',
            'size_bytes' => 100,
        ]);

        $this->assertFalse($photo->save());
        $this->assertNotEmpty($photo->errors('mime_type'));
    }

    public function testOrderHasManyDeliveryPhotos(): void
    {
        $order = $this->makeOrder();
        $photo = new OrderDeliveryPhoto([
            'order_id' => $order->id,
            'file_name' => 'comprovante.png',
            'original_name' => 'comprovante.png',
            'mime_type' => OrderDeliveryPhoto::MIME_PNG,
            'size_bytes' => 100,
        ]);
        $photo->save();

        $photos = $order->deliveryPhotos()->get();

        $this->assertCount(1, $photos);
        $this->assertEquals($photo->id, $photos[0]->id);
    }

    public function testPhotoBelongsToOrder(): void
    {
        $order = $this->makeOrder();
        $photo = new OrderDeliveryPhoto([
            'order_id' => $order->id,
            'file_name' => 'comprovante.png',
            'original_name' => 'comprovante.png',
            'mime_type' => OrderDeliveryPhoto::MIME_PNG,
            'size_bytes' => 100,
        ]);
        $photo->save();

        $this->assertEquals($order->id, $photo->order->id);
    }
}
