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

enum OrderDeliveryStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case SHIPPING = 'shipping';
    case COMPLETED = 'completed';
    case CANCELED = 'canceled';

    public static function all(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn($case) => [$case->value => $case->label()])
            ->toArray();
    }

    public function label(): string
    {
        return match ($this) {
            self::PENDING => __('Pending'),
            self::PROCESSING => __('Processing'),
            self::SHIPPING => __('Shipping'),
            self::COMPLETED => __('Completed'),
            self::CANCELED => __('Canceled'),
        };
    }
}
