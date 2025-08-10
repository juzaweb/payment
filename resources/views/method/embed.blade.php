@if($paymentHistory->status === \Juzaweb\Modules\Payment\Enums\PaymentHistoryStatus::SUCCESS)
<div class="alert alert-success">
    <strong>{{ __('Successful') }}</strong>
    <p>{{ __('Payment successful :amount', ['amount' => $paymentHistory->amount]) }}</p>
</div>
@else
<div class="alert alert-danger">
    <strong>{{ __('Failed') }}</strong>
    <p>{{ __('Payment failed :amount', ['amount' => $paymentHistory->amount]) }}</p>
</div>
@endif
