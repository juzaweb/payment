@extends('core::layouts.admin')

@section('content')
    <div class="row">
        <div class="col-md-12">
            @can('payment-methods.create')
                <a href="{{ $createUrl }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> {{ __('Add Payment Method') }}
                </a>
            @endcan
        </div>
    </div>

    <div class="row mt-3">
        <div class="col-md-12">
            {{--@component('components.datatables.filters')
                <div class="col-md-3 jw-datatable_filters">

                </div>
            @endcomponent--}}
        </div>

        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ __('Payment Methods') }}</h3>
                </div>
                <div class="card-body">
                    {{ $dataTable->table() }}
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    {{--<script src="https://js.stripe.com/v3/"></script>
    <script src="{{ asset('payment.js') }}"></script>--}}

    <script type="text/javascript" nonce="{{ csp_script_nonce() }}">
        $(function () {
            /*const paymentForm = new PaymentForm(
                'test',
                '#payment-form',
                {
                    stripePublishKey: '{{ $paymentMethods->where('driver', 'Stripe')->first()->config['publishable_key'] ?? '' }}',
                    language: {
                        'cardholder_name': '{{ __('Cardholder Name') }}',
                        'card_number': '{{ __('Card Number') }}',
                        'expiry_date': '{{ __('Expiry Date') }}',
                        'cvc': '{{ __('CVC') }}',
                        'stripe_publish_key_not_set': '{{ __('Stripe publish key is not set.') }}',
                    },
                    onSuccess: function (response) {
                        show_notify({
                            success: true,
                            message: '{{ __('Payment request sent successfully!') }}',
                        });

                        $('#exampleModal').modal('hide');
                    },
                    onError: function (error) {
                        show_notify({
                            success: false,
                            message: error.message || '{{ __('An error occurred while processing the payment request.') }}',
                        });

                        $('#exampleModal').modal('hide');
                    }
                }
            );*/
        });
    </script>

    {{--<div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <form method="post"
                  action="{{ route('payment.purchase', ['test']) }}"
                  data-success="handlePaymentSuccess"
                  id="payment-form"
            >
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">{{ __('Test Payment') }}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div id="payment-container">
                            {{ Field::select(__('Method'), 'method')->dropDownList(
                                $paymentMethods->pluck('name', 'driver')->toArray()
                            ) }}

                            {{ Field::text(__('Amount'), 'amount', ['value' => 10]) }}

                            <div id="form-card"></div>

                            <div id="payment-message"></div>

                            <button type="submit" class="btn btn-primary">{{ __('Send Payment Request') }}</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>--}}

    {{ $dataTable->scripts(null, ['nonce' => csp_script_nonce()]) }}
@endsection
