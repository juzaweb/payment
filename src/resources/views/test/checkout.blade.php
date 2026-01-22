<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Test Payment') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container pt-5 pb-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">{{ __('Test Payment') }}</h4>
                    </div>

                    <div class="card-body">
                        <p>{{ __('Amount') }}: {{ $amount }} {{ $currency }}</p>

                        <div class="mt-4">
                            <a href="{{ $returnUrl }}" class="btn btn-success">
                                {{ __('Payment Success') }}
                            </a>

                            <a href="{{ $cancelUrl }}" class="btn btn-danger ml-2">
                                {{ __('Cancel Payment') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
