<?php

namespace App\Controllers;

use App\Models\Notification;
use App\Models\Order;
use App\Models\User;
use App\Services\DeliveryProofUploader;
use Core\Http\Controllers\Controller;
use Core\Http\Request;
use Lib\FlashMessage;

class OrdersController extends Controller
{
    public function index(): void
    {
        $title = 'Pedidos';
        $user = $this->currentUser();
        $orders = Order::visibleFor($user);

        $this->render('orders/index', compact('title', 'orders'));
    }

    public function show(Request $request): void
    {
        $order = $this->findVisibleOrder($request);
        $title = 'Pedido #' . $order->id;

        $this->render('orders/show', compact('title', 'order'));
    }

    public function new(): void
    {
        $user = $this->currentUser();

        if ($user->isDeliverer() && !$user->isAdmin()) {
            FlashMessage::danger('Entregadores nao podem criar pedidos.');
            $this->redirectTo(route('orders.index'));
        }

        $order = new Order(['client_id' => $user->id]);
        $clients = $this->clientsForForm();
        $title = 'Novo pedido';

        $this->render('orders/form', compact('title', 'order', 'clients'));
    }

    public function create(Request $request): void
    {
        $user = $this->currentUser();

        if ($user->isDeliverer() && !$user->isAdmin()) {
            FlashMessage::danger('Entregadores nao podem criar pedidos.');
            $this->redirectTo(route('orders.index'));
        }

        $order = new Order($this->orderParams($request, null));

        if ($order->save()) {
            FlashMessage::success('Pedido criado com sucesso.');
            $this->redirectTo(route('orders.show', ['id' => $order->id]));
        }

        FlashMessage::danger('Verifique os dados do pedido.');
        $clients = $this->clientsForForm();
        $title = 'Novo pedido';
        $this->render('orders/form', compact('title', 'order', 'clients'));
    }

    public function edit(Request $request): void
    {
        $order = $this->findVisibleOrder($request);
        $user = $this->currentUser();

        if (!$order->canBeEditedBy($user)) {
            FlashMessage::danger('Voce nao pode alterar este pedido.');
            $this->redirectTo(route('orders.show', ['id' => $order->id]));
        }

        $clients = $this->clientsForForm();
        $title = 'Editar pedido #' . $order->id;

        $this->render('orders/form', compact('title', 'order', 'clients'));
    }

    public function update(Request $request): void
    {
        $order = $this->findVisibleOrder($request);
        $user = $this->currentUser();
        $previousStatus = $order->status;

        if (!$order->canBeEditedBy($user)) {
            FlashMessage::danger('Voce nao pode alterar este pedido.');
            $this->redirectTo(route('orders.show', ['id' => $order->id]));
        }

        $deliveryPhotoResult = $this->storeDeliveryPhotosFromRequest($request, $order, false);
        if (!empty($deliveryPhotoResult['errors'])) {
            FlashMessage::danger(implode(' ', $deliveryPhotoResult['errors']));
            $clients = $this->clientsForForm();
            $title = 'Editar pedido #' . $order->id;
            $this->render('orders/form', compact('title', 'order', 'clients'));
            return;
        }

        foreach ($this->orderParams($request, $order) as $property => $value) {
            $order->$property = $value;
        }

        if (
            $user->isDeliverer()
            && $order->status === Order::STATUS_DELIVERED
            && empty($order->deliveryPhotos()->get())
        ) {
            FlashMessage::danger('Anexe pelo menos uma foto de comprovacao antes de finalizar a entrega.');
            $clients = $this->clientsForForm();
            $title = 'Editar pedido #' . $order->id;
            $this->render('orders/form', compact('title', 'order', 'clients'));
            return;
        }

        if ($order->save()) {
            if ($previousStatus !== Order::STATUS_DELIVERED && $order->status === Order::STATUS_DELIVERED) {
                $this->notifyDeliveredOrder($order);
                FlashMessage::success('Pedido entregue. O comprovante ficou salvo na galeria.');
            } else {
                FlashMessage::success('Pedido atualizado com sucesso.');
            }

            $this->redirectTo(route('orders.show', ['id' => $order->id]));
        }

        FlashMessage::danger('Verifique os dados do pedido.');
        $clients = $this->clientsForForm();
        $title = 'Editar pedido #' . $order->id;
        $this->render('orders/form', compact('title', 'order', 'clients'));
    }

