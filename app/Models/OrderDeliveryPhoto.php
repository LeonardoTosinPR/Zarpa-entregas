<?php

namespace App\Models;

use Core\Constants\Constants;
use Core\Database\ActiveRecord\BelongsTo;
use Core\Database\ActiveRecord\Model;
use Lib\Validations;

class OrderDeliveryPhoto extends Model
{
    protected static string $table = 'order_delivery_photos';
    protected static array $columns = [
        'order_id',
        'file_name',
        'original_name',
        'mime_type',
        'size_bytes',
        'created_at'
    ];

    public const MAX_SIZE_BYTES = 2097152;
    public const MIME_JPEG = 'image/jpeg';
    public const MIME_PNG = 'image/png';

    public function __construct($params = [])
    {
        parent::__construct($params);

        if ($this->created_at === null) {
            $this->created_at = date('Y-m-d H:i:s');
        }
    }

    public function validates(): void
    {
        Validations::notEmpty('order_id', $this);
        Validations::notEmpty('file_name', $this);
        Validations::notEmpty('original_name', $this);
        Validations::notEmpty('mime_type', $this);
        Validations::notEmpty('size_bytes', $this);
        Validations::inclusion('mime_type', $this, self::allowedMimeTypes());

        if (!is_numeric($this->size_bytes) || (int) $this->size_bytes <= 0) {
            $this->addError('size_bytes', 'deve ser maior que zero');
        } elseif ((int) $this->size_bytes > self::MAX_SIZE_BYTES) {
            $this->addError('size_bytes', 'deve ter ate 2MB');
        }
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * @return array<string>
     */
    public static function allowedMimeTypes(): array
    {
        return [self::MIME_JPEG, self::MIME_PNG];
    }

    public function publicPath(): string
    {
        return '/assets/uploads/delivery-proofs/' . $this->file_name;
    }

    public function absolutePath(): string
    {
        return (string) Constants::rootPath()->join('public' . $this->publicPath());
    }

    public function formattedSize(): string
    {
        return number_format(((int) $this->size_bytes) / 1024, 1, ',', '.') . ' KB';
    }
}
