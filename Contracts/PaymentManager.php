<?php
/**
 * JUZAWEB CMS - Laravel CMS for Your Project
 *
 * @package    juzaweb/cms
 * @author     The Anh Dang
 * @link       https://cms.juzaweb.com
 * @license    GNU V2
 */

namespace Juzaweb\Modules\Payment\Contracts;

interface PaymentManager
{
    public function registerDriver(string $name, callable $resolver): void;

    public function registerModule(string $name, ModuleHandlerInterface $handler): void;

    public function module(string $module): ModuleHandlerInterface;

    public function drivers(): array;

    public function modules(): array;

    public function driver(string $name, array $config): PaymentGatewayInterface;

    public function config(string $driver): array;

    public function renderConfig(string $driver, array $config = []): string;
}
