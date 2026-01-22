@extends('core::layouts.admin')

@section('content')
    <form action="{{ $action }}" class="form-ajax" method="post">
        @if ($model->exists)
            @method('PUT')
        @endif

        <div class="row">
            <div class="col-md-12">
                <a href="{{ $backUrl }}" class="btn btn-warning">
                    <i class="fas fa-arrow-left"></i> {{ __('Back') }}
                </a>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> {{ __('Save') }}
                </button>
            </div>
        </div>

        <div class="row mt-3">
            <div class="col-md-9">
                <x-card title="{{ __('Order Information') }}">
                    <div class="row">
                        <div class="col-md-6">
                            {{ Field::text($model, 'code')->disabled() }}
                        </div>
                        <div class="col-md-6">
                            {{ Field::text($model, 'payment_method_name', ['label' => __('Payment method')])->disabled() }}
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            {{ Field::text($model, 'quantity')->disabled() }}
                        </div>

                        <div class="col-md-4">
                            {{ Field::text($model, 'total')->value(format_price($model->total))->disabled() }}
                        </div>
                    </div>
                </x-card>

                <x-card title="{{ __('Order Items') }}" class="mt-3">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>{{ __('Title') }}</th>
                                <th class="text-center">{{ __('Price') }}</th>
                                <th class="text-center">{{ __('Quantity') }}</th>
                                <th class="text-center">{{ __('Total') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($model->items as $item)
                                <tr>
                                    <td>{{ $item->title }}</td>
                                    <td class="text-center">{{ format_price($item->price) }}@if($item->compare_price) (<del>{{ format_price($item->compare_price) }}</del>) @endif</td>
                                    <td class="text-center">{{ $item->quantity }}</td>
                                    <td class="text-center">{{ format_price($item->line_price) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </x-card>

                {{-- <x-card title="{{ __('Shipping Information') }}" class="mt-3">
                    {{ Field::text($model, 'address')->disabled() }}

                    {{ Field::text($model, 'country_code')->disabled() }}
                </x-card> --}}

                <x-card title="{{ __('Note') }}" class="mt-3">
                    {{ Field::textarea($model, 'note')->disabled() }}
                </x-card>
            </div>

            <div class="col-md-3">
                <x-card title="{{ __('Statuses') }}">
                    {{ Field::select($model, 'payment_status')->value($model->payment_status?->value)->dropDownList(\Juzaweb\Modules\Payment\Enums\OrderPaymentStatus::all()) }}

                    {{ Field::select($model, 'delivery_status')->value($model->delivery_status?->value)->dropDownList(\Juzaweb\Modules\Payment\Enums\OrderDeliveryStatus::all()) }}
                </x-card>
            </div>
        </div>
    </form>
@endsection

@section('scripts')
    <script type="text/javascript" nonce="{{ csp_script_nonce() }}">
        $(function() {
            //
        });
    </script>
@endsection