    public function destroy(Request $request): void
    {
        $order = $this->findVisibleOrder($request);
        $user = $this->currentUser();

        if (!$order->canBeDeletedBy($user)) {
            FlashMessage::danger('Voce nao pode excluir este pedido.');
            $this->redirectTo(route('orders.show', ['id' => $order->id]));
        }

        (new DeliveryProofUploader())->removeAllForOrder($order);

        if ($order->destroy()) {
            FlashMessage::success($user->isAdmin() ? 'Pedido excluido com sucesso.' : 'Pedido cancelado com sucesso.');
        } else {
            FlashMessage::danger('Nao foi possivel cancelar o pedido.');
        }

        $this->redirectTo(route('orders.index'));
    }

    public function accept(Request $request): void
    {
        $order = $this->findVisibleOrder($request);
        $user = $this->currentUser();

        if (!$order->canBeAcceptedBy($user)) {
            FlashMessage::danger('Este pedido nao esta disponivel para aceite.');
            $this->redirectTo(route('orders.show', ['id' => $order->id]));
        }

        $order->courier_id = $user->id;
        $order->status = Order::STATUS_ACCEPTED;

        if ($order->save()) {
            FlashMessage::success('Pedido aceito com sucesso.');
        } else {
            FlashMessage::danger('Nao foi possivel aceitar o pedido.');
        }

        $this->redirectTo(route('orders.show', ['id' => $order->id]));
    }

    public function uploadDeliveryPhotos(Request $request): void
    {
        $order = $this->findVisibleOrder($request);
        $user = $this->currentUser();

        if (!$order->canReceiveDeliveryPhotosFrom($user)) {
            FlashMessage::danger('Voce nao pode anexar comprovantes neste pedido.');
            $this->redirectTo(route('orders.show', ['id' => $order->id]));
        }

        $result = $this->storeDeliveryPhotosFromRequest($request, $order, true);

        if (!empty($result['errors'])) {
            FlashMessage::danger(implode(' ', $result['errors']));
        } else {
            $count = count($result['photos']);
            FlashMessage::success($count === 1 ? 'Foto anexada com sucesso.' : "{$count} fotos anexadas com sucesso.");
        }

        $this->redirectTo(route('orders.show', ['id' => $order->id]));
    }

    public function destroyDeliveryPhoto(Request $request): void
    {
        $order = $this->findVisibleOrder($request);
        $user = $this->currentUser();

        if (!$order->canManageDeliveryPhotosBy($user)) {
            FlashMessage::danger('Voce nao pode remover este comprovante.');
            $this->redirectTo(route('orders.show', ['id' => $order->id]));
        }

        $photo = $order->deliveryPhotos()->findById((int) $request->getParam('photo_id'));

        if ($photo === null) {
            FlashMessage::danger('Comprovante nao encontrado.');
            $this->redirectTo(route('orders.show', ['id' => $order->id]));
        }

        if ((new DeliveryProofUploader())->remove($photo)) {
            FlashMessage::success('Comprovante removido do pedido e do filesystem.');
        } else {
            FlashMessage::danger('Nao foi possivel remover o comprovante.');
        }

        $this->redirectTo(route('orders.show', ['id' => $order->id]));
    }

    public function refuse(Request $request): void
    {
        $order = $this->findVisibleOrder($request);
        $user = $this->currentUser();

        if (!$order->canBeRefusedBy($user)) {
            FlashMessage::danger('Voce nao pode recusar este pedido.');
            $this->redirectTo(route('orders.show', ['id' => $order->id]));
        }

        $order->courier_id = null;
        $order->status = Order::STATUS_PENDING;

        if ($order->save()) {
            FlashMessage::success('Pedido recusado. Ele voltou para a fila de pendentes.');
        } else {
            FlashMessage::danger('Nao foi possivel recusar o pedido.');
        }

        $this->redirectTo(route('orders.index'));
    }

    private function findVisibleOrder(Request $request): Order
    {
        $order = Order::findById((int) $request->getParam('id'));
        $user = $this->currentUser();

        if ($order === null || !$order->canBeViewedBy($user)) {
            FlashMessage::danger('Pedido nao encontrado.');
            $this->redirectTo(route('orders.index'));
        }

        return $order;
    }

