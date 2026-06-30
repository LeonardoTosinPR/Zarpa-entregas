<?php

namespace App\Models;

use Core\Database\ActiveRecord\BelongsToMany;
use Core\Database\ActiveRecord\Model;
use Lib\Validations;

class Tag extends Model
{
    protected static string $table = 'tags';
    protected static array $columns = ['name', 'color'];

    public const COLOR_SECONDARY = 'secondary';
    public const COLOR_PRIMARY = 'primary';
    public const COLOR_SUCCESS = 'success';
    public const COLOR_DANGER = 'danger';
    public const COLOR_WARNING = 'warning';
    public const COLOR_INFO = 'info';
    public const COLOR_DARK = 'dark';

    public function __construct($params = [])
    {
        parent::__construct($params);

        if ($this->color === null) {
            $this->color = self::COLOR_SECONDARY;
        }
    }

    public function validates(): void
    {
        Validations::notEmpty('name', $this);
        Validations::minLength('name', $this, 2);
        Validations::uniqueness('name', $this);
        Validations::inclusion('color', $this, self::colors());
    }

    /**
     * @return array<string>
     */
    public static function colors(): array
    {
        return [
            self::COLOR_SECONDARY,
            self::COLOR_PRIMARY,
            self::COLOR_SUCCESS,
            self::COLOR_DANGER,
            self::COLOR_WARNING,
            self::COLOR_INFO,
            self::COLOR_DARK
        ];
    }

    public function orders(): BelongsToMany
    {
        return $this->belongsToMany(Order::class, 'order_tags', 'tag_id', 'order_id');
    }

    public function badgeClass(): string
    {
        return 'text-bg-' . $this->color;
    }

    /**
     * @return array<Tag>
     */
    public static function orderedByName(): array
    {
        $tags = self::all();
        usort($tags, fn(Tag $a, Tag $b) => strcasecmp($a->name, $b->name));

        return $tags;
    }
}
