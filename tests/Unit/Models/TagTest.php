<?php

namespace Tests\Unit\Models;

use App\Models\Order;
use App\Models\Tag;
use App\Models\User;
use PDOException;
use Tests\TestCase;

class TagTest extends TestCase
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
            'client_id'        => $client->id,
            'pickup_address'   => 'Rua de Coleta, 123',
            'delivery_address' => 'Rua de Entrega, 456',
            'distance_km'      => '1.00',
        ]);
        $order->save();
        return $order;
    }

    private function makeTag(array $overrides = []): Tag
    {
        $tag = new Tag(array_merge([
            'name'  => 'Urgente',
            'color' => Tag::COLOR_DANGER,
        ], $overrides));
        $tag->save();
        return $tag;
    }

    // 3.1 - validates()

    public function testSaveFailsWithoutName(): void
    {
        $tag = new Tag();

        $this->assertFalse($tag->save());
        $this->assertNotEmpty($tag->errors('name'));
    }

    public function testSaveFailsWithDuplicateName(): void
    {
        $this->makeTag(['name' => 'Urgente']);
        $duplicate = new Tag(['name' => 'Urgente']);

        $this->assertFalse($duplicate->save());
        $this->assertNotEmpty($duplicate->errors('name'));
    }

    public function testSaveFailsWithInvalidColor(): void
    {
        $tag = new Tag(['name' => 'Especial', 'color' => 'roxo']);

        $this->assertFalse($tag->save());
        $this->assertNotEmpty($tag->errors('color'));
    }

    public function testSavesValidTagWithDefaultColor(): void
    {
        $tag = new Tag(['name' => 'Comum']);

        $this->assertTrue($tag->save());
        $this->assertSame(Tag::COLOR_SECONDARY, $tag->color);
    }

    public function testBadgeClassReflectsColor(): void
    {
        $tag = $this->makeTag(['color' => Tag::COLOR_INFO]);

        $this->assertSame('text-bg-info', $tag->badgeClass());
    }

    public function testOrderedByNameSortsCaseInsensitively(): void
    {
        $this->makeTag(['name' => 'banana']);
        $this->makeTag(['name' => 'Abacaxi']);
        $this->makeTag(['name' => 'cereja']);

        $names = array_map(fn(Tag $tag) => $tag->name, Tag::orderedByName());

        $this->assertSame(['Abacaxi', 'banana', 'cereja'], $names);
    }

    // 2.1 - BelongsToMany (relacao N:N)

    public function testAttachCreatesRelation(): void
    {
        $order = $this->makeOrder($this->makeClient());
        $tag = $this->makeTag();

        $order->tags()->attach((int) $tag->id);

        $this->assertSame(1, $order->tags()->count());
        $this->assertSame((int) $tag->id, (int) $order->tags()->get()[0]->id);
    }

    public function testExistsReflectsRelation(): void
    {
        $order = $this->makeOrder($this->makeClient());
        $tag = $this->makeTag();

        $this->assertFalse($order->tags()->exists((int) $tag->id));

        $order->tags()->attach((int) $tag->id);

        $this->assertTrue($order->tags()->exists((int) $tag->id));
    }

    public function testDetachRemovesRelation(): void
    {
        $order = $this->makeOrder($this->makeClient());
        $tag = $this->makeTag();
        $order->tags()->attach((int) $tag->id);

        $order->tags()->detach((int) $tag->id);

        $this->assertSame(0, $order->tags()->count());
        $this->assertEmpty($order->tags()->get());
    }

    public function testUniqueIndexPreventsDuplicateRelation(): void
    {
        $order = $this->makeOrder($this->makeClient());
        $tag = $this->makeTag();
        $order->tags()->attach((int) $tag->id);

        $this->expectException(PDOException::class);

        $order->tags()->attach((int) $tag->id);
    }

    public function testTagOrdersReturnsRelatedOrders(): void
    {
        $client = $this->makeClient();
        $order = $this->makeOrder($client);
        $tag = $this->makeTag();
        $order->tags()->attach((int) $tag->id);

        $this->assertSame(1, $tag->orders()->count());
        $this->assertSame((int) $order->id, (int) $tag->orders()->get()[0]->id);
    }

    public function testDestroyingTagCascadesRelations(): void
    {
        $order = $this->makeOrder($this->makeClient());
        $tag = $this->makeTag();
        $order->tags()->attach((int) $tag->id);

        $tag->destroy();

        $this->assertSame(0, $order->tags()->count());
    }
}
