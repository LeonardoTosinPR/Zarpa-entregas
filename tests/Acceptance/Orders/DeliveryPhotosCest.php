<?php

namespace Tests\Acceptance\Orders;

use App\Models\Order;
use App\Models\User;
use Core\Constants\Constants;
use Tests\Acceptance\BaseAcceptanceCest;
use Tests\Support\AcceptanceTester;

class DeliveryPhotosCest extends BaseAcceptanceCest
{
    public function _after(AcceptanceTester $page): void
    {
        $this->cleanupUploadDirectory();
        $this->removeProofFixture();
        parent::_after($page);
    }

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

    private function makeDeliverer(): User
    {
        $user = new User([
            'name'                  => 'Entregador Teste',
            'email'                 => 'entregador@example.com',
            'cpf'                   => '11144477735',
            'password'              => '123456',
            'password_confirmation' => '123456',
            'user_type'             => User::USER_TYPE_DELIVERER,
        ]);
        $user->save();
        return $user;
    }

    private function makeAcceptedOrder(User $client, User $deliverer): Order
    {
        $order = new Order([
            'client_id'         => $client->id,
            'courier_id'       => $deliverer->id,
            'pickup_address'    => 'Rua de Coleta, 123',
            'delivery_address'  => 'Rua de Entrega, 456',
            'package_size'      => Order::PACKAGE_SMALL,
            'payment_method'    => Order::PAYMENT_PIX,
            'distance_km'       => '1.00',
            'status'            => Order::STATUS_ACCEPTED,
            'confirmation_code' => 'ABC123',
        ]);
        $order->save();
        return $order;
    }

    // 1.1, 1.2, 1.3 - Upload, visualizacao e remocao de imagem/arquivo
    public function delivererUploadsViewsAndRemovesDeliveryPhoto(AcceptanceTester $page): void
    {
        $client = $this->makeClient();
        $deliverer = $this->makeDeliverer();
        $order = $this->makeAcceptedOrder($client, $deliverer);
        $this->ensureProofFixture();

        $page->login($deliverer->email, '123456');
        $page->amOnPage('/orders/' . $order->id);
        $page->attachFile('#delivery-photos', 'proof.png');
        $page->click('Anexar');

        $page->see('Foto anexada com sucesso.');
        $page->see('proof.png');

        $page->click('.delivery-proof-card .btn-outline-danger');
        $page->acceptPopup();

        $page->see('Comprovante removido do pedido e do filesystem.');
        $page->see('Nenhum comprovante anexado.');
    }

    private function ensureProofFixture(): void
    {
        $png = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAIAAACQd1PeAAAADUlEQVR42mP8z8BQDwAFgwJ/lOKZVwAAAABJRU5ErkJggg==';
        file_put_contents($this->proofFixturePath(), base64_decode($png));
    }

    private function removeProofFixture(): void
    {
        $path = $this->proofFixturePath();

        if (is_file($path)) {
            unlink($path);
        }
    }

    private function proofFixturePath(): string
    {
        return (string) Constants::rootPath()->join('tests/Support/Data/proof.png');
    }

    private function cleanupUploadDirectory(): void
    {
        $directory = (string) Constants::rootPath()->join('public/assets/uploads/delivery-proofs');

        if (!is_dir($directory)) {
            return;
        }

        foreach (glob($directory . DIRECTORY_SEPARATOR . '*') ?: [] as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }
}
