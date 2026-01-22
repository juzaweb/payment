<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml" class="anyflexbox boxshadow display-table">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="{{ __('Checkout') }} - {{ setting('sitename') }}" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('Checkout') }} - {{ setting('sitename') }}</title>
    <link rel="shortcut icon" href="{{ upload_url(config('icon')) }}" type="image/x-icon" />

    <link rel="stylesheet" href="{{ mix('css/checkout.min.css', 'modules/payment') }}">

    @php
        $activeTheme = active_theme();
    @endphp
    @if (view()->exists("{$activeTheme}::checkout.custom-css"))
        @include("{$activeTheme}::checkout.custom-css")
    @endif

    <script type="text/javascript" nonce="{{ csp_script_nonce() }}">
        var Juzaweb = Juzaweb || {};
        Juzaweb.store = '';
        Juzaweb.theme = {
            "id": 606449,
            "role": "main",
            "name": "{{ setting('sitename') }}"
        };
        Juzaweb.template = '';
        Juzaweb.purchaseUrl = "{{ route('payment.purchase', [$module]) }}";
    </script>

    <script type="text/javascript" nonce="{{ csp_script_nonce() }}">
        if (typeof Juzaweb == 'undefined') {
            Juzaweb = {};
        }
        Juzaweb.Checkout = {};
        Juzaweb.Checkout.token = "{{ $order->id }}";
        Juzaweb.Checkout.apiHost = "";
    </script>
    <script src="https://js.stripe.com/v3/?locale={{ app()->getLocale() }}"></script>
</head>

