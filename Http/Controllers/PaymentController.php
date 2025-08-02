<?php

namespace Juzaweb\Modules\Payment\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Juzaweb\Core\Http\Controllers\ThemeController;
use Juzaweb\Modules\Payment\Exceptions\PaymentException;
use Juzaweb\Modules\Payment\Http\Requests\PaymentRequest;
use Juzaweb\Modules\Payment\Models\PaymentHistory;
use Juzaweb\Modules\Payment\Models\PaymentMethod;
use Juzaweb\Modules\Payment\Services\PaymentManager;

class PaymentController extends ThemeController
{
    public function purchase(PaymentRequest $request, string $module, string $method)
    {
        $user = $request->user();
        $paymentMethod = PaymentMethod::where('driver', $method)
            ->where('active', true)
            ->first();

        abort_if($paymentMethod == null, 404, __('Payment method not found!'));

        $paymentHistory = new PaymentHistory(
            [
                'payment_method' => $method,
                'status' => 'processing',
                'module' => $module,
            ]
        );

        $paymentHistory->payer()->associate($user);

        $paymentHistory->save();

        try {
            $payment = DB::transaction(
                fn () => PaymentManager::create($module, $paymentMethod)
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
