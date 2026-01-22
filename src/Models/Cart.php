<?php

namespace Juzaweb\Modules\Payment\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Juzaweb\Modules\Core\Models\Model;
use Juzaweb\Modules\Core\Traits\HasAPI;
use Juzaweb\Modules\Core\Traits\HasCreator;
use Juzaweb\Modules\Core\Traits\UsedInFrontend;

class Cart extends Model
{
    use HasAPI, HasUuids,  HasCreator, UsedInFrontend;

    protected $table = 'carts';

    protected $fillable = [
        'created_by',
        'created_type',
    ];

    public function items()
    {
        return $this->hasMany(CartItem::class, 'cart_id', 'id');
    }

    public function scopeWhereInFrontend(Builder $builder, bool $cache = true): Builder
    {
        return $builder->with(
            [
                'items' => function ($q) {
                    $q->cacheFor(3600)->with(['orderable' => function ($q2) {
                        $q2->with(['media'])->cacheFor(3600)->withTranslation();
                    }]);
                }
            ]
        );
    }

    public function getTotalAmount()
    {
        return $this->items->sum(fn($item) => $item->orderable->price * $item->quantity);
    }
}