<body class="body--custom-background-color ">
    <div class="banner" data-header="">
        <div class="wrap">
            <div class="shop logo logo--left ">
                <h1 class="shop__name">
                    <a href="/">
                        {{ setting('sitename') }}
                    </a>
                </h1>
            </div>
        </div>
    </div>
    <button class="order-summary-toggle" bind-event-click="Juzaweb.StoreCheckout.toggleOrderSummary(this)">
        <div class="wrap">
            <h2>
                <label class="control-label">{{ trans('payment::translation.order') }}</label>
                <label class="control-label hidden-small-device">
                    ({{ $order->items->count() }} {{ trans('payment::translation.products') }})
                </label>
                <label class="control-label visible-small-device inline">
                    ({{ $order->items->count() }})
                </label>
            </h2>

            <a class="underline-none expandable pull-right" href="javascript:void(0)">
                {{ trans('payment::translation.view_detail') }}
            </a>
        </div>
    </button>

    <div context="paymentStatus"
        define='{ paymentStatus: new Juzaweb.PaymentStatus(this,{payment_processing:"",payment_provider_id:"",payment_info:{} }) }'>

    </div>
    <form method="post" action="{{ route('payment.purchase', [$module]) }}" data-toggle="validator"
        class="content stateful-form formCheckout">

        {{ csrf_field() }}
        <input type="hidden" name="order_id" value="{{ $order->id }}">
        <input type="hidden" name="method" value="{{ $paymentMethods[0]->driver ?? '' }}" id="payment-method-input">

        @php
            $stripeMethod = $paymentMethods->where('driver', 'Stripe')->first();
            $stripePublishKey = null;
            if ($stripeMethod) {
                $stripePublishKey = $stripeMethod->sandbox
                    ? $stripeMethod->getConfig('sandbox_publishable_key')
                    : $stripeMethod->getConfig('live_publishable_key');
            }
        @endphp
        <div class="wrap" context="checkout"
            define='{checkout: new Juzaweb.StoreCheckout(this,{
                token: "{{ $order->id }}",
                email: "{{ $order->creator->email }}",
                totalOrderItemPrice: "${{ $order->getTotalAmount() }}",
                shippingFee: 0,
                freeShipping: false,
                requiresShipping: false,
                existCode: false,
                code: "",
                discount: 0,
                settingLanguage: "{{ app()->getLocale() }}",
                moneyFormat: "",
                discountLabel: "{{ trans('payment::translation.free') }}",
                districtPolicy: "optional",
                wardPolicy: "hidden",
                district: "",
                ward: "",
                province:"",
                otherAddress: false,
                shippingId: 0,
                shippingMethods: {},
                customerAddressId: 0,
                reductionCode: "",
                stripePublishKey: "{{ $stripePublishKey }}"
            }),
            payment_method_id: "{{ $paymentMethods[0]->driver ?? '' }}"}'>
            <div class='sidebar '>
                <div class="sidebar_header">
                    <h2>
                        <label class="control-label">{{ trans('payment::translation.order') }}
                            ({{ $order->items->count() }} {{ trans('payment::translation.products') }})</label>
                    </h2>
                    <hr class="full_width" />
                </div>
                <div class="sidebar__content">
                    <div class="order-summary order-summary--product-list order-summary--is-collapsed">
                        <div class="summary-body summary-section summary-product">
                            <div class="summary-product-list">
                                <table class="product-table">
                                    <tbody>
                                        @foreach ($order->items as $item)
                                            <tr class="product product-has-image clearfix">
                                                <td>
                                                    <div class="product-thumbnail">
                                                        <div class="product-thumbnail__wrapper">
                                                            <img src="{{ $item->orderable->thumbnail }}"
                                                                class="product-thumbnail__image" alt="" />
                                                        </div>
                                                        <span class="product-thumbnail__quantity"
                                                            aria-hidden="true">{{ $item->quantity }}</span>
                                                    </div>
                                                </td>
                                                <td class="product-info">
                                                    <span class="product-info-name">
                                                        {{ $item->orderable?->name ?? $item->orderable?->title }}
                                                    </span>
                                                </td>

                                                <td class="product-price text-right">
                                                    ${{ $item->line_price }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                <div class="order-summary__scroll-indicator">
                                    {{ trans('payment::translation.scroll_mouse_to_view_more') }}
                                    <i class="fa fa-long-arrow-down" aria-hidden="true"></i>
                                </div>
                            </div>
                        </div>
                        <hr class="m0" />
                    </div>
                    <div class="order-summary order-summary--total-lines">
                        <div class="summary-section border-top-none--mobile">
                            <div class="total-line total-line-subtotal clearfix">
                                <span class="total-line-name pull-left">
                                    {{ trans('payment::translation.total_price') }}
                                </span>

                                <span bind="totalOrderItemPrice" class="total-line-subprice pull-right">
                                    ${{ $order->getTotalAmount() }}
                                </span>
                            </div>

                            <div class="total-line total-line-shipping clearfix" bind-show="requiresShipping">
                                <span class="total-line-name pull-left">
                                    {{ trans('payment::translation.shipping_fee') }}
                                </span>
                                <span
                                    bind="shippingFee !=  0 ? shippingFee : ((requiresShipping && shippingMethods.length == 0) ? 'This area does not support transportation': '{{ trans('payment::translation.free') }}')"
                                    class="total-line-shipping pull-right">
                                    {{ trans('payment::translation.free') }}
                                </span>
                            </div>

                            <div class="total-line total-line-total clearfix">
                                <span class="total-line-name pull-left">
                                    {{ trans('payment::translation.total') }}
                                </span>
                                <span bind="totalPrice" class="total-line-price pull-right">
                                    ${{ $order->getTotalAmount() }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="form-group clearfix hidden-sm hidden-xs">
                        <div class="field__input-btn-wrapper mt10">
                            <a class="previous-link" href="/">
                                <i class="fa fa-angle-left fa-lg" aria-hidden="true"></i>
                                <span>{{ trans('payment::translation.back_to_home') }}</span>
                            </a>
                            <button class="btn btn-primary btn-checkout"
                                data-loading-text="{{ trans('payment::translation.please_wait') }}" type="button"
                                id="btn-order-checkout">
                                {{ strtoupper(trans('payment::translation.pay_now')) }}
                            </button>
                        </div>
                    </div>
                    <div class="form-group has-error">
                        <div class="help-block ">
                            <ul class="list-unstyled">

                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <div class="main" role="main">
                <div class="main_header">
                    <div class="shop logo logo--left ">
                        <h1 class="shop__name">
                            <a href="/">
                                {{ setting('sitename') }}
                            </a>
                        </h1>
                    </div>
                </div>

                <div class="main_content">
                    <div class="row">
                        <div class="col-md-6 col-lg-6">
                            <h2 class="section__title layout-flex__item layout-flex__item--stretch">
                                <i class="fa fa-id-card-o fa-lg section__title--icon hidden-md hidden-lg"
                                    aria-hidden="true"></i>
                                <label class="control-label">{{ trans('payment::translation.information') }}</label>
                            </h2>

                            @guest
                                <a class="layout-flex__item section__title--link"
                                    href="{{ route('login') }}?redirect=/{{ url()->current() }}">
                                    <i class="fa fa-user-circle-o fa-lg" aria-hidden="true"></i>
                                    {{ trans('payment::translation.login') }}
                                </a>
                            @endguest

                            <div class="section__content">
                                <div class="form-group" bind-class="{'has-error' : invalidEmail}">
                                    <div>
                                        <label class="field__input-wrapper" bind-class="{ 'js-is-filled': email }">
                                            <span class="field__label" bind-event-click="handleClick(this)">
                                                {{ trans('payment::translation.email') }}
                                            </span>
                                            <input name="email" type="email" bind="email"
                                                class="field__input form-control" id="_email"
                                                data-error="{{ trans('payment::translation.email_is_malformed') }}"
                                                required value="{{ $order->creator->email }}" disabled />
                                        </label>
                                    </div>
                                    <div class="help-block with-errors">
                                    </div>
                                </div>

                                <div class="billing">
                                    <div>
                                        <div class="form-group">
                                            <div class="field__input-wrapper"
                                                bind-class="{ 'js-is-filled': billing_address.full_name }">
                                                <span class="field__label" bind-event-click="handleClick(this)">
                                                    {{ trans('payment::translation.full_name') }}
                                                </span>
                                                <input name="name" type="text"
                                                    class="field__input form-control" id="_billing_address_last_name"
                                                    data-error="{{ trans('payment::translation.please_enter_full_name') }}"
                                                    required bind="billing_address.full_name" autocomplete="off"
                                                    value="{{ $order->creator->name }}" disabled />
                                            </div>
                                            <div class="help-block with-errors"></div>
                                        </div>

                                        <div class="form-group">
                                            <div class="field__input-wrapper"
                                                bind-class="{ 'js-is-filled': billing_address.phone }">
                                                <span class="field__label" bind-event-click="handleClick(this)">
                                                    {{ trans('payment::translation.phone') }}
                                                </span>
                                                <input name="phone" type="tel"
                                                    class="field__input form-control" id="_billing_address_phone"
                                                    bind="billing_address.phone" value="{{ $order->creator->phone }}"
                                                    disabled />
                                            </div>
                                            <div class="help-block with-errors"></div>
                                        </div>


                                    </div>
                                </div>
                            </div>

                            <div class="section">
                                <div class="section__content">
                                    <div class="form-group m0">
                                        <div>
                                            <label class="field__input-wrapper" bind-class="{'js-is-filled': note}"
                                                style="border: none">
                                                <span class="field__label" bind-event-click="handleClick(this)">
                                                    {{ trans('payment::translation.note') }}
                                                </span>
                                                <textarea name="notes" disabled bind-event-change="saveAbandoned()" bind-event-focus="handleFocus(this)"
                                                    bind-event-blur="handleFieldBlur(this)" bind="note" class="field__input form-control m0">{{ $order->note }}</textarea>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-6">

                            {{-- <div class="section shipping-method hide" bind-show="shippingMethodsLoading || shippingMethods.length > 0">
                            <div class="section__header">
                                <h2 class="section__title">
                                    <i class="fa fa-truck fa-lg section__title--icon hidden-md hidden-lg" aria-hidden="true"></i>
                                    <label class="control-label">{{ trans('payment::translation.shipping') }}</label>
                                </h2>
                            </div>
                            <div class="section__content">
                                <div class="wait-loading-shipping-methods hide" bind-show="shippingMethodsLoading" style="margin-bottom:10px">
                                    <div class="next-spinner">
                                        <svg class="icon-svg icon-svg--color-accent icon-svg--size-32 icon-svg--spinner">
                                            <use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#next-spinner"></use>
                                        </svg>
                                    </div>
                                </div>
                                <div class="content-box" bind-show="!shippingMethodsLoading && shippingMethods.length > 0">

                                </div>
                            </div>
                        </div> --}}

                            <div class="section payment-methods">
                                <div class="section__header">
                                    <h2 class="section__title">
                                        <i class="fa fa-credit-card fa-lg section__title--icon hidden-md hidden-lg"
                                            aria-hidden="true"></i>
                                        <label
                                            class="control-label">{{ trans('payment::translation.payment') }}</label>
                                    </h2>
                                </div>
                                <div class="section__content">
                                    @foreach ($paymentMethods as $index => $paymentMethod)
                                        <div class="content-box">

                                            <div class="content-box__row">
                                                <div class="radio-wrapper">
                                                    <div class="radio__input">
                                                        <input class="input-radio" type="radio"
                                                            value="{{ $paymentMethod->driver }}" name="method"
                                                            id="payment_method_{{ $paymentMethod->id }}"
                                                            data-check-id="4" bind="payment_method_id"
                                                            @if ($index === 0) checked @endif>
                                                    </div>

                                                    <label class="radio__label"
                                                        for="payment_method_{{ $paymentMethod->id }}">
                                                        <span
                                                            class="radio__label__primary">{{ $paymentMethod->name }}</span>
                                                        <span class="radio__label__accessory">
                                                            <ul>
                                                                <li class="payment-icon-v2 payment-icon--4">
                                                                    <i class="fa fa-money payment-icon-fa"
                                                                        aria-hidden="true"></i>
                                                                </li>
                                                            </ul>
                                                        </span>
                                                    </label>
                                                </div>
                                            </div>

                                            @if ($paymentMethod->description)
                                                <div class="radio-wrapper content-box__row content-box__row--secondary hide"
                                                    id="payment-gateway-subfields-{{ $paymentMethod->id }}"
                                                    bind-show="payment_method_id == '{{ $paymentMethod->driver }}'">
                                                    <div class="blank-slate">
                                                        <p>{{ $paymentMethod->description }}</p>
                                                    </div>
                                                </div>
                                            @endif

                                            @if ($paymentMethod->driver === 'Stripe')
                                                <!-- Card element will be shown in modal -->
                                            @endif

                                        </div>
                                    @endforeach

                                </div>
                            </div>
                            <div class="section hidden-md hidden-lg">
                                <div class="form-group clearfix m0">
                                    <button class="btn btn-primary btn-checkout" data-loading-text="Đang xử lý"
                                        type="button" id="btn-order-checkout">
                                        {{ trans('payment::translation.pay_now') }}
                                    </button>
                                </div>
                                <div class="text-center mt20">
                                    <a class="previous-link" href="/cart">
                                        <i class="fa fa-angle-left fa-lg" aria-hidden="true"></i>
                                        <span>{{ trans('payment::translation.back_to_cart') }}</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- <div class="main_footer footer unprint">
                <div class="mt10"></div>
            </div> --}}

        <div class="modal fade" id="payment-modal" data-width="" tabindex="-1" role="dialog"
            data-backdrop="static" data-keyboard="false">
            <div class="modal-dialog modal-md">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">{{ trans('payment::translation.payment_your_order') }}</h4>
                    </div>
                    <div class="modal-body">
                        <div id="payment-container">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </div>

        <div class="modal fade" id="refund-policy" data-width="" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <button aria-hidden="true" data-dismiss="modal" class="close" type="button">×</button>
                        <h4 class="modal-title">{{ trans('payment::translation.refund_policy') }}</h4>
                    </div>
                    <div class="modal-body">
                        <pre></pre>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="privacy-policy" data-width="" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <button aria-hidden="true" data-dismiss="modal" class="close" type="button">×</button>
                        <h4 class="modal-title">{{ trans('payment::translation.privacy_policy') }}</h4>
                    </div>
                    <div class="modal-body">
                        <pre></pre>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="terms-of-service" data-width="" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <button aria-hidden="true" data-dismiss="modal" class="close" type="button">×</button>
                        <h4 class="modal-title">{{ trans('payment::translation.terms_of_service') }}</h4>
                    </div>
                    <div class="modal-body">
                        <pre></pre>
                    </div>
                </div>
            </div>
        </div>
        </div>
        </div>
    </form>
    <div id="icon-symbols" style="display: none;">
        <svg xmlns="http://www.w3.org/2000/svg">
            <symbol id="spinner-large"><svg xmlns="http://www.w3.org/2000/svg" viewBox="-270 364 66 66">
                    <path
                        d="M-237 428c-17.1 0-31-13.9-31-31s13.9-31 31-31v-2c-18.2 0-33 14.8-33 33s14.8 33 33 33 33-14.8 33-33h-2c0 17.1-13.9 31-31 31z" />
                </svg></symbol>
            <symbol id="chevron-right"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10">
                    <path d="M2 1l1-1 4 4 1 1-1 1-4 4-1-1 4-4" />
                </svg></symbol>
            <symbol id="success"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                    <path
                        d="M12 24C5.373 24 0 18.627 0 12S5.373 0 12 0s12 5.373 12 12-5.373 12-12 12zm0-2c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10zm3.784-13.198c.386-.396 1.02-.404 1.414-.018.396.386.404 1.02.018 1.414l-5.85 6c-.392.403-1.04.403-1.432 0l-3.15-3.23c-.386-.396-.378-1.03.018-1.415.395-.385 1.028-.377 1.414.018l2.434 2.5 5.134-5.267z" />
                </svg></symbol>
            <symbol id="arrow"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16">
                    <path d="M16 8.1l-8.1 8.1-1.1-1.1L13 8.9H0V7.3h13L6.8 1.1 7.9 0 16 8.1z" />
                </svg></symbol>
            <symbol id="spinner-button"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                    <path
                        d="M20 10c0 5.523-4.477 10-10 10S0 15.523 0 10 4.477 0 10 0v2c-4.418 0-8 3.582-8 8s3.582 8 8 8 8-3.582 8-8h2z" />
                </svg></symbol>
            <symbol id="next-spinner"><svg preserveAspectRatio="xMinYMin">
                    <circle class="next-spinner__ring" cx="50%" cy="50%" r="45%"></circle>
                </svg></symbol>
        </svg>
    </div>
    <script type="text/javascript" nonce="{{ csp_script_nonce() }}">
        var code_langs = {
            'choose_province': '{{ trans('payment::theme.choose_province') }}',
            'cardholder_name': '{{ trans('payment::theme.cardholder_name') }}',
            'card_information': '{{ trans('payment::theme.card_information') }}',
            'cancel': '{{ trans('payment::theme.cancel') }}',
            'pay_now': '{{ trans('payment::theme.pay_now') }}',
            'processing': '{{ trans('payment::theme.processing') }}',
            'complete_payment': '{{ trans('payment::theme.complete_payment') }}',
        };
    </script>
    <script src="https://code.jquery.com/jquery-2.2.4.min.js" type="text/javascript"></script>
    <script src="{{ mix('js/checkout.min.js', 'modules/payment') }}" type="text/javascript"></script>

    <script type="text/javascript" nonce="{{ csp_script_nonce() }}">
        $(document).ajaxStart(function() {
            NProgress.start();
        });
        $(document).ajaxComplete(function() {
            NProgress.done();
        });

        context = {};

        $(function() {
            Twine.reset(context).bind().refresh();

            // Handle order checkout button
            $('#btn-order-checkout').on('click', function() {
                var checkoutContext = Twine.context(document.querySelector('[context="checkout"]'));
                if (checkoutContext && checkoutContext.checkout) {
                    var selectedMethod = $('input[name="method"]:checked').val();

                    if (selectedMethod === 'Stripe') {
                        // Show Stripe modal for card input
                        checkoutContext.checkout.showStripeModal('{{ $order->id }}');
                    } else {
                        // Submit form for other payment methods
                        $('.formCheckout').submit();
                    }
                }
            });
        });

        $(document).ready(function() {
            var $select2 = $('.filter-dropdown').select2({
                containerCssClass: 'field__input',
                dropdownCssClass: 'field__input',
                dropdownParent: $('.main_content'),
                language: {
                    noResults: function() {
                        return "{{ trans('payment::translation.no_results') }}"
                    },
                    searching: function() {
                        return "{{ trans('payment::translation.searching') }}…"
                    }
                }
            });

            setTimeout(function() {
                Twine.context(document.body).checkout.calculateFeeAndSave('');
            }, 50);

        });
    </script>

</body>

</html>
