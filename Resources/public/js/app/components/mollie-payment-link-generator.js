define(function(require) {
    'use strict';

    var MolliePaymentLinkGeneratorComponent;
    var BaseComponent = require('oroui/js/app/components/base/component');
    var MultiSelectFilter = require('oro/filter/multiselect-filter');
    var $ = require('jquery');
    var _ = require('underscore');

    MolliePaymentLinkGeneratorComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            paymentFilterLabel: 'Payment methods',
            baseSelector: null,
            copyButtonSelector: null,
            closeButtonSelector: null,
            isMolliePaymentOnOrder: false,
            isPaymentsApiOnly: false,
            paymentMethods: {},
            paymentMethodsSelector: ''
        },

        /**
         * @property {$}
         */
        $el: null,

        /**
         * {@inheritdoc}
         */
        constructor: function MollieFormRefresherComponent() {
            MollieFormRefresherComponent.__super__.constructor.apply(this, arguments);
        },

        /**
         * {@inheritdoc}
         */
        initialize: function(options) {
            this.options = _.extend({}, this.options, options);
            if (_.isString(this.options.paymentMethods)) {
                this.options.paymentMethods = $.parseJSON(this.options.paymentMethods);
            }

            let popupWindow = document.querySelector(this.options.baseSelector);
            if (popupWindow) {
                let copyButton = popupWindow.querySelector(this.options.copyButtonSelector);
                let closeButton = popupWindow.querySelector(this.options.closeButtonSelector);
                if (copyButton && closeButton) {
                    this.attachEventListener(copyButton, closeButton);
                }
            }

            if (!this.options.isMolliePaymentOnOrder) {
                this.initPaymentFilter();
            }

        },

        initPaymentFilter: function() {
            let me = this,
                choices = _.values(_.mapObject(this.options.paymentMethods, function(label, key) {
                    return {value: key, label: label}
                })),
                paymentMethodsEl = $(this.options.paymentMethodsSelector),
                selectedPaymentMethods = paymentMethodsEl ? paymentMethodsEl.val().split(',') : [];

            this.paymentFilter = new MultiSelectFilter({
                label: this.options.paymentFilterLabel,
                name: 'oro_action_operation[molliePaymentLink][paymentMethods]',
                widgetOptions: {
                    classes: 'mollie-payment-link-multiselect-filter-widget select-filter-widget multiselect-filter-widget',
                    appendTo: function () {
                        return $(me.options.baseSelector);
                    }
                },
                choices: choices
            });

            this.paymentFilter.render();
            this.paymentFilter.on('update', this.onPaymentFilterStateChange, this);
            $(this.options.baseSelector).find('fieldset .filter-box').append(this.paymentFilter.$el);
            this.paymentFilter.rendered();

            if (selectedPaymentMethods.length) {
                this.paymentFilter.setValue({value: selectedPaymentMethods});
            }
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            if (this.paymentFilter) {
                this.paymentFilter.dispose();
            }

            delete this.$el;
            delete this.paymentFilter;

            MolliePaymentLinkGeneratorComponent.__super__.dispose.call(this);
        },

        attachEventListener: function(copyButton, closeButton) {
            let me = this;
            copyButton.addEventListener('click', function (event) {
                // Just close dialog if there is no need for payment method selection, submit otherwise
                if (me.options.isMolliePaymentOnOrder) {
                    event.preventDefault();
                    closeButton.click();
                }
            })
        },

        onPaymentFilterStateChange: function () {
            $(this.options.paymentMethodsSelector).val(this.paymentFilter.getValue().value.join(','));
        }


    });

    return MolliePaymentLinkGeneratorComponent;
});
