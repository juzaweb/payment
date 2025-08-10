<?php
/**
 * JUZAWEB CMS - Laravel CMS for Your Project
 *
 * @package    juzaweb/cms
 * @author     The Anh Dang
 * @link       https://cms.juzaweb.com
 * @license    GNU V2
 */

namespace Juzaweb\Modules\Payment\Services;

class CompleteResult extends PaymentResult
{
    protected bool $isEmbed = false;

    public static function make(string $transactionId, string $isSuccessful, array $data = []): static
    {
        return new self($transactionId, $isSuccessful, $data);
    }

    public function __construct(string $transactionId, string $isSuccessful, array $data = [])
    {
        $this->transactionId = $transactionId;
        $this->isSuccessful = $isSuccessful;
        $this->data = $data;
    }
}
