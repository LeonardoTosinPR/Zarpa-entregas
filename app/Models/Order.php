<?php

namespace App\Models;

use Core\Database\ActiveRecord\HasMany;
use Core\Database\ActiveRecord\Model;
use Core\Database\Database;
use Lib\Validations;
use PDO;

class Order extends Model
{
    protected static string $table = 'orders';
    protected static array $columns = [
        'client_id',
        'courier_id',
        'pickup_address',
        'delivery_address',
        'package_size',
        'is_fragile',
        'distance_km',
        'status',
        'payment_method',
        'shipping_fee',
        'confirmation_code',
        'created_at'
    ];

    public const STATUS_PENDING = 'pendente';
    public const STATUS_ACCEPTED = 'aceito';
    public const STATUS_CANCELED = 'cancelado';
    public const STATUS_ON_ROUTE = 'em rota';
    public const STATUS_DELIVERED = 'entregue';

    public const PAYMENT_CASH = 'dinheiro';
    public const PAYMENT_PIX = 'pix';
    public const PAYMENT_CARD = 'cartao';

    public const PACKAGE_SMALL = 'pequeno';
    public const PACKAGE_MEDIUM = 'medio';
    public const PACKAGE_LARGE = 'grande';

    private const FRAGILE_FEE = 5.00;
    private const DISTANCE_FEE_PER_KM = 0.10;

    public function __construct($params = [])
    {
        parent::__construct($params);

        if ($this->status === null) {
            $this->status = self::STATUS_PENDING;
        }

        if ($this->payment_method === null) {
            $this->payment_method = self::PAYMENT_PIX;
        }

        if ($this->package_size === null) {
            $this->package_size = self::PACKAGE_SMALL;
        }

        if ($this->is_fragile === null) {
            $this->is_fragile = 0;
        }

        if ($this->distance_km === null || $this->distance_km === '') {
            $this->distance_km = '0.00';
        }

        if ($this->shipping_fee === null || $this->shipping_fee === '') {
            $this->shipping_fee = $this->calculateShippingFee();
        }

        if ($this->created_at === null) {
            $this->created_at = date('Y-m-d H:i:s');
        }
    }