    /**
     * @return array<string, mixed>
     */
    private function orderParams(Request $request, ?Order $order): array
    {
        $user = $this->currentUser();
        $params = $request->getParam('order', []);

        if ($user->isAdmin()) {
            return [
                'client_id' => $params['client_id'] ?? $order?->client_id,
                'courier_id' => $order?->courier_id,
                'pickup_address' => trim($params['pickup_address'] ?? $order?->pickup_address ?? ''),
                'delivery_address' => trim($params['delivery_address'] ?? $order?->delivery_address ?? ''),
                'package_size' => $params['package_size'] ?? $order?->package_size ?? Order::PACKAGE_SMALL,
                'is_fragile' => isset($params['is_fragile']) ? 1 : 0,
                'distance_km' => $params['distance_km'] ?? $order?->distance_km ?? '0.00',
                'status' => $order === null ? Order::STATUS_PENDING : ($params['status'] ?? $order->status),
                'payment_method' => $params['payment_method'] ?? $order?->payment_method ?? Order::PAYMENT_PIX,
                'shipping_fee' => $order?->shipping_fee,
                'confirmation_code' => trim($params['confirmation_code'] ?? $order?->confirmation_code ?? $this->confirmationCode())
            ];
        }

        if ($user->isDeliverer()) {
            $allowedStatuses = [Order::STATUS_ACCEPTED, Order::STATUS_ON_ROUTE, Order::STATUS_DELIVERED];
            $status = $params['status'] ?? $order?->status ?? Order::STATUS_ACCEPTED;

            return [
                'client_id' => $order?->client_id,
                'courier_id' => $user->id,
                'pickup_address' => $order?->pickup_address,
                'delivery_address' => $order?->delivery_address,
                'package_size' => $order?->package_size,
                'is_fragile' => $order?->is_fragile,
                'distance_km' => $order?->distance_km,
                'status' => in_array($status, $allowedStatuses, true) ? $status : $order?->status,
                'payment_method' => $order?->payment_method,
                'shipping_fee' => $order?->shipping_fee,
                'confirmation_code' => $order?->confirmation_code
            ];
        }

        return [
            'client_id' => $user->id,
            'courier_id' => $order?->courier_id,
            'pickup_address' => trim($params['pickup_address'] ?? $order?->pickup_address ?? ''),
            'delivery_address' => trim($params['delivery_address'] ?? $order?->delivery_address ?? ''),
            'package_size' => $params['package_size'] ?? $order?->package_size ?? Order::PACKAGE_SMALL,
            'is_fragile' => isset($params['is_fragile']) ? 1 : 0,
            'distance_km' => $params['distance_km'] ?? $order?->distance_km ?? '0.00',
            'status' => $order?->status ?? Order::STATUS_PENDING,
            'payment_method' => $params['payment_method'] ?? $order?->payment_method ?? Order::PAYMENT_PIX,
            'shipping_fee' => $order?->shipping_fee,
            'confirmation_code' => $order?->confirmation_code ?? $this->confirmationCode()
        ];
    }

    /**
     * @return array<User>
     */
    private function clientsForForm(): array
    {
        return array_filter(User::all(), fn($user) => $user->isClient() || $user->isAdmin());
    }

    private function confirmationCode(): string
    {
        return strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
    }

    /**
     * @return array{photos: array<\App\Models\OrderDeliveryPhoto>, errors: array<string>}
     */
    private function storeDeliveryPhotosFromRequest(Request $request, Order $order, bool $requireFiles): array
    {
        $uploader = new DeliveryProofUploader();
        $fileBag = $request->getFile('delivery_photos', []);
        $files = $uploader->normalizeFiles($fileBag);

        if (!$requireFiles && empty($files)) {
            return ['photos' => [], 'errors' => []];
        }

        if (!empty($files) && !$order->canReceiveDeliveryPhotosFrom($this->currentUser())) {
            return ['photos' => [], 'errors' => ['Voce nao pode anexar comprovantes neste pedido.']];
        }

        return $uploader->storeForOrder($order, $fileBag);
    }

    private function notifyDeliveredOrder(Order $order): void
    {
        Notification::createFor(
            (int) $order->client_id,
            'Seu pedido #' . $order->id . ' foi entregue. Obrigado por usar o Zarpa.'
        );
    }
}
