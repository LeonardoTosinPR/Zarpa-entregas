<?php

namespace Tests\Unit\Services;

use App\Models\Order;
use App\Models\OrderDeliveryPhoto;
use App\Models\User;
use App\Services\DeliveryProofUploader;
use Core\Constants\Constants;
use Tests\TestCase;

class DeliveryProofUploaderTest extends TestCase
{
    protected function tearDown(): void
    {
        $this->cleanupUploadDirectory();
        parent::tearDown();
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

    public function testStoresUploadedImageAndCreatesRecord(): void
    {
        $order = $this->makeOrder();
        $tmp = $this->makeTempPng();
        $uploader = new DeliveryProofUploader();

        $result = $uploader->storeForOrder($order, $this->fileBag($tmp, 'entrega.png'));

        $this->assertEmpty($result['errors']);
        $this->assertCount(1, $result['photos']);
        $photo = $result['photos'][0];

        $this->assertSame($order->id, (int) $photo->order_id);
        $this->assertSame(OrderDeliveryPhoto::MIME_PNG, $photo->mime_type);
        $this->assertFileExists($photo->absolutePath());
        $this->assertCount(1, $order->deliveryPhotos()->get());
    }

    public function testRejectsInvalidImagePayload(): void
    {
        $order = $this->makeOrder();
        $tmp = tempnam(sys_get_temp_dir(), 'proof_');
        file_put_contents($tmp, '<?php echo "malicioso";');
        $uploader = new DeliveryProofUploader();

        $result = $uploader->storeForOrder($order, $this->fileBag($tmp, 'entrega.png'));

        $this->assertNotEmpty($result['errors']);
        $this->assertCount(0, $order->deliveryPhotos()->get());

        if (is_file($tmp)) {
            unlink($tmp);
        }
    }

    public function testRejectsFilesLargerThanTwoMegabytes(): void
    {
        $order = $this->makeOrder();
        $tmp = $this->makeTempPng();
        $uploader = new DeliveryProofUploader();

        $result = $uploader->storeForOrder(
            $order,
            $this->fileBag($tmp, 'entrega.png', OrderDeliveryPhoto::MAX_SIZE_BYTES + 1)
        );

        $this->assertNotEmpty($result['errors']);
        $this->assertCount(0, $order->deliveryPhotos()->get());

        if (is_file($tmp)) {
            unlink($tmp);
        }
    }

    public function testRemovesImageFromFilesystemAndDatabase(): void
    {
        $order = $this->makeOrder();
        $tmp = $this->makeTempPng();
        $uploader = new DeliveryProofUploader();
        $result = $uploader->storeForOrder($order, $this->fileBag($tmp, 'entrega.png'));
        $photo = $result['photos'][0];
        $path = $photo->absolutePath();

        $this->assertTrue($uploader->remove($photo));

        $this->assertFileDoesNotExist($path);
        $this->assertNull(OrderDeliveryPhoto::findById($photo->id));
    }

    /**
     * @return array{name: array<int, string>, type: array<int, string>, tmp_name: array<int, string>, error: array<int, int>, size: array<int, int>}
     */
    private function fileBag(string $path, string $name, ?int $size = null): array
    {
        return [
            'name' => [$name],
            'type' => [OrderDeliveryPhoto::MIME_PNG],
            'tmp_name' => [$path],
            'error' => [UPLOAD_ERR_OK],
            'size' => [$size ?? filesize($path)],
        ];
    }

    private function makeTempPng(): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'proof_');
        $png = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAIAAACQd1PeAAAADUlEQVR42mP8z8BQDwAFgwJ/lOKZVwAAAABJRU5ErkJggg==';
        file_put_contents($tmp, base64_decode($png));

        return $tmp;
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
