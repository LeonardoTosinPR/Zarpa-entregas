<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderDeliveryPhoto;
use Core\Constants\Constants;

class DeliveryProofUploader
{
    private const UPLOAD_DIR = 'public/assets/uploads/delivery-proofs';

    /**
     * @param array<string, mixed> $fileBag
     * @return array{photos: array<OrderDeliveryPhoto>, errors: array<string>}
     */
    public function storeForOrder(Order $order, array $fileBag): array
    {
        $files = $this->normalizeFiles($fileBag);

        if (empty($files)) {
            return ['photos' => [], 'errors' => ['Selecione pelo menos uma foto.']];
        }

        $errors = $this->validateFiles($files);
        if (!empty($errors)) {
            return ['photos' => [], 'errors' => $errors];
        }

        $storedPhotos = [];
        $storedPaths = [];

        foreach ($files as $file) {
            $mimeType = $this->detectMimeType($file['tmp_name']);
            $fileName = $this->buildFileName($mimeType);
            $destination = $this->uploadPath($fileName);

            if (!$this->moveUploadedFile($file['tmp_name'], $destination)) {
                $this->removeStoredPaths($storedPaths);
                foreach ($storedPhotos as $photo) {
                    $photo->destroy();
                }

                return ['photos' => [], 'errors' => ['Nao foi possivel salvar a foto enviada.']];
            }

            $storedPaths[] = $destination;

            $photo = new OrderDeliveryPhoto([
                'order_id' => $order->id,
                'file_name' => $fileName,
                'original_name' => $this->originalName($file['name']),
                'mime_type' => $mimeType,
                'size_bytes' => (int) $file['size'],
            ]);

            if (!$photo->save()) {
                $this->removeStoredPaths($storedPaths);
                foreach ($storedPhotos as $storedPhoto) {
                    $storedPhoto->destroy();
                }

                return ['photos' => [], 'errors' => ['Nao foi possivel registrar a foto enviada.']];
            }

            $storedPhotos[] = $photo;
        }

        return ['photos' => $storedPhotos, 'errors' => []];
    }

    public function remove(OrderDeliveryPhoto $photo): bool
    {
        $path = $photo->absolutePath();

        if (is_file($path)) {
            unlink($path);
        }

        return $photo->destroy();
    }

    public function removeAllForOrder(Order $order): void
    {
        foreach ($order->deliveryPhotos()->get() as $photo) {
            $this->remove($photo);
        }
    }

    /**
     * @param array<string, mixed> $fileBag
     * @return array<int, array{name: string, type: string, tmp_name: string, error: int, size: int}>
     */
    public function normalizeFiles(array $fileBag): array
    {
        if (!isset($fileBag['name'])) {
            return [];
        }

        if (!is_array($fileBag['name'])) {
            if (($fileBag['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                return [];
            }

            return [[
                'name' => (string) ($fileBag['name'] ?? ''),
                'type' => (string) ($fileBag['type'] ?? ''),
                'tmp_name' => (string) ($fileBag['tmp_name'] ?? ''),
                'error' => (int) ($fileBag['error'] ?? UPLOAD_ERR_NO_FILE),
                'size' => (int) ($fileBag['size'] ?? 0),
            ]];
        }

        $files = [];

        foreach ($fileBag['name'] as $index => $name) {
            $error = (int) ($fileBag['error'][$index] ?? UPLOAD_ERR_NO_FILE);
            if ($error === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            $files[] = [
                'name' => (string) $name,
                'type' => (string) ($fileBag['type'][$index] ?? ''),
                'tmp_name' => (string) ($fileBag['tmp_name'][$index] ?? ''),
                'error' => $error,
                'size' => (int) ($fileBag['size'][$index] ?? 0),
            ];
        }

        return $files;
    }

    /**
     * @param array<int, array{name: string, type: string, tmp_name: string, error: int, size: int}> $files
     * @return array<string>
     */
    private function validateFiles(array $files): array
    {
        $errors = [];

        foreach ($files as $file) {
            $name = $this->originalName($file['name']);

            if ($file['error'] !== UPLOAD_ERR_OK) {
                $errors[] = "{$name}: falha no envio do arquivo.";
                continue;
            }

            if ($file['size'] <= 0) {
                $errors[] = "{$name}: arquivo vazio.";
                continue;
            }

            if ($file['size'] > OrderDeliveryPhoto::MAX_SIZE_BYTES) {
                $errors[] = "{$name}: tamanho maximo de 2MB.";
                continue;
            }

            if (!is_file($file['tmp_name'])) {
                $errors[] = "{$name}: arquivo temporario nao encontrado.";
                continue;
            }

            $mimeType = $this->detectMimeType($file['tmp_name']);
            if (!in_array($mimeType, OrderDeliveryPhoto::allowedMimeTypes(), true)) {
                $errors[] = "{$name}: use apenas JPEG ou PNG.";
                continue;
            }

            if (@getimagesize($file['tmp_name']) === false) {
                $errors[] = "{$name}: imagem invalida.";
            }
        }

        return $errors;
    }

    private function detectMimeType(string $path): string
    {
        if (class_exists(\finfo::class)) {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            return (string) $finfo->file($path);
        }

        $imageInfo = @getimagesize($path);
        return is_array($imageInfo) ? (string) ($imageInfo['mime'] ?? '') : '';
    }

    private function buildFileName(string $mimeType): string
    {
        $extension = $mimeType === OrderDeliveryPhoto::MIME_PNG ? 'png' : 'jpg';
        return bin2hex(random_bytes(16)) . '.' . $extension;
    }

    private function uploadPath(string $fileName): string
    {
        $directory = (string) Constants::rootPath()->join(self::UPLOAD_DIR);

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        return $directory . DIRECTORY_SEPARATOR . $fileName;
    }

    private function originalName(string $name): string
    {
        return substr(basename(str_replace('\\', '/', $name)), 0, 255);
    }

    private function moveUploadedFile(string $source, string $destination): bool
    {
        if (is_uploaded_file($source)) {
            return move_uploaded_file($source, $destination);
        }

        if (PHP_SAPI === 'cli') {
            if (!copy($source, $destination)) {
                return false;
            }

            unlink($source);
            return true;
        }

        return false;
    }

    /**
     * @param array<string> $paths
     */
    private function removeStoredPaths(array $paths): void
    {
        foreach ($paths as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }
}