    public function validates(): void
    {
        Validations::notEmpty('client_id', $this);
        Validations::notEmpty('pickup_address', $this);
        Validations::notEmpty('delivery_address', $this);
        Validations::notEmpty('status', $this);
        Validations::inclusion('status', $this, self::statuses());
        Validations::notEmpty('payment_method', $this);
        Validations::inclusion('payment_method', $this, self::paymentMethods());
        Validations::notEmpty('package_size', $this);
        Validations::inclusion('package_size', $this, array_keys(self::packageBasePrices()));

        if (!is_numeric($this->distance_km) || (float) $this->distance_km <= 0) {
            $this->addError('distance_km', 'deve ser um valor maior que zero');
        }

        $this->shipping_fee = $this->calculateShippingFee();

        if (!is_numeric($this->shipping_fee) || (float) $this->shipping_fee < 0) {
            $this->addError('shipping_fee', 'deve ser um valor maior ou igual a zero');
        }

        if ($this->courier_id === null && in_array($this->status, [self::STATUS_ACCEPTED, self::STATUS_ON_ROUTE, self::STATUS_DELIVERED], true)) {
            $this->addError('status', 'precisa ser aceito por um entregador');
        }
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_ACCEPTED,
            self::STATUS_CANCELED,
            self::STATUS_ON_ROUTE,
            self::STATUS_DELIVERED
        ];
    }

    public static function paymentMethods(): array
    {
        return [
            self::PAYMENT_CASH,
            self::PAYMENT_PIX,
            self::PAYMENT_CARD
        ];
    }

    public static function packageBasePrices(): array
    {
        return [
            self::PACKAGE_SMALL => 10.00,
            self::PACKAGE_MEDIUM => 15.00,
            self::PACKAGE_LARGE => 20.00
        ];
    }

    public static function packageSizeLabel(string $packageSize): string
    {
        return [
            self::PACKAGE_SMALL => 'Pequeno',
            self::PACKAGE_MEDIUM => 'Medio',
            self::PACKAGE_LARGE => 'Grande'
        ][$packageSize] ?? ucfirst($packageSize);
    }

    public static function statusLabel(string $status): string
    {
        return [
            self::STATUS_PENDING => 'Pendente',
            self::STATUS_ACCEPTED => 'Aceito',
            self::STATUS_CANCELED => 'Cancelado',
            self::STATUS_ON_ROUTE => 'Em rota',
            self::STATUS_DELIVERED => 'Entregue'
        ][$status] ?? ucfirst($status);
    }

    public static function paymentMethodLabel(string $paymentMethod): string
    {
        return [
            self::PAYMENT_CASH => 'Dinheiro',
            self::PAYMENT_PIX => 'Pix',
            self::PAYMENT_CARD => 'Cartao'
        ][$paymentMethod] ?? ucfirst($paymentMethod);
    }

    public function statusBadgeClass(): string
    {
        return [
            self::STATUS_PENDING => 'text-bg-warning',
            self::STATUS_ACCEPTED => 'text-bg-info',
            self::STATUS_CANCELED => 'text-bg-secondary',
            self::STATUS_ON_ROUTE => 'text-bg-primary',
            self::STATUS_DELIVERED => 'text-bg-success'
        ][$this->status] ?? 'text-bg-secondary';
    }

    public function client(): ?User
    {
        return $this->client_id ? User::findById((int) $this->client_id) : null;
    }

    public function courier(): ?User
    {
        return $this->courier_id ? User::findById((int) $this->courier_id) : null;
    }

    public function deliveryPhotos(): HasMany
    {
        return $this->hasMany(OrderDeliveryPhoto::class, 'order_id');
    }

    public function statusLabelText(): string
    {
        return self::statusLabel($this->status);
    }

    public function paymentMethodLabelText(): string
    {
        return self::paymentMethodLabel($this->payment_method);
    }

    public function formattedShippingFee(): string
    {
        return 'R$ ' . number_format((float) $this->shipping_fee, 2, ',', '.');
    }

    public function formattedDistance(): string
    {
        return number_format((float) $this->distance_km, 2, ',', '.') . ' km';
    }

    public function packageSizeLabelText(): string
    {
        return self::packageSizeLabel($this->package_size);
    }

    public function isFragile(): bool
    {
        return (bool) $this->is_fragile;
    }

    public function calculateShippingFee(): string
    {
        $basePrice = self::packageBasePrices()[$this->package_size] ?? self::packageBasePrices()[self::PACKAGE_SMALL];
        $fragileFee = $this->isFragile() ? self::FRAGILE_FEE : 0.00;
        $distanceFee = max(0, (float) $this->distance_km) * self::DISTANCE_FEE_PER_KM;

        return number_format($basePrice + $fragileFee + $distanceFee, 2, '.', '');
    }

    public function mapsUrl(): string
    {
        return 'https://www.google.com/maps/dir/?api=1&origin='
            . rawurlencode($this->pickup_address)
            . '&destination='
            . rawurlencode($this->delivery_address)
            . '&travelmode=driving';
    }

    public function canBeViewedBy(User $user): bool
    {
        return $user->isAdmin()
            || ((int) $this->client_id === (int) $user->id)
            || ($user->isDeliverer() && ($this->status === self::STATUS_PENDING || (int) $this->courier_id === (int) $user->id));
    }

    public function canBeEditedBy(User $user): bool
    {
        return $user->isAdmin()
            || ((int) $this->client_id === (int) $user->id && $this->status === self::STATUS_PENDING)
            || ($user->isDeliverer() && (int) $this->courier_id === (int) $user->id && $this->status !== self::STATUS_DELIVERED);
    }

    public function canBeAcceptedBy(User $user): bool
    {
        return $user->isDeliverer() && $this->status === self::STATUS_PENDING && $this->courier_id === null;
    }

    public function canBeDeletedBy(User $user): bool
    {
        return $user->isAdmin()
            || ((int) $this->client_id === (int) $user->id);
    }

    public function canBeRefusedBy(User $user): bool
    {
        return $user->isDeliverer()
            && (int) $this->courier_id === (int) $user->id
            && in_array($this->status, [self::STATUS_ACCEPTED, self::STATUS_ON_ROUTE], true);
    }

    public function canReceiveDeliveryPhotosFrom(User $user): bool
    {
        return $user->isDeliverer()
            && (int) $this->courier_id === (int) $user->id
            && in_array($this->status, [self::STATUS_ACCEPTED, self::STATUS_ON_ROUTE, self::STATUS_DELIVERED], true);
    }

    public function canManageDeliveryPhotosBy(User $user): bool
    {
        return $user->isAdmin() || $this->canReceiveDeliveryPhotosFrom($user);
    }

    /**
     * @return array<Order>
     */
    public static function visibleFor(User $user): array
    {
        if ($user->isAdmin()) {
            return self::orderedByCreatedAt();
        }

        if ($user->isDeliverer()) {
            return self::fromSql(
                'SELECT id, client_id, courier_id, pickup_address, delivery_address, package_size, is_fragile, distance_km, status, payment_method, shipping_fee, confirmation_code, created_at FROM orders WHERE status = :status OR courier_id = :courier_id ORDER BY created_at DESC',
                ['status' => self::STATUS_PENDING, 'courier_id' => $user->id]
            );
        }

        return self::fromSql(
            'SELECT id, client_id, courier_id, pickup_address, delivery_address, package_size, is_fragile, distance_km, status, payment_method, shipping_fee, confirmation_code, created_at FROM orders WHERE client_id = :client_id ORDER BY created_at DESC',
            ['client_id' => $user->id]
        );
    }

    /**
     * @return array<Order>
     */
    public static function orderedByCreatedAt(): array
    {
        return self::fromSql(
            'SELECT id, client_id, courier_id, pickup_address, delivery_address, package_size, is_fragile, distance_km, status, payment_method, shipping_fee, confirmation_code, created_at FROM orders ORDER BY created_at DESC'
        );
    }

    /**
     * @param array<string, mixed> $params
     * @return array<Order>
     */
    private static function fromSql(string $sql, array $params = []): array
    {
        $pdo = Database::getDatabaseConn();
        $stmt = $pdo->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();

        return array_map(fn($row) => new self($row), $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function __set(string $property, mixed $value): void
    {
        if (in_array($property, ['client_id', 'courier_id'], true) && $value === '') {
            $value = null;
        }

        if (in_array($property, ['shipping_fee', 'distance_km'], true) && is_string($value)) {
            $value = str_replace(',', '.', $value);
        }

        if ($property === 'is_fragile') {
            $value = $value ? 1 : 0;
        }

        parent::__set($property, $value);
    }
}
