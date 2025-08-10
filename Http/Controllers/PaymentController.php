<?php

namespace Juzaweb\Modules\Payment\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Juzaweb\Core\Http\Controllers\ThemeController;
use Juzaweb\Modules\Payment\Enums\PaymentHistoryStatus;
use Juzaweb\Modules\Payment\Exceptions\PaymentException;
use Juzaweb\Modules\Payment\Facades\PaymentManager;
use Juzaweb\Modules\Payment\Http\Requests\PaymentRequest;
use Juzaweb\Modules\Payment\Models\PaymentHistory;

class PaymentController extends ThemeController
{
    public function purchase(PaymentRequest $request, string $module)
    {
        $user = $request->user();
        $method = $request->post('method');

        try {
            $payment = DB::transaction(
                function () use ($module, $request, $user, $method) {
                    return PaymentManager::create($user, $module, $method, $request->all());
                }
            );
        } catch (PaymentException $e) {
            return $this->error($e->getMessage());
        }

        if ($payment->isSuccessful()) {
            return $this->success(__('Payment successful!'));
        }

        if ($payment->isRedirect()) {
            return $this->success(
                [
                    'type' => 'redirect',
                    'redirect' => $payment->getRedirectUrl(),
                    'message' => __('Redirecting to payment gateway...'),
                ]
            );
        }

        if ($payment->isEmbed()) {
            return $this->success(
                [
                    'type' => 'embed',
                    'embedUrl' => $payment->getEmbedUrl(),
                ]
            );
        }

        return $this->failResponse();
    }

    public function return(Request $request, string $module, string $paymentHistoryId)
    {
        $paymentModule = PaymentManager::module($module);

        try {
            $payment = DB::transaction(
                function () use ($request, $module, $paymentHistoryId) {
                    $paymentHistory = PaymentHistory::lockForUpdate()->find($paymentHistoryId);

                    throw_if($paymentHistory == null, new PaymentException(__('Payment transaction not found!')));

                    throw_if($paymentHistory->status !== PaymentHistoryStatus::PROCESSING, new PaymentException(__('Transaction has been processed!')));

                    return PaymentManager::complete($module, $paymentHistory, $request->all());
                }
            );
        } catch (PaymentException $e) {
            return $this->error(
                [
                    'message' => $e->getMessage(),
                    'redirect' => $paymentModule->getReturnUrl(),
                ]
            );
        }

        if ($payment->isSuccessful()) {
            return $this->success(
                [
                    'message' => __('Payment completed successfully!'),
                    'redirect' => $paymentModule->getReturnUrl(),
                ]
            );
        }

        return $this->failResponse($paymentModule->getReturnUrl());
    }

    public function cancel(Request $request, string $module, string $transactionId)
    {
        $paymentModule = PaymentManager::module($module);

        try {
            $paymentHistory = PaymentHistory::lockForUpdate()->find($transactionId);

            abort_if($paymentHistory == null, 404, __('Transaction not found!'));

            $payment = DB::transaction(fn () => PaymentManager::cancel($module, $paymentHistory, $request->all()));
        } catch (PaymentException $e) {
            return $this->error($e->getMessage());
        }

        return $this->warning(
            [
                'message' => __('Payment has been cancelled!'),
                'redirect' => $paymentModule->getReturnUrl(),
            ]
        );
    }

    protected function failResponse(?string $redirectUrl = null)
    {
        if ($redirectUrl) {
            return $this->error(
                [
                    'message' => __('Payment failed!'),
                    'redirect' => $redirectUrl,
                ]
            );
        }

        return $this->error(
            __('Sorry, there was an error processing your payment. Please try again later.')
        );
    }
}
