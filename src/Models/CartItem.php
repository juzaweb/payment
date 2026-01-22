<?php

/**
 * JUZAWEB CMS - Laravel CMS for Your Project
 *
 * @package    juzaweb/cms
 * @author     The Anh Dang
 * @link       https://cms.juzaweb.com
 * @license    GNU V2
 */

namespace Juzaweb\Modules\Payment\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Juzaweb\Modules\Core\Models\Model;

class CartItem extends Model
{
    use HasUuids;

    protected $table = 'cart_items';

    protected $fillable = [
        'cart_id',
        'orderable_id',
        'orderable_type',
        'quantity',
    ];

    protected $casts = [
        'quantity' => 'integer',
    ];

    protected $appends = [
        'line_price',
    ];

    public function cart()
    {
        return $this->belongsTo(Cart::class, 'cart_id', 'id');
    }

    public function orderable()
    {
        return $this->morphTo();
    }

    public function getLinePriceAttribute(): float
    {
        if ($this->orderable && isset($this->orderable->price)) {
            return (float) $this->orderable->price * $this->quantity;
        }

        return 0;
    }
}
