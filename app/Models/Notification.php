<?php

namespace App\Models;

use Core\Database\ActiveRecord\Model;
use Lib\Validations;

class Notification extends Model
{
    protected static string $table = 'notifications';
    protected static array $columns = ['user_id', 'message', 'created_at'];

    public function __construct($params = [])
    {
        parent::__construct($params);

        if ($this->created_at === null) {
            $this->created_at = date('Y-m-d H:i:s');
        }
    }

    public function validates(): void
    {
        Validations::notEmpty('user_id', $this);
        Validations::notEmpty('message', $this);
    }

    public static function createFor(int $userId, string $message): bool
    {
        $notification = new self([
            'user_id' => $userId,
            'message' => $message
        ]);

        return $notification->save();
    }

    /**
     * @return array<Notification>
     */
    public static function consumeFor(User $user): array
    {
        $notifications = self::where(['user_id' => $user->id]);

        foreach ($notifications as $notification) {
            $notification->destroy();
        }

        return $notifications;
    }
}
