<?php
/**
 * JUZAWEB CMS - Laravel CMS for Your Project
 *
 * @package    juzaweb/cms
 * @author     The Anh Dang
 * @link       https://cms.juzaweb.com
 * @license    GNU V2
 */

namespace Juzaweb\Modules\Payment\Enums;

enum OrderPaymentStatus: string
{
    case PENDING = 'pending';
    case COMPLETED = 'completed';

    public static function all()
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($case) => [$case->value => $case->label()])
            ->toArray();
    }

    public function label(): string
    {
        return match($this) {
            self::PENDING => __('Pending'),
            self::COMPLETED => __('Paid'),
        };
    }
}
