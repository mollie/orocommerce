define(function (require) {
    'use strict';

    const BaseView = require('oroui/js/app/views/base/view');
    const $ = require('jquery');

    const accordionView = BaseView.extend({
        autoRender: true,

        events: {
            'click .mollie-payment-method-nav': 'onHeaderClick',
            'click .mollie-remove-image': 'onRemoveImageClick',
        },

        apiMethodChooserSelector: 'select.mollie-method-select',
        singleClickPaymentCheckbox: 'input.mollie-payment-single-click-status',
        surchargeTypeChooserSelector: 'select.mollie-surcharge-type-select',
        surchargeType: {
            NO_FEE: 'no_fee',
            FIXED_FEE: 'fixed_fee',
            PERCENTAGE: 'percentage',
            FIXED_FEE_AND_PERCENTAGE: 'fixed_fee_and_percentage'
        },

        /**
         * Renders a tabs view
         */
        initialize: function (options) {
            this.setInitialFields();
            this.addListeners();

            return accordionView.__super__.initialize.call(this, options);
        },

        setInitialFields: function () {
            var self = this;
            $(this.apiMethodChooserSelector).each(function () {
                self.displayFieldsBasedOnMethod($(this).val(),$(this).attr('data-method-wrapper'));
            });
            $(this.surchargeTypeChooserSelector).each(function () {
                self.displayFieldsBasedOnSyrchargeType($(this).val(), $(this).attr('data-method-wrapper'));
            });
            $(this.singleClickPaymentCheckbox).each(function () {
                self.displayFieldsBasedOnSinglePaymentStatus($(this)[0].checked);
            });
        },

        addListeners: function () {
            $(this.apiMethodChooserSelector).change(this.handleApiMethodChange.bind(this));
            $(this.singleClickPaymentCheckbox).change(this.handleSingleClickStatusChange.bind(this));
            $(this.surchargeTypeChooserSelector).change(this.handleSyrchargeTypeChange.bind(this));
        },

        handleApiMethodChange: function (event) {
            let target = $(event.target);

            this.displayFieldsBasedOnMethod(target.val(), target.attr('data-method-wrapper'));

        },

        handleSyrchargeTypeChange: function (event) {
            let target = $(event.target);

            this.displayFieldsBasedOnSyrchargeType(target.val(), target.attr('data-method-wrapper'));
        },

        displayFieldsBasedOnMethod: function (apiMethod, identifier) {
            let wrapper = $('.mollie-payment-method[data-payment-method-id="'+ identifier +'"]');
            if (wrapper.length === 0) {
                return;
            }

            if (apiMethod === 'payment_api') {
                wrapper.find('.mollie-transaction-description, .mollie-payment-expiry-days').removeClass('mollie-hide-row');
                wrapper.find('.mollie-order-expiry-days').addClass('mollie-hide-row');
            } else {
                wrapper.find('.mollie-transaction-description, .mollie-payment-expiry-days').addClass('mollie-hide-row');
                wrapper.find('.mollie-order-expiry-days').removeClass('mollie-hide-row');
            }
        },

        displayFieldsBasedOnSyrchargeType: function (surchargeType, identifier) {
            let wrapper = $('.mollie-payment-method[data-payment-method-id="' + identifier + '"]');
            if (wrapper.length === 0) {
                return;
            }

            switch (surchargeType) {
                case this.surchargeType.NO_FEE:
                    wrapper.find('.mollie-surcharge-fixed-amount, .mollie-surcharge-percentage, .mollie-surcharge-limit').addClass('mollie-hide-row');
                    break;
                case this.surchargeType.FIXED_FEE:
                    wrapper.find('.mollie-surcharge-fixed-amount').removeClass('mollie-hide-row');
                    wrapper.find('.mollie-surcharge-percentage, .mollie-surcharge-limit').addClass('mollie-hide-row');
                    break;
                case this.surchargeType.PERCENTAGE:
                    wrapper.find('.mollie-surcharge-fixed-amount').addClass('mollie-hide-row');
                    wrapper.find('.mollie-surcharge-percentage, .mollie-surcharge-limit').removeClass('mollie-hide-row');
                    break;
                case this.surchargeType.FIXED_FEE_AND_PERCENTAGE:
                    wrapper.find('.mollie-surcharge-fixed-amount, .mollie-surcharge-percentage, .mollie-surcharge-limit').removeClass('mollie-hide-row');
                    break;
            }
        },

        handleSingleClickStatusChange: function (event) {
            let target = $(event.target);

            this.displayFieldsBasedOnSinglePaymentStatus(target[0].checked);

        },

        displayFieldsBasedOnSinglePaymentStatus: function (checked) {
            let wrapper = $('.mollie-payment-method[data-payment-method-id="creditcard"]');
            if (wrapper.length === 0) {
                return;
            }

            let fields = wrapper.find('.mollie-payment-single-click-approval-text, .mollie-payment-single-click-description');

            if (fields.length > 0 && checked) {
                fields.removeClass('mollie-hide-row');
            } else {
                fields.addClass('mollie-hide-row');
            }
        },

        /**
         * Disposes the view
         */
        dispose: function () {
            if (this.disposed) {
                // the view is already removed
                return;
            }

            accordionView.__super__.dispose.call(this);
        },

        onHeaderClick: function (e) {
            var $target = e.currentTarget,
                $activeIndicator = $target.querySelector('.mollie-toggle i'),
                $paymentMethodId = $target.getAttribute('data-payment-method-id'),
                $accordionContent = this.el.
                    querySelector('.mollie-payment-method[data-payment-method-id="'+$paymentMethodId+'"]');

            if ($activeIndicator) {
                $activeIndicator.classList.toggle("fa-chevron-down");
                $activeIndicator.classList.toggle("fa-chevron-up");
            }

            if ($accordionContent) {
                $accordionContent.classList.toggle("hide");
            }
        },

        onRemoveImageClick: function (e) {
            var $target = e.currentTarget,
                $imageId = $target.getAttribute('data-payment-image-id'),
                $imagePathEl = this.el.querySelector('#'+$imageId),
                $imageContainerEl = this.el.
                    querySelector('.mollie-payment-image-container[data-payment-image-id="'+$imageId+'"]');

            if ($imagePathEl) {
                $imagePathEl.value = '';
            }
            if ($imageContainerEl) {
                $imageContainerEl.classList.add('hide');
            }
        }
    });

    return accordionView;
});