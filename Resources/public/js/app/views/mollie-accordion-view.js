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
        },

        addListeners: function () {
            $(this.apiMethodChooserSelector).change(this.handleApiMethodChange.bind(this));
        },

        handleApiMethodChange: function (event) {
            let target = $(event.target);

            this.displayFieldsBasedOnMethod(target.val(), target.attr('data-method-wrapper'));

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