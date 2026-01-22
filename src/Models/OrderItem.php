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

use Juzaweb\Modules\Core\Models\Model;

class OrderItem extends Model
{
    protected $table = 'order_items';

    protected $fillable = [
        'title',
        'price',
        'line_price',
        'quantity',
        'compare_price',
        'sku_code',
        'barcode',
        'order_id',
        'orderable_id',
        'orderable_type',
    ];

    public function orderable()
    {
        return $this->morphTo(__FUNCTION__, 'orderable_type', 'orderable_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }
}
