<!DOCTYPE html>
<html lang="en">
<head>
    @php
        $breadcrumbs = \Juzaweb\Core\Facades\Breadcrumb::getItems();
        $title = $breadcrumbs ? last($breadcrumbs)['title'] : __('Dashboard');
    @endphp
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} | Juzaweb</title>

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <link rel="shortcut icon" href="{{ setting('favicon') ? upload_url(setting('favicon')) : '/favicon.ico' }}" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/flag-icon-css/6.6.6/css/flag-icons.min.css">
    <link rel="stylesheet" href="{{ mix('css/vendor.min.css', 'vendor/core') }}">
    <link rel="stylesheet" href="{{ mix('css/admin.min.css', 'vendor/core') }}">

    @yield('head')
</head>

<body>

    @if($paymentHistory->status === \Juzaweb\Modules\Payment\Enums\PaymentHistoryStatus::SUCCESS)
    <div class="alert alert-success">
        <strong>{{ __('Successful') }}</strong>
        <p>{{ __('Payment successful :amount', ['amount' => $paymentHistory->paymentable?->getTotalAmount()]) }}</p>
    </div>
    @else
    <div class="alert alert-danger">
        <strong>{{ __('Failed') }}</strong>
        <p>{{ __('Payment failed :amount', ['amount' => $paymentHistory->paymentable?->getTotalAmount()]) }}</p>
    </div>
    @endif

</body>
</html>
