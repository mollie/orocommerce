define(function(require) {
    'use strict';

    const _ = require('underscore');
    const BaseComponent = require('oroui/js/app/components/base/component');

    const MollieOrderSubmitComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            paymentMethod: null,
            selectors: {
                form: 'oro_workflow_transition',
            }
        },

        /**
         * @property {jQuery}
         */
        $el: null,

        /**
         * {@inheritdoc}
         */
        constructor: function MollieOrderSubmitComponent() {
            MollieOrderSubmitComponent.__super__.constructor.apply(this, arguments);
        },

        /**
         * {@inheritdoc}
         */
        initialize: function(options) {
            this.options = _.extend({}, this.options, options);
            this.$el = this.options._sourceElement;

            let issuer = window.localStorage.getItem('mollieIssuer');
            let form = document.forms[this.options.selectors.form];

            if (issuer) {
                this.addToForm('mollie-issuer', window.localStorage.getItem('mollieIssuer'), form);
                window.localStorage.removeItem('mollieIssuer');
            }

            let cardToken = window.localStorage.getItem('mollieToken');
            if (cardToken) {
                this.addToForm('mollie-card-token', window.localStorage.getItem('mollieToken'), form);
                window.localStorage.removeItem('mollieToken');
            }

            let saveSingleClickCreditCardPaymentValue = window.localStorage.getItem('saveSingleClickCreditCardPayment');
            if (saveSingleClickCreditCardPaymentValue) {
                this.addToForm('mollie-save-single-click-payment', window.localStorage.getItem('saveSingleClickCreditCardPayment'), form);
                window.localStorage.removeItem('saveSingleClickCreditCardPayment');
            }

            let useSavedSingleClickCreditCardPaymentValue = window.localStorage.getItem('useSavedSingleClickCreditCardPayment');
            if (useSavedSingleClickCreditCardPaymentValue) {
                this.addToForm('mollie-use-saved-single-click-payment', window.localStorage.getItem('useSavedSingleClickCreditCardPayment'), form);
                window.localStorage.removeItem('useSavedSingleClickCreditCardPayment');
            }
        },

        addToForm: function (name, value, form) {
            let input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'oro_workflow_transition[' + this.options.paymentMethod + '-' + name + ']';
            input.value = value;

            form.appendChild(input);
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            delete this.$el;

            MollieOrderSubmitComponent.__super__.dispose.call(this);
        }


    });

    return MollieOrderSubmitComponent;
});
