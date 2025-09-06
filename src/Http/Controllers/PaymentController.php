<?php

namespace Juzaweb\Modules\Payment\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Juzaweb\Core\Http\Controllers\ThemeController;
use Juzaweb\Modules\Payment\Enums\PaymentHistoryStatus;
use Juzaweb\Modules\Payment\Events\PaymentFail;
use Juzaweb\Modules\Payment\Events\PaymentSuccess;
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
        $method = PaymentMethod::where('driver', $request->get('method'))
            ->where('active', true)
            ->first();

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
            if ($method->paymentDriver()->isReturnInEmbed()) {
                return $this->success(
                    [
                        'type' => 'embed',
                        'embed_url' => $payment->getRedirectUrl(),
                        'payment_history_id' => $payment->getPaymentHistory()->id,
                    ]
                );
            }

            return $this->success(
                [
                    'type' => 'redirect',
                    'redirect' => $payment->getRedirectUrl(),
                    'message' => __('Redirecting to payment gateway...'),
                ]
            );
        }

        return $this->failResponse();
    }

    public function return(Request $request, string $module, string $paymentHistoryId)
    {
        $paymentModule = PaymentManager::module($module);
        $returnUrl = $paymentModule->getReturnUrl();

        try {
            $payment = DB::transaction(
                function () use ($request, $module, $paymentHistoryId, &$returnUrl) {
                    $paymentHistory = PaymentHistory::lockForUpdate()->find($paymentHistoryId);

                    throw_if($paymentHistory == null, new PaymentException(__('Payment transaction not found!')));

                    throw_if($paymentHistory->status !== PaymentHistoryStatus::PROCESSING, new PaymentException(__('Transaction has been processed!')));

                    $gateway = $paymentHistory->paymentMethod->paymentDriver();

                    if ($gateway->isReturnInEmbed()) {
                        $returnUrl = route('payment.embed', [$module, $paymentHistoryId]);
                    }

                    return PaymentManager::complete($module, $paymentHistory, $request->all());
                }
            );
        } catch (PaymentException $e) {
            return $this->error(
                [
                    'message' => $e->getMessage(),
                    'redirect' => $returnUrl,
                ]
            );
        }

        if ($payment->isSuccessful()) {
            return $this->success(
                [
                    'message' => __('Payment completed successfully!'),
                    'redirect' => $returnUrl,
                ]
            );
        }

        return $this->failResponse($returnUrl);
    }

    public function cancel(Request $request, string $module, string $transactionId)
    {
        $paymentModule = PaymentManager::module($module);
        $returnUrl = $paymentModule->getReturnUrl();

        try {
            $payment = DB::transaction(
                function () use ($transactionId, $module, $request) {
                    $paymentHistory = PaymentHistory::lockForUpdate()->find($transactionId);

                    throw_if($paymentHistory == null, new PaymentException(__('Payment transaction not found!')));

                    return PaymentManager::cancel($module, $paymentHistory, $request->all());
                }
            );
        } catch (PaymentException $e) {
            return $this->error([
                'message' => $e->getMessage(),
                'redirect' => $returnUrl,
            ]);
        }

        return $this->warning(
            [
                'message' => __('Payment has been cancelled!'),
                'redirect' => $returnUrl,
            ]
        );
    }

    public function webhook(Request $request, string $module, string $driver)
    {
        $handler = PaymentManager::module($module);
        $paymentMethod = PaymentMethod::where('driver', $driver)
            ->where('active', true)
            ->first();

        if ($paymentMethod == null) {
            Log::error(
                'Payment method not found',
                [
                    'module' => $module,
                    'driver' => $driver,
                    'request' => $request->all(),
                ]
            );

            return response(
                [
                    'message' => __('Payment method not found!'),
                    'success' => false,
                ]
            );
        }

        $gateway = $paymentMethod->paymentDriver();

        $response = $gateway->handleWebhook($request);

        if ($response === null) {
            return response(['success' => true]);
        }

        try {
            $payment = DB::transaction(
                function () use ($request, $handler, $module, $response) {
                    $paymentHistory = PaymentHistory::lockForUpdate()
                        ->where(['payment_id' => $response->getTransactionId(), 'module' => $module])
                        ->first();

                    throw_if($paymentHistory == null, new PaymentException(__('Payment transaction not found!')));

                    throw_if($paymentHistory->status !== PaymentHistoryStatus::PROCESSING, new PaymentException(__('Transaction has been processed!')));

                    if ($response->isSuccessful()) {
                        $handler->success($paymentHistory->paymentable, $request->all());

                        $paymentHistory->update(
                            [
                                'status' => PaymentHistoryStatus::SUCCESS,
                            ]
                        );

                        event(new PaymentSuccess($paymentHistory->paymentable, $request->all()));
                    } else {
                        $handler->fail($paymentHistory->paymentable, $request->all());

                        $paymentHistory->update(
                            [
                                'status' => PaymentHistoryStatus::FAILED,
                            ]
                        );

                        event(new PaymentFail($paymentHistory->paymentable, $request->all()));
                    }

                    return $paymentHistory;
                }
            );
        } catch (PaymentException $e) {
            report($e);
            return response(
                [
                    'message' => $e->getMessage(),
                    'success' => false,
                ]
            );
        }

        return response(['success' => true]);
    }

    public function embed(string $module, string $transactionId)
    {
        $paymentHistory = PaymentHistory::find($transactionId);

        throw_if($paymentHistory == null, new PaymentException(__('Payment transaction not found!')));

        // $paymentHistory->load(['paymentable']);

        return view(
            'payment::method.embed',
            compact('module', 'paymentHistory')
        );
    }

    public function status(string $module, string $transactionId)
    {
        $paymentHistory = PaymentHistory::find($transactionId);

        throw_if($paymentHistory == null, new PaymentException(__('Payment transaction not found!')));

        $paymentHistory->load(['paymentable']);

        return $this->success(
            [
                'status' => $paymentHistory->status->value,
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
