<?php

namespace Juzaweb\Modules\Payment\Http\Controllers;

use Illuminate\Http\Request;
use Juzaweb\Core\Http\Controllers\ThemeController;
use Juzaweb\Modules\Payment\Http\Requests\PaymentRequest;

class PaymentController extends ThemeController
{
    public function purchase(PaymentRequest $request, string $module): JsonResponse
    {
        $method = Payment::method($request->input('method'));

        try {
            $payment = DB::transaction(
                fn () => Payment::create($request, $module, $method)
            );
        } catch (PaymentException $e) {
            return $this->restFail($e->getMessage());
        }

        if ($payment->isSuccessful()) {
            return $this->restSuccess(
                [
                    'type' => 'complete',
                    'transaction_id' => $payment->transactionId,
                    'status' => $payment->status,
                    'module' => $module,
                ],
                __('Payment successful!')
            );
        }

        if ($payment->isRedirect) {
            return $this->restSuccess(
                [
                    'type' => 'redirect',
                    'redirect_url' => $payment->getRedirectUrl(),
                    'status' => $payment->status,
                    'module' => $module,
                ],
                __('Redirecting...')
            );
        }

        return $this->failResponse($payment);
    }

    public function complete(Request $request, string $module, string $transactionId): JsonResponse
    {
        try {
            $payment = DB::transaction(
                function () use ($request, $transactionId) {
                    $paymentHistory = PaymentHistory::lockForUpdate()->find($transactionId);

                    throw_if($paymentHistory == null, new PaymentException(__('Payment transaction not found!')));

                    throw_if($paymentHistory->status !== PaymentHistory::STATUS_PROCESSING, new PaymentException(__('Transaction has been processed!')));

                    return Payment::complete($request, $paymentHistory);
                }
            );
        } catch (PaymentException $e) {
            return $this->restFail($e->getMessage());
        }

        if ($payment->isSuccessful()) {
            return $this->restSuccess(
                [
                    'type' => 'complete',
                    'transaction_id' => $transactionId,
                    'status' => $payment->status,
                    'module' => $module,
                ],
                __('Payment successful!')
            );
        }

        return $this->failResponse($payment);
    }

    public function cancel(Request $request, string $module, string $transactionId): JsonResponse
    {
        try {
            $paymentHistory = PaymentHistory::lockForUpdate()->find($transactionId);

            abort_if($paymentHistory == null, 404, __('Payment transaction not found!'));

            $payment = DB::transaction(fn () => Payment::cancel($request, $paymentHistory));
        } catch (PaymentException $e) {
            return $this->restFail($e->getMessage());
        }

        return $this->restSuccess(
            [
                'type' => 'cancel',
                'transaction_id' => $transactionId,
                'status' => $payment->status,
                'module' => $module,
            ],
            __('Payment canceled!')
        );
    }

    protected function failResponse(PaymentResult $result): JsonResponse
    {
        return $this->restFail(
            __('Sorry, there was an error processing your payment. Please try again later.')
        );
    }
}
