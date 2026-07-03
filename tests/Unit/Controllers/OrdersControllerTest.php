<?php

namespace Tests\Unit\Controllers;

use App\Controllers\OrdersController;
use App\Models\Order;
use App\Models\Tag;
use App\Models\User;
use Core\Http\Request;
use Core\Router\Router;
use Lib\Authentication\Auth;
use Tests\TestCase;

class OrdersControllerTest extends TestCase
{
    private function makeClient(): User
    {
        $user = new User([
            'name' => 'Cliente Teste',
            'email' => 'cliente' . random_int(10000, 99999) . '@example.com',
            'cpf' => '52998224725',
            'password' => '123456',
            'password_confirmation' => '123456',
            'user_type' => User::USER_TYPE_CLIENT,
        ]);
        $this->assertTrue($user->save());
        return $user;
    }

    private function makeOrder(User $client): Order
    {
        $order = new Order([
            'client_id' => $client->id,
            'pickup_address' => 'Rua A, 1',
            'delivery_address' => 'Rua B, 2',
            'distance_km' => '1.00',
        ]);

        $this->assertTrue($order->save());
        $this->assertNotNull($order->id);

        $savedOrder = Order::findById((int) $order->id);
        $this->assertNotNull($savedOrder);

        return $savedOrder;
    }

    private function makeTag(array $overrides = []): Tag
    {
        $tag = new Tag(array_merge([
            'name' => 'Etiqueta ' . random_int(1000, 9999),
            'color' => Tag::COLOR_PRIMARY,
        ], $overrides));
        $tag->save();
        return $tag;
    }

    protected function setUp(): void
    {
        parent::setUp();
        Router::reset();
        require __DIR__ . '/../../../config/routes.php';
    }

    public function testAttachTagWithSingleTagId(): void
    {
        $_SESSION = [];
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/orders/1/tags';

        $client = $this->makeClient();
        Auth::login($client);

        $order = $this->makeOrder($client);
        $tag = $this->makeTag();

        $_REQUEST = [
            'id' => $order->id,
            'tag_id' => $tag->id,
        ];

        $request = new Request();

        $controller = new class($order, $client) extends OrdersController {
            public ?string $redirectUrl = null;
            private Order $order;
            private User $user;

            public function __construct(Order $order, User $user)
            {
                parent::__construct();
                $this->order = $order;
                $this->user = $user;
            }

            protected function redirectTo(string $location): void
            {
                $this->redirectUrl = $location;
            }

            protected function findVisibleOrder(Request $request): Order
            {
                return $this->order;
            }

            public function currentUser(): ?User
            {
                return $this->user;
            }
        };

        $controller->attachTag($request);

        $this->assertSame(1, $order->tags()->count());
        $this->assertSame((int) $tag->id, (int) $order->tags()->get()[0]->id);
        $this->assertNotNull($controller->redirectUrl);
    }

    public function testAttachTagWithMultipleTagIds(): void
    {
        $_SESSION = [];
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/orders/1/tags';

        $client = $this->makeClient();
        Auth::login($client);

        $order = $this->makeOrder($client);
        $tagA = $this->makeTag(['name' => 'Etiqueta A']);
        $tagB = $this->makeTag(['name' => 'Etiqueta B']);

        $_REQUEST = [
            'id' => $order->id,
            'tag_id' => [(int) $tagA->id, (int) $tagB->id],
        ];

        $request = new Request();

        $controller = new class($order, $client) extends OrdersController {
            public ?string $redirectUrl = null;
            private Order $order;
            private User $user;

            public function __construct(Order $order, User $user)
            {
                parent::__construct();
                $this->order = $order;
                $this->user = $user;
            }

            protected function redirectTo(string $location): void
            {
                $this->redirectUrl = $location;
            }

            protected function findVisibleOrder(Request $request): Order
            {
                return $this->order;
            }

            public function currentUser(): ?User
            {
                return $this->user;
            }
        };

        $controller->attachTag($request);

        $this->assertSame(2, $order->tags()->count());
        $this->assertEqualsCanonicalizing(
            [(int) $tagA->id, (int) $tagB->id],
            array_map(fn (Tag $tag) => (int) $tag->id, $order->tags()->get())
        );
        $this->assertNotNull($controller->redirectUrl);
    }
}
