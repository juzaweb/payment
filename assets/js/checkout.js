window.Juzaweb || (window.Juzaweb = {});
(function () {
    Juzaweb.Utility = {
        getParameter: function (name) {
            name = name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
            var regex = new RegExp("[\\?&]" + name + "=([^&#]*)"),
                results = regex.exec(location.search);
            return results === null ? "" : decodeURIComponent(results[1].replace(/\+/g, " "));
        },
        checkEmbed: function () {
            return this.getParameter('embed') === '1';
        },
        redirect: function (url) {
            if (this.checkEmbed()) {
                window.top.location.href = url;
            } else {
                window.location.href = url;
            }
        }
    };
    Juzaweb.Template = {
        SHIPPING_METHOD: '<div class="content-box__row"><div class="radio-wrapper"><div class="radio__input"><input class="input-radio" type="radio" value="{{shipping_method_value}}" name="ShippingMethod" id="shipping_method_{{shipping_method_id}}" bind="shippingMethod" bind-event-change="changeShippingMethod()" fee="{{shipping_method_fee}}" /></div><label class="radio__label" for="shipping_method_{{shipping_method_id}}"> <span class="radio__label__primary">{{shipping_method_name}}</span><span class="radio__label__accessory"><span class="content-box__emphasis">{{shipping_method_fee_text}}</span></span></label> </div> <!-- /radio-wrapper--> </div>'
    };

    Juzaweb.StoreCheckout = function () {
        function StoreCheckout(e, options) {
            if (!options)
                options = {};

            this.ele = e;
            this.existCode = options.existCode;
            this.totalOrderItemPrice = options.totalOrderItemPrice;
            this.discount = options.discount;
            this.shippingFee = options.shippingFee;
            this.freeShipping = options.freeShipping;
            this.requiresShipping = options.requiresShipping;
            this.code = options.code;
            this.inValidCode = false;
            this.applyWithPromotion = true;
            this.discountShipping = false;
            this.loadedShippingMethods = false;
            this.settingLanguage = options.settingLanguage;
            this.invalidEmail = false;
            this.moneyFormat = options.moneyFormat;
            this.discountLabel = options.discountLabel;
            this.districtPolicy = options.districtPolicy;
            this.district = options.district;
            this.wardPolicy = options.wardPolicy;
            this.ward = options.ward;
            this.billingLatLng = {};
            this.shippingLatLng = {};
            this.checkToEnableScrollIndicator();
            this.customerAddress = null;
            this.province = options.province;
            this.token = options.token;
            this.email = options.email;
            this.otherAddress = options.otherAddress;
            this.shippingId = options.shippingId;
            this.shippingMethods = options.shippingMethods;
            this.shippingMethodsLoading = false;
            this.stripePublishKey = options.stripePublishKey;
            this.stripe = null;
            this.stripeElement = null;

            this.$ajax = null;
            this.$calculateFee = null;
            this.ajaxAbandonedTimeout = null;

            this.bindRequiredOtherAddress();

            this.reduction_code = options.reductionCode != "";
        };

        function isEmail(email) {
            var regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;
            return regex.test(email);
        }

        StoreCheckout.prototype.bindRequiredOtherAddress = function () {
            if (this.otherAddress) {
                $("#_shipping_address_last_name").prop('required', true);
                $("#_shipping_address_phone").prop('required', true);
                $("#_shipping_address_address1").prop('required', true);
                $("#shippingProvince").prop('required', true);
                $("#shippingDistrict").prop('required', true);
                $("#shippingWard").prop('required', true);
            } else {
                $("#_shipping_address_last_name").removeAttr('required');
                $("#_shipping_address_phone").removeAttr('required');
                $("#_shipping_address_address1").removeAttr('required');
                $("#shippingProvince").removeAttr('required');
                $("#shippingDistrict").removeAttr('required');
                $("#shippingWard").removeAttr('required');
            }
        };

        StoreCheckout.prototype.handleClick = function (element) {
            $(element).closest(".field__input-wrapper").find(".field__input").focus();
        };

        StoreCheckout.prototype.handleFocus = function (element) {
            $(element).closest(".field__input-wrapper").addClass("js-is-focused")
        };

        StoreCheckout.prototype.handleFieldBlur = function (element) {
            $(element).closest(".field__input-wrapper").removeClass("js-is-focused")
        };

        StoreCheckout.prototype.checkToEnableScrollIndicator = function () {
            var $summaryWrapper = $(".summary-product");
            var $productTable = $(".product-table");

            if ($summaryWrapper.height() < $productTable.height()) {
                $summaryWrapper.addClass("order-summary--is-scrollable");

                $(".order-summary--is-scrollable").scroll(function () {
                    $(this).removeClass("order-summary--is-scrollable");
                });
            }
        };

        StoreCheckout.prototype.changeCustomerAddress = function (model) {
            if (!model.id) {
                model = {
                    "address1": null,
                    "address2": null,
                    "city": "",
                    "full_name": "",
                    "phone": "",
                    "province": "",
                    "province_code": "",
                    "district": "",
                    "district_code": "",
                    "ward": "",
                    "ward_code": "",
                };
                this.billing_address = model;
                this.shipping_address = model;
            }

            this.customerAddress = model;

            $("select[name='BillingProvinceId'] option").filter(function () {
                return $(this).text() == model.city;
            }).prop('selected', true).trigger("change");
        };

        StoreCheckout.prototype.changeEmail = function () {
            var email = $("#_email").val();
            if (isEmail(email)) {
                if (!!this.code) {
                    this.caculateShippingFee();
                }

                this.abandonedCheckout();
            }
        };

        StoreCheckout.prototype.saveAbandoned = function () {
            this.abandonedCheckout();
        };

        StoreCheckout.prototype.billingCountryChange = function (designThemeId) {
            if (!this.ortherAddress) {
                var that = this;
                if (this.show_country) {
                    var url = "/checkout/getprovinces/" + (!that.BillingCountryId ? 'none' : that.BillingCountryId);
                    if (!!designThemeId) {
                        url += "&designThemeId=" + designThemeId;
                    }
                    $.ajax({
                        url: url,
                        success: function (data) {
                            var html = "<option value=''>--- " + code_langs.choose_province + " ---</option>";

                            for (var i = 0; i < data.length; i++) {
                                var province = data[i];
                                var selected = (that.province === province.name) || (that.customerAddress != null && that.customerAddress.province === province.name);
                                html += "<option value='" + province.id + "'" + (selected ? "selected='selected'" : "") + ">" + province.name + "</option>";
                            }

                            $("select[name='BillingProvinceId']").empty().html(html);
                            $("select[name='BillingProvinceId']").trigger("change");
                        }
                    });
                }
            }
        };

        StoreCheckout.prototype.billingProviceChange = function (designThemeId) {
            if (!this.otherAddress) {
                var that = this;
                if (this.show_district) {
                    var url = "/checkout/getdistricts?provinceId=" + that.BillingProvinceId;
                    if (!!designThemeId) {
                        url += "&designThemeId=" + designThemeId;
                    }
                    $.ajax({
                        url: url,
                        success: function (data) {
                            var html = "<option value=''>---  ---</option>";

                            for (var i = 0; i < data.length; i++) {
                                var district = data[i];
                                var selected = (that.district === district.name) || (that.customerAddress != null && that.customerAddress.district === district.name);
                                html += "<option value='" + district.id + "'" + (selected ? "selected='selected'" : "") + ">" + district.name + "</option>"
                            }

                            $("select[name='BillingDistrictId']").empty().html(html);
                            $("select[name='BillingDistrictId']").trigger("change");
                        }
                    });
                } else {
                    this.caculateShippingFee(designThemeId);
                }
            }

            this.abandonedCheckout();
        };

        StoreCheckout.prototype.caculateShippingFee = function (designThemeId) {
            if (this.$calculateFee != null) {
                this.$calculateFee.abort();
            }

            var that = this;

            if (this.settingLanguage != "vi") {
                var provinceId = 0;
                var districtId = 0;
            } else {
                var provinceId = that.otherAddress ? that.ShippingProvinceId : that.BillingProvinceId;
                var districtId = that.otherAddress ? that.ShippingDistrictId : that.BillingDistrictId;
            }
            var shippingMethod = $("[name='ShippingMethod']:checked").val();

            var email = $("#_email").val();

            var url = "/checkout/getshipping/" + that.token;
            if (!!designThemeId) {
                url += "?designThemeId=" + designThemeId;
            }

            data = {
                provinceId: provinceId,
                districtId: districtId,
                code: that.code,
                shippingMethod: shippingMethod,
                email: email
            };

            if (this.reduction_code) {
                data.code = null;
            }

            this.shippingMethodsLoading = true;
            Twine.refreshImmediately();
            this.$calculateFee = $.ajax({
                url: url,
                type: "POST",
                data: data,
                success: function (data) {
                    that.loadedShippingMethods = true;

                    if (data.error) {
                        that.shippingMethods = [];
                        Twine.refreshImmediately();
                    } else {
                        that.existCode = data.exist_code;

                        if (that.code && !that.existCode && !that.reduction_code) {
                            that.inValidCode = !that.existCode;
                            that.applyWithPromotion = true;

                        } else {
                            that.inValidCode = false;
                            that.applyWithPromotion = data.apply_with_promotion;
                        }

                        that.freeShipping = data.free_shipping;
                        that.discount = data.discount;
                        that.totalOrderItemPrice = data.total_line_item_price;
                        that.totalPrice = data.total_price;

                        if (that.requiresShipping)
                            that.shippingMethods = data.shipping_methods;

                        that.discountShipping = data.discount_shipping;

                        $(".shipping-method .content-box").empty();

                        for (var index in that.shippingMethods) {
                            var shippingMethod = that.shippingMethods[index];
                            var template = Juzaweb.Template.SHIPPING_METHOD.replace(/{{shipping_method_value}}/g, shippingMethod.value);
                            template = template.replace(/{{shipping_method_name}}/g, shippingMethod.name);
                            template = template.replace(/{{shipping_method_fee}}/g, shippingMethod.fee);
                            template = template.replace(/{{shipping_method_id}}/g, shippingMethod.id);
                            template = template.replace(/{{shipping_method_fee_text}}/g, (shippingMethod.fee != 0 ? shippingMethod.fee : that.discountLabel));
                            $(".shipping-method .content-box").append(template);
                        }

                        that.shippingMethodsLoading = false;

                        Twine.unbind($(".shipping-method .content-box").get(0));
                        Twine.bind($(".shipping-method .content-box").get(0));

                        Twine.refreshImmediately();

                        $("[name=ShippingMethod][value='" + data.shipping_method + "']").click();
                        that.applyShippingMethod();
                    }
                },
                error: function () {
                    that.shippingMethodsLoading = false;
                }
            });

            return false;
        };

        StoreCheckout.prototype.shippingCountryChange = function (designThemeId) {
            if (!this.ortherAddress) {
                var that = this;
                if (this.show_country) {

                    var url = "/checkout/getprovinces?countryId=" + that.ShippingCountryId;
                    if (!!designThemeId) {
                        url += "&designThemeId=" + designThemeId;
                    }

                    $.ajax({
                        url: url,
                        success: function (data) {
                            var html = "<option value=''>--- Choose a province ---</option>";

                            for (var i = 0; i < data.length; i++) {
                                var province = data[i];
                                var selected = (that.province === province.name) || (that.customerAddress != null && that.customerAddress.province === province.name);
                                html += "<option value='" + province.id + "'" + (selected ? "selected='selected'" : "") + ">" + province.name + "</option>";
                            }

                            $("select[name='ShippingProvinceId']").empty().html(html);
                            $("select[name='ShippingProvinceId']").trigger("change");
                        }
                    });
                }
            }
        };

        StoreCheckout.prototype.shippingProviceChange = function (designThemeId) {
            if (this.otherAddress) {
                var that = this;
                if (this.show_district) {
                    this.showShippingDistrict(designThemeId);
                } else {
                    this.caculateShippingFee(designThemeId);
                }
            } else {
                var initDistrict = $("select[name='ShippingDistrictId'] >option").length > 0 ? false : true;
                if (initDistrict) {
                    if (this.show_district) {
                        this.showShippingDistrict(designThemeId);
                    }
                }
            }
        };

        StoreCheckout.prototype.showShippingDistrict = function (designThemeId) {
            var that = this;
            var url = "/checkout/getdistricts?provinceId=" + that.ShippingProvinceId;
            if (!!designThemeId) {
                url += "&designThemeId=" + designThemeId;
            }
            $.ajax({
                url: url,
                async: false,
                success: function (data) {
                    var html = "<option value=''>--- Select district ---</option>";

                    for (var i = 0; i < data.length; i++) {
                        var district = data[i];
                        var selected = that.district === district.name;
                        html += "<option value='" + district.id + "'" + (selected ? "selected='selected'" : "") + ">" + district.name + "</option>"
                    }

                    $("select[name='ShippingDistrictId']").empty().html(html);
                    $("select[name='ShippingDistrictId']").trigger("change");
                }
            });
            this.abandonedCheckout();
        };

        StoreCheckout.prototype.showShippingWard = function (designThemeId) {
            if (this.ShippingDistrictId != null && this.ShippingDistrictId > 0) {
                var that = this;
                var url = "/checkout/getwards?districtId=" + that.ShippingDistrictId;
                if (!!designThemeId) {
                    url += "&designThemeId=" + designThemeId;
                }
                $.ajax({
                    url: url,
                    async: false,
                    success: function (data) {
                        var html = "<option value=''>--- Choose a ward ---</option>";

                        for (var i = 0; i < data.length; i++) {
                            var ward = data[i];
                            var selected = that.ward === ward.name;
                            html += "<option value='" + ward.id + "'" + (selected ? "selected='selected'" : "") + ">" + ward.name + "</option>"
                        }

                        $("select[name='ShippingWardId']").empty().html(html);
                        $("select[name='ShippingWardId']").trigger("change");
                    }
                });
            }
        };

        StoreCheckout.prototype.calculateFeeAndSave = function (designThemeId) {
            this.caculateShippingFee(designThemeId);
            this.abandonedCheckout();
        };

        StoreCheckout.prototype.shippingDistrictChange = function (designThemeId) {
            if (this.otherAddress) {
                this.caculateShippingFee(designThemeId);
                if (this.show_ward) {
                    this.showShippingWard(designThemeId);
                }

                this.abandonedCheckout();
            } else {
                var initWard = $("select[name='ShippingWardId'] >option").length <= 0;
                if (initWard) {
                    if (this.show_ward) {
                        this.showShippingWard(designThemeId);
                    }
                }
            }
        };

        StoreCheckout.prototype.billingDistrictChange = function (designThemeId) {
            if (!this.otherAddress) {
                this.caculateShippingFee(designThemeId);

                if (this.BillingDistrictId != null && this.BillingDistrictId > 0) {
                    var that = this;
                    if (this.show_ward) {
                        var url = "/checkout/getwards?districtId=" + that.BillingDistrictId;
                        if (!!designThemeId) {
                            url += "&designThemeId=" + designThemeId;
                        }
                        $.ajax({
                            url: url,
                            success: function (data) {
                                var html = "<option value=''>--- Choose a ward ---</option>";

                                for (var i = 0; i < data.length; i++) {
                                    var ward = data[i];
                                    var selected = (that.ward === ward.name) || (that.customerAddress != null && that.customerAddress.ward === ward.name);
                                    html += "<option value='" + ward.id + "'" + (selected ? "selected='selected'" : "") + ">" + ward.name + "</option>"
                                }

                                $("select[name='BillingWardId']").empty().html(html);
                                $("select[name='BillingWardId']").trigger("change");
                            }
                        });
                    }
                }

                this.abandonedCheckout();
            }
        };

        StoreCheckout.prototype.shippingWardChange = function () {
            if (this.otherAddress) {
                this.abandonedCheckout();
            }
        };

        StoreCheckout.prototype.billingWardChange = function () {
            if (!this.otherAddress) {
                this.abandonedCheckout();
            }
        };

        StoreCheckout.prototype.changeOtherAddress = function (element) {
            element.value = this.otherAddress;
            if (this.otherAddress) {
                $("#_shipping_address_last_name").prop('required', true);
                $("#_shipping_address_phone").prop('required', true);
                $("#_shipping_address_address1").prop('required', true);
                $("#shippingProvince").prop('required', true);
                $("#shippingDistrict").prop('required', true);
                $("#shippingWard").prop('required', true);
                $("select[name='ShippingProvinceId']").trigger("change");
            } else {
                if ($(".help-block.with-errors > ul").length > 0) {
                    $(".help-block.with-errors").empty();
                }
                $("#_shipping_address_last_name").removeAttr('required');
                $("#_shipping_address_phone").removeAttr('required');
                $("#_shipping_address_address1").removeAttr('required');
                $("#shippingProvince").removeAttr('required');
                $("#shippingDistrict").removeAttr('required');
                $("#shippingWard").removeAttr('required');
                $("select[name='BillingProvinceId']").trigger("change");
            }
            this.abandonedCheckout();
        };

        StoreCheckout.prototype.applyShippingMethod = function () {
            this.shippingMethod = $("[name='ShippingMethod']:checked").val();
            var shippingFee = parseFloat($("[name='ShippingMethod']:checked").attr("fee"));

            if (this.discountShipping) {
                if (shippingFee <= 0) {
                    this.freeShipping = true;
                    this.discount = shippingFee;
                } else {
                    this.freeShipping = false;
                    this.discount = 0;
                }
            } else {
                if (shippingFee <= 0) {
                    this.freeShipping = true;
                } else {
                    this.freeShipping = false;
                }
            }

            this.shippingFee = $("[name='ShippingMethod']:checked").attr("fee");
            Twine.refreshImmediately();
        };

        StoreCheckout.prototype.changeShippingMethod = function () {
            this.shippingMethod = $("[name='ShippingMethod']:checked").val();
            var shippingFee = parseFloat($("[name='ShippingMethod']:checked").attr("fee"));

            if (this.discountShipping) {
                if (shippingFee <= 0) {
                    this.freeShipping = true;
                    this.discount = shippingFee;
                } else {
                    this.freeShipping = false;
                    this.discount = 0;
                }
            } else {
                if (shippingFee <= 0) {
                    this.freeShipping = true;
                } else {
                    this.freeShipping = false;
                }
            }

            this.shippingFee = $("[name='ShippingMethod']:checked").attr("fee");

            Twine.refreshImmediately();
        };

        StoreCheckout.toggleOrderSummary = function (e) {
            var $toggle = $(e);
            var $container = $(".order-summary--product-list");

            $container.wrapInner("<div />");

            var i = $container.height();
            var r = $container.find("> div").height();
            var n = 0 === i ? r : 0;

            $container.css("height", i);
            $container.find("> div").contents().unwrap();

            setTimeout(function (i) {
                return function () {
                    $toggle.toggleClass("order-summary-toggle--hide");
                    $container.toggleClass("order-summary--is-collapsed");
                    $container.addClass("order-summary--transition");
                    $container.css("height", n);
                }
            }(this), 0);

            $container.one("webkitTransitionEnd oTransitionEnd otransitionend transitionend msTransitionEnd", function (t) {
                return function (t) {
                    if ($container.is(t.target)) {
                        $container.removeClass("order-summary--transition");
                        $container.removeAttr("style");
                    }
                }
            }(this))
        };

        StoreCheckout.prototype.removeCode = function (designThemeId) {
            this.code = "";
            this.caculateShippingFee(designThemeId);
        };

        StoreCheckout.prototype.paymentCheckout = function (googleApiKey) {
            $(".btn-checkout").button('loading');
            var that = this;
            var paymentMethod = $('input[name="method"]:checked').val();
            var $form = $("form.formCheckout");

            // Validate form first
            $form.validator('validate');
            if ($(".help-block.with-errors > ul").length > 0) {
                $(".btn-checkout").button('reset');
                return;
            }

            // Call createOrder API
            $.ajax({
                url: $form.attr('action'),
                type: 'POST',
                data: $form.serialize(),
                success: function (response) {
                    if (!response.success) {
                        that.showError(response.message || 'Failed to create order');
                        $(".btn-checkout").button('reset');
                        return;
                    }

                    // Store order info
                    that.currentOrder = response.data;

                    if (paymentMethod === 'Stripe') {
                        // Show Stripe modal for payment
                        that.showStripeModal(response.order_id);
                    } else {
                        // For other payment methods, proceed with payment immediately
                        that.processPaymentWithOrder(response.order_id);
                    }
                },
                error: function (xhr) {
                    var response = xhr.responseJSON || {};
                    that.showError(response.message || 'An error occurred');
                    $(".btn-checkout").button('reset');
                }
            });
        };

        StoreCheckout.prototype.showError = function (message) {
            var errorHtml = "<li>" + message + "</li>";
            $(".sidebar__content .has-error .help-block > ul").html(errorHtml);
        };

        StoreCheckout.prototype.processPaymentWithOrder = function (orderId) {
            var that = this;
            var paymentMethod = $('input[name="method"]:checked').val();

            // Custom payment method bypasses payment processing
            if (paymentMethod === 'Custom') {
                window.location.href = '/invoices/' + orderId;
                return;
            }

            // Submit payment for other non-Stripe methods
            $.ajax({
                url: Juzaweb.purchaseUrl,
                type: 'POST',
                data: {
                    order_id: orderId,
                    method: paymentMethod,
                    _token: $('meta[name="csrf-token"]').attr('content'),
                },
                success: function (response) {
                    if (response.success) {
                        // Check if response has embed_url for iframe display
                        if (response.embed_url) {
                            // Create and show modal with iframe
                            that.showPaymentIframe(response.embed_url, orderId);

                            that.checkStatus(response.payment_history_id, response.order_id);
                        } else {
                            // Standard redirect
                            var redirectUrl = response.redirect || '/invoices/' + orderId;
                            window.location.href = redirectUrl;
                        }
                    } else {
                        that.showError(response.message || 'Payment failed');
                        $(".btn-checkout").button('reset');
                    }
                },
                error: function (xhr) {
                    var response = xhr.responseJSON || {};
                    that.showError(response.message || 'Payment processing failed');
                    $(".btn-checkout").button('reset');
                }
            });
        };

        StoreCheckout.prototype.showStripeModal = function (orderId) {
            var that = this;
            this.currentOrderId = orderId;

            // Create Stripe form HTML
            var stripeFormHtml = `
                <div class="form-group">
                    <label for="cardholder-name">${code_langs.cardholder_name}</label>
                    <input id="cardholder-name" class="form-control" type="text" placeholder="John Doe" required>
                </div>

                <div class="form-group">
                    <label for="stripe-card-element">${code_langs.card_information}</label>
                    <div id="stripe-card-element" class="form-control">
                        <!-- A Stripe Element will be inserted here. -->
                    </div>
                </div>

                <div id="stripe-card-errors" role="alert" class="text-danger mt-2"></div>

                <div id="payment-message"></div>

                <div class="text-right mt-3">
                    <button type="button" class="btn btn-default" data-dismiss="modal">${code_langs.cancel}</button>
                    <button type="button" class="btn btn-primary btn-pay-now" id="btn-stripe-pay-now">
                        ${code_langs.pay_now}
                    </button>
                </div>
            `;

            // Insert form into modal
            $('#payment-container').html(stripeFormHtml);

            // Show modal
            $('#payment-modal').modal('show');

            // Initialize Stripe after modal is shown
            $('#payment-modal').on('shown.bs.modal', function () {
                that.initStripe();
                $(".btn-checkout").button('reset');
            });

            // Cleanup when modal is hidden (user cancels)
            $('#payment-modal').off('hidden.bs.modal').on('hidden.bs.modal', function () {
                // Only redirect if NOT already on order checkout page
                if (that.currentOrderId && !window.location.pathname.includes('/order/')) {
                    window.location.href = '/invoices/' + that.currentOrderId;
                }

                $('#payment-container').html('');
                if (that.stripeElement) {
                    that.stripeElement.unmount();
                    that.stripeElement = null;
                }
                $(this).off('shown.bs.modal');
            });

            // Handle Pay Now button click
            $(document).off('click', '#btn-stripe-pay-now').on('click', '#btn-stripe-pay-now', function () {
                var $btn = $(this);
                $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> ' + code_langs.processing);

                that.stripe.createPaymentMethod({
                    type: 'card',
                    card: that.stripeElement,
                    billing_details: {
                        name: $('#cardholder-name').val() || $('input[name="name"]').val(),
                        email: $('input[name="email"]').val(),
                        phone: $('input[name="phone"]').val(),
                    },
                }).then(function (result) {
                    if (result.error) {
                        var errorElement = document.getElementById('stripe-card-errors');
                        errorElement.textContent = result.error.message;
                        $btn.prop('disabled', false).html(code_langs.pay_now);
                    } else {
                        // Payment method created successfully
                        // Now submit payment with order_id
                        var paymentData = {
                            payment_method: result.paymentMethod.id,
                            order_id: that.currentOrderId,
                            method: 'Stripe',
                            _token: $('meta[name="csrf-token"]').attr('content'),
                        };

                        $.ajax({
                            url: Juzaweb.purchaseUrl,
                            type: 'POST',
                            data: paymentData,
                            success: function (response) {
                                if (response.success) {
                                    if (response.embed_url) {
                                        // Create and show modal with iframe
                                        that.showPaymentIframe(response.embed_url, that.currentOrderId);

                                        that.checkStatus(response.payment_history_id, response.order_id);
                                        return false;
                                    }

                                    $('#payment-modal').modal('hide');

                                    // Redirect will happen via returnCheckout or via response
                                    if (response.data && response.redirect) {
                                        window.location.href = response.redirect;
                                    } else {
                                        window.location.href = '/invoices/' + that.currentOrderId;
                                    }
                                } else {
                                    var errorElement = document.getElementById('stripe-card-errors');
                                    errorElement.textContent = response.message || 'Payment failed';
                                    $btn.prop('disabled', false).html(code_langs.pay_now);
                                }
                            },
                            error: function (xhr) {
                                var response = xhr.responseJSON || {};
                                var errorElement = document.getElementById('stripe-card-errors');
                                errorElement.textContent = response.message || 'Payment failed';
                                $btn.prop('disabled', false).html(code_langs.pay_now);
                            }
                        });
                    }
                });
            });
        };

        StoreCheckout.prototype.onPaymentMethodChange = function () {
            // No longer need to init Stripe inline
        };

        StoreCheckout.prototype.initStripe = function () {
            if (this.stripe) return;
            if (!this.stripePublishKey) return;

            this.stripe = Stripe(this.stripePublishKey);
            var elements = this.stripe.elements();
            var style = {
                base: {
                    color: '#32325d',
                    fontFamily: '"Helvetica Neue", Helvetica, sans-serif',
                    fontSmoothing: 'antialiased',
                    fontSize: '16px',
                    '::placeholder': {
                        color: '#aab7c4'
                    },
                    padding: '10px 12px'
                },
                invalid: {
                    color: '#fa755a',
                    iconColor: '#fa755a'
                }
            };

            this.stripeElement = elements.create('card', {
                style: style,
                hidePostalCode: true
            });
            this.stripeElement.mount('#stripe-card-element');

            this.stripeElement.on('change', function (event) {
                var displayError = document.getElementById('stripe-card-errors');
                if (event.error) {
                    displayError.textContent = event.error.message;
                } else {
                    displayError.textContent = '';
                }
            });
        };

        StoreCheckout.prototype.showPaymentIframe = function (embedUrl, orderId) {
            var that = this;

            // Update modal title
            $('#payment-modal .modal-title').html(code_langs.complete_payment || 'Complete Payment');

            // Create iframe HTML
            var iframeHtml = '<iframe id="payment-iframe" src="' + embedUrl + '" style="width: 100%; height: 450px; border: none;"></iframe>';

            // Insert iframe into modal container
            $('#payment-container').html(iframeHtml);

            // Show modal
            $('#payment-modal').modal('show');

            // Handle modal close - redirect to invoice
            // $('#payment-modal').off('hidden.bs.modal').on('hidden.bs.modal', function () {
            //     window.location.href = '/invoices/' + orderId;
            // });
        };

        StoreCheckout.prototype.loadScriptGoogleMapApi = function (url, callback) {
            jQuery.ajax({
                timeout: 3000,
                url: url,
                dataType: 'script',
                async: true,
                global: false,
                success: function () {
                    callback(true);
                },
                error: function () {
                    callback(false);
                },
            });
        };

        StoreCheckout.prototype.getBillingAddress = function () {
            var that = this;
            var address = "";

            if (that.billing_address.address1) {
                address += that.billing_address.address1 + ", ";
            }
            if (that.BillingWardId) {
                var wardName = $("#billingWard").find(":selected").text();
                if (wardName) {
                    address += wardName + ", ";
                }
            }
            if (that.BillingDistrictId) {
                var districtName = $("#billingDistrict").find(":selected").text();
                if (districtName) {
                    address += districtName + ", ";
                }
            }
            if (that.BillingProvinceId) {
                var provinceName = $("#billingProvince").find(":selected").text();
                if (provinceName) {
                    address += provinceName;
                }
            }

            if (that.BillingCountryId) {
                var countryName = $("#billingCountry").find(":selected").text();
                if (countryName) {
                    address += ", " + countryName;
                }
            }

            return address;
        };

        StoreCheckout.prototype.getShippingAddress = function () {
            var that = this;
            var address = "";
            if (that.shipping_address.address1) {
                address += that.shipping_address.address1 + ", ";
            }
            if (that.ShippingWardId) {
                var wardName = $("#shippingWard").find(":selected").text();
                if (wardName) {
                    address += wardName + ", ";
                }
            }
            if (that.ShippingDistrictId) {
                var districtName = $("#shippingDistrict").find(":selected").text();
                if (districtName) {
                    address += districtName + ", ";
                }
            }
            if (that.ShippingProvinceId) {
                var provinceName = $("#shippingProvince").find(":selected").text();
                if (provinceName) {
                    address += provinceName;
                }
            }

            if (that.ShippingCountryId) {
                var countryName = $("#shippingCountry").find(":selected").text();
                if (countryName) {
                    address += ", " + countryName;
                }
            }

            return address;
        };

        StoreCheckout.prototype.toggle = function (e, container) {
            var $toggle = $(e);
            var $container = $(container);

            $container.wrapInner("<div />");

            var i = $container.height();
            var r = $container.find("> div").height();
            var n = 0 === i ? r : 0;

            $container.css("height", i);
            $container.find("> div").contents().unwrap();

            setTimeout(function (i) {
                return function () {
                    $toggle.toggleClass("open");
                    $container.toggleClass("mobile--is-expanded mobile--is-collapsed");
                    $container.addClass("mobile--transition");
                    $container.css("height", n);
                }
            }(this), 0);

            $container.one("webkitTransitionEnd oTransitionEnd otransitionend transitionend msTransitionEnd", function (t) {
                return function (t) {
                    if ($container.is(t.target)) {
                        $container.removeClass("mobile--transition");
                        $container.removeAttr("style");
                    }
                }
            }(this))
        };

        StoreCheckout.prototype.getLatLong = function (address, callback) {
            // If adress is not supplied, use default value 'Ferrol, Galicia, Spain'
            address = address || '';
            // Initialize the Geocoder
            if (typeof google !== 'undefined') {
                geocoder = new google.maps.Geocoder();
                if (geocoder && geocoder.geocode) {
                    geocoder.geocode({
                        'address': address
                    }, function (results, status) {
                        if (status == google.maps.GeocoderStatus.OK) {
                            callback(results[0]);
                        }
                        else {
                            callback(false);
                        }
                    });
                }
                else {
                    callback(false);
                }
            } else {
                callback(false);
            }
        };

        StoreCheckout.prototype.setBillingLatLng = function (result) {
            if (result == false) {
                this.billingLatLng.Lat = "";
                this.billingLatLng.Lng = "";
            }
            else {
                this.billingLatLng.Lat = result.geometry.location.lat();
                this.billingLatLng.Lng = result.geometry.location.lng();
            }
        };

        function handleCustomPaymentMessage(content) {
            $("#qr-error-modal .invalid_order").hide();
            $("#qr-error-modal .custom_error_message").html(content)
            $('#qr-error-modal').one('hidden.bs.modal', function () {
                $("#qr-error-modal .invalid_order").show();
                $("#qr-error-modal .custom_error_message").html("");
            });
            $(".trigger-qr-error-modal").trigger("click");
        };

        StoreCheckout.prototype.returnCheckout = function () {
            var that = this;
            var $form = $("form.formCheckout");

            $form.validator('validate');
            if ($(".help-block.with-errors > ul").length <= 0) {
                let url = $form.attr('action');
                let method = "POST";

                $.ajax({
                    url: url,
                    type: method,
                    global: true,
                    data: $form.serialize()
                }).done(function (response) {

                    if (response.type === 'redirect') {
                        Juzaweb.Utility.redirect(response.redirect);
                        return false;
                    }

                    if (response.type === 'embed') {
                        let htm = `<iframe src="${response.embed_url}" width="100%" height="350px" frameborder="0"></iframe>`;
                        if ($('#payment-container').length) {
                            $('#payment-container').html(htm);
                        } else {
                            form.html(htm);
                        }

                        $('#payment-modal').modal();

                        that.checkStatus(response.payment_history_id, response.order_id);

                        return false;
                    }

                    let errorHtml = "";
                    if (response.errors != null && response.errors.length > 0) {
                        for (i = 0; i < response.errors.length; i++) {
                            errorHtml += "<li>" + response.errors[i] + "</li>";
                        }
                    } else {
                        errorHtml += "<li>" + response.message + "</li>";
                    }

                    $(".sidebar__content .has-error .help-block > ul").html(errorHtml);

                    $(".btn-checkout").button('reset');

                    return false;
                }).fail(function (data) {
                    let response = data.responseJSON;
                    let errorHtml = "";

                    if (response.errors != null && response.errors.length > 0) {
                        for (i = 0; i < response.errors.length; i++) {
                            errorHtml += "<li>" + response.errors[i] + "</li>";
                        }
                    } else {
                        errorHtml += "<li>" + response.message + "</li>";
                    }

                    $(".sidebar__content .has-error .help-block > ul").html(errorHtml);

                    $(".btn-checkout").button('reset');

                    return false;
                });
            } else {
                $(".btn-checkout").button('reset');
            }
        };

        StoreCheckout.prototype.abandonedCheckout = function () {
            var $form = $("form.formCheckout");
            var url = window.location.href;
            var method = "POST";
            if (this.$ajax != null) {
                this.$ajax.abort();
            }

            if (this.ajaxAbandonedTimeout != null) {
                clearTimeout(this.ajaxAbandonedTimeout);
            }
            var $that = this;
            this.ajaxAbandonedTimeout = setTimeout(function () {
                $that.$ajax = $.ajax({
                    url: url,
                    type: method,
                    global: false,
                    data: $form.serialize() + "&_method=patch",
                    success: function (data) {
                    }
                });
            }, 3000);
        };

        StoreCheckout.prototype.checkStatus = function (paymentHistoryId, orderId) {
            let interval = setInterval(function () {
                $.ajax({
                    type: 'GET',
                    url: `/payment/ecommerce/status/${paymentHistoryId}`,
                    dataType: 'json',
                    success: function (response) {
                        let status = response.status;

                        if (status === 'pending' || status === 'processing') {
                            return;
                        }

                        // if (self.options.onSuccess && status === 'success') {
                        //     self.options.onSuccess(response);
                        // }
                        //
                        // if (status === 'failed' && self.options.onError) {
                        //     self.options.onError(response);
                        // }

                        window.location.href = `/invoices/${orderId}`;

                        clearInterval(interval);
                    }.bind(this),
                    error: function (jqxhr) {
                        // if (self.options.onError) {
                        //     self.options.onError(jqxhr);
                        // }

                        window.location.href = `/invoices/${orderId}`;

                        clearInterval(interval);
                    }.bind(this)
                });
            }, 2000);
        }

        return StoreCheckout;
    }();
    Juzaweb.CheckoutProblems = function () {
        function CheckoutProblems(e, options) {
            if (!options)
                options = {};

            this.token = options.token;
        }

        CheckoutProblems.prototype.removeItem = function (e) {
            var $form = $(e).parent('form.edit_checkout');
            var $that = this;
            $.ajax({
                url: '/checkout/' + $that.token,
                type: 'POST',
                data: $form.serialize(),
                success: function (data) {
                    window.location = "/checkout/" + data.token;
                }
            })
        };

        CheckoutProblems.prototype.continueCheckout = function () {
            var $form = $("#form_stock_problems_to_checkout");
            var $that = this;
            $.ajax({
                url: '/checkout/' + $that.token,
                type: 'POST',
                async: false,
                data: $form.serialize(),
                success: function (data) {
                    window.location = "/checkout/" + data.token;
                }
            })
        };

        return CheckoutProblems;
    }();

    Juzaweb.PaymentStatus = function () {
        function applySelf(func, that) {
            return function () {
                return func.apply(that, arguments);
            }
        }

        function PaymentStatus(element, options) {
            if (options != null) {
                for (var option in options) {
                    this[option] = options[option];
                }
            }

            this.element = element;
            this.countdown = applySelf(this.countdown, this);
            this.pollingResult = applySelf(this.pollingResult, this);

            if (this.payment_processing === 'true') {
                if (this.payment_provider_id == "17") {
                    this.zaloData = this.payment_info;
                    this.remaining = parseInt(this.zaloData.remaining_duration);
                    var requestSent = 30 - Math.ceil(this.remaining / 10);

                    this.startPollingPaymentStatus(requestSent);
                }
            }
        }

        PaymentStatus.prototype.startPaymentTimer = function () {
            this.qrTimer = $(".qr-timer");
            this.timerInterval = 1000;
            this.expected = Date.now() + this.timerInterval;
            this.currentTimeout = setTimeout(this.countdown, this.timerInterval);
        };

        PaymentStatus.prototype.stopPaymentTimer = function () {
            clearTimeout(this.currentTimeout);
        };

        PaymentStatus.prototype.countdown = function () {
            var driftedOffset = 0;
            if (this.remaining > 0) {
                var drift = Date.now() - this.expected;
                if (drift > this.timerInterval) {
                    var passedTime = drift / this.timerInterval;
                    driftedOffset = Math.floor(passedTime - 1)
                    this.remaining -= driftedOffset;
                }
                --this.remaining;
                this.qrTimer.text(this.remaining);

                this.expected += (driftedOffset * this.timerInterval) + this.timerInterval;
                this.currentTimeout = setTimeout(this.countdown, Math.max(0, (driftedOffset * this.timerInterval) + this.timerInterval - drift));
            } else {
                this.stopPaymentTimer();
                var order_id = this.zaloData.order_id;
                setTimeout(function () {
                    Juzaweb.Utility.redirect("/checkout/failure/" + order_id);
                }, 1000);
            }
        };

        PaymentStatus.prototype.startPollingPaymentStatus = function (defaultCount) {
            if (defaultCount == null) {
                defaultCount = 0;
            }
            this.pollingCount = defaultCount;
            this.currentInterval = setInterval(this.pollingResult, 10000);
        };

        PaymentStatus.prototype.pollingResult = function () {
            if (!this.zaloData || !this.zaloData.app_trans_id) {
                this.stopPollingPaymentStatus();
                return false;
            }

            if (this.pollingRequestSending && this.currentPollingRequest !== null && this.currentPollingRequest !== undefined) {
                this.currentPollingRequest.abort();
            }

            this.pollingRequestSending = true;
            this.pollingCount += 1;
            var caller = this;
            this.currentPollingRequest = $.ajax({
                url: "/zalopay/checkpaymentstatus",
                method: "POST",
                contentType: "application/json; charset=utf-8",
                timeout: 10000,
                global: false,
                data: JSON.stringify({ app_trans_id: this.zaloData.app_trans_id }),
                success: function (data) {
                    if (1 == data.return_code) {
                        Juzaweb.Utility.redirect("/checkout/thankyou/" + caller.zaloData.order_token)
                    } else {
                        if (-117 != data.return_code && -49 != data.return_code && data.return_code <= 0) {
                            setTimeout(function () { Juzaweb.Utility.redirect("/checkout/failure/" + caller.zaloData.order_id); }, 1000);
                        }
                    }
                },
                error: function () {

                },
                complete: function (result, status) {
                    caller.pollingRequestSending = false;
                    if ("abort" == status) {
                        console.log("abort request")
                    }
                    if (caller.remaining <= 0 || caller.pollingCount >= 30) {
                        caller.stopPollingPaymentStatus();
                        setTimeout(function () { Juzaweb.Utility.redirect("/checkout/cancelled/" + caller.zaloData.order_id); }, 500);
                    }
                }
            });
        };

        PaymentStatus.prototype.stopPollingPaymentStatus = function () {
            clearInterval(this.currentInterval);
        };

        return PaymentStatus;
    }();
}).call(this);
