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

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Juzaweb\Modules\Core\Models\Model;

class PaymentMethodTranslation extends Model
{
    protected $table = 'payment_method_translations';

    protected $fillable = [
        'name',
        'description',
        'locale',
        'payment_method_id',
    ];

    public function method(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }
}
