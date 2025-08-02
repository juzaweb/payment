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

class PurchaseResult extends PaymentResult
{
    /**
     * @var string|null
     */
    protected ?string $redirectUrl = null;

    /**
     * @var string|null
     */
    protected ?string $transactionId = null;

    /**
     * @var array
     */
    protected array $data = [];

    protected bool $isSuccessful = false;

    public static function make(string $transactionId, string $redirectUrl = null, array $data = []): static
    {
        return new self($transactionId, $redirectUrl, $data);
    }

    public function __construct(string $transactionId, string $redirectUrl = null, array $data = [])
    {
        $this->transactionId = $transactionId;
        $this->redirectUrl = $redirectUrl;
        $this->data = $data;
    }

    public function setSuccessful(bool $isSuccessful): self
    {
        $this->isSuccessful = $isSuccessful;

        return $this;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function setData(array $data): self
    {
        $this->data = $data;

        return $this;
    }

    public function isSuccessful(): bool
    {
        return $this->isSuccessful;
    }

    public function isRedirect(): bool
    {
        return $this->redirectUrl !== null;
    }

    public function setRedirectUrl(string $url): self
    {
        $this->redirectUrl = $url;

        return $this;
    }

    public function getRedirectUrl(): ?string
    {
        return $this->redirectUrl;
    }

    public function setTransactionId(string $transactionId): self
    {
        $this->transactionId = $transactionId;

        return $this;
    }

    public function getTransactionId(): ?string
    {
        return $this->transactionId;
    }
}
