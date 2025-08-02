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
use Juzaweb\Modules\Payment\Models\PaymentMethod;

class PaymentController extends ThemeController
{
    public function purchase(PaymentRequest $request, string $module)
    {
        $user = $request->user();
        $method = $request->post('method');
        $paymentMethod = PaymentMethod::where('driver', $method)
            ->where('active', true)
            ->first();

        abort_if($paymentMethod == null, 404, __('Payment method not found!'));

        try {
            $payment = DB::transaction(
                function () use ($module, $paymentMethod, $request, $user, $method) {
                    $paymentHistory = new PaymentHistory(
                        [
                            'payment_method' => $method,
                            'status' => PaymentHistoryStatus::PROCESSING,
                            'module' => $module,
                        ]
                    );

                    $paymentHistory->payer()->associate($user);

                    $paymentHistory->save();

                    return PaymentManager::create($module, $paymentMethod, $request->all());
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
                    'redirect_url' => $payment->getRedirectUrl(),
                ]
            );
        }

        return $this->failResponse();
    }

    public function complete(Request $request, string $module, string $transactionId)
    {
        try {
            $payment = DB::transaction(
                function () use ($request, $transactionId) {
                    $paymentHistory = PaymentHistory::lockForUpdate()->find($transactionId);

                    throw_if($paymentHistory == null, new PaymentException(__('Payment transaction not found!')));

                    throw_if($paymentHistory->status !== PaymentHistoryStatus::PROCESSING, new PaymentException(__('Transaction has been processed!')));

                    return PaymentManager::complete($paymentHistory, $request->all());
                }
            );
        } catch (PaymentException $e) {
            return $this->error($e->getMessage());
        }

        if ($payment->isSuccessful()) {
            return $this->success(__('Payment successful!'));
        }

        return $this->failResponse();
    }

    public function cancel(Request $request, string $module, string $transactionId)
    {
        try {
            $paymentHistory = PaymentHistory::lockForUpdate()->find($transactionId);

            abort_if($paymentHistory == null, 404, __('Transaction not found!'));

            $payment = DB::transaction(fn () => PaymentManager::cancel($module, $paymentHistory, $request->all()));
        } catch (PaymentException $e) {
            return $this->error($e->getMessage());
        }

        return $this->success(__('Payment canceled!'));
    }

    protected function failResponse()
    {
        return $this->error(
            __('Sorry, there was an error processing your payment. Please try again later.')
        );
    }
}
