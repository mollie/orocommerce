define(function(require) {
    'use strict';

    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var $ = require('jquery');
    var BaseComponent = require('oroui/js/app/components/base/component');

    var Mollie = require('https://js.mollie.com/v1/mollie.js');

    const MollieCreditCardComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            profileId: null,
            testMode: true,
            paymentMethod: null,
            cardHolderSelector: null,
            cardHolderErrorSelector: null,
            cardNumberSelector: null,
            cardNumberErrorSelector: null,
            expiryDateSelector: null,
            expiryDateErrorSelector: null,
            verificationCodeSelector: null,
            verificationCodeErrorSelector: null,

            formSelector: 'oro_workflow_transition'
        },

        /**
         * @property {jQuery}
         */
        $el: null,

        $mollieTools: null,
        $cardHolder: null,
        $cardNumber: null,
        expiryDate: null,
        verificationCode: null,

        /**
         * {@inheritdoc}
         */
        constructor: function MollieCreditCardComponent() {
            MollieCreditCardComponent.__super__.constructor.apply(this, arguments);
        },

        /**
         * {@inheritdoc}
         */
        initialize: function(options) {
            this.options = _.extend({}, this.options, options);
            this.$el = this.options._sourceElement;

            this.options.cardHolderSelector = '#' + this.options.paymentMethod + '-card-holder';
            this.options.cardHolderErrorSelector = '#' + this.options.paymentMethod + '-card-holder-error';
            this.options.cardNumberSelector = '#' + this.options.paymentMethod + '-card-number';
            this.options.cardNumberErrorSelector = '#' + this.options.paymentMethod + '-card-number-error';
            this.options.expiryDateSelector = '#' +  this.options.paymentMethod + '-expiry-date';
            this.options.expiryDateErrorSelector = '#' +  this.options.paymentMethod + '-expiry-date-error';
            this.options.verificationCodeSelector = '#' +  this.options.paymentMethod + '-verification-code';
            this.options.verificationCodeErrorSelector =  '#' + this.options.paymentMethod + '-verification-code-error';

            mediator.on('checkout:payment:before-transit', this.beforeTransit, this);
            mediator.on('checkout-content:initialized', this.contentLoaded, this);
            mediator.on('checkout:payment:method:changed', this.onPaymentMethodChanged, this);

        },

        /**
         * @param {Object} eventData
         */
        onPaymentMethodChanged: function(eventData) {
            if (eventData.paymentMethod === this.options.paymentMethod) {
                this.unmount();
                this.mountComponents();
            }
        },

        unmount: function() {
            try {
                let mollieComponents = document.querySelectorAll('.mollie-component');
                for (let i = 0; i < mollieComponents.length; i++) {
                    mollieComponents[i].remove();
                }

                let mollieControllers = document.querySelectorAll('.mollie-components-controller');
                for (let i = 0; i < mollieControllers.length; i++) {
                    mollieControllers[i].remove();
                }

            } catch (e) {

            }
        },

        contentLoaded: function () {
            let mollieComponents = document.querySelectorAll('.mollie-component');
            if (mollieComponents.length === 0) {
                this.mountComponents();
            }
        },

        mountComponents: function () {
            let locale  = document.querySelector('html').getAttribute('lang');
            this.$mollieTools = Mollie(
                this.options.profileId,
                {
                    locale: locale + '_' + locale.toUpperCase(),
                    testmode: this.options.testMode
                }
            );


            $('#' + this.options.paymentMethod + '-mollie-card-token').val('');
            /**
             * Create card holder input
             */
            this.$cardHolder = this.createMollieComponent(
                'cardHolder',
                this.options.cardHolderSelector,
                this.options.cardHolderErrorSelector
            );

            this.$cardNumber = this.createMollieComponent(
                'cardNumber',
                this.options.cardNumberSelector,
                this.options.cardNumberErrorSelector
            )

            this.$expiryDate = this.createMollieComponent(
                'expiryDate',
                this.options.expiryDateSelector,
                this.options.expiryDateErrorSelector
            )

            this.$verificationCode = this.createMollieComponent(
                'verificationCode',
                this.options.verificationCodeSelector,
                this.options.verificationCodeErrorSelector
            )

            this.addListeners(this.$cardHolder, this.options.cardHolderSelector);
            this.addListeners(this.$cardNumber, this.options.cardNumberSelector);
            this.addListeners(this.$expiryDate, this.options.expiryDateSelector);
            this.addListeners(this.$verificationCode, this.options.verificationCodeSelector);

            this.addFormListener();
        },

        addFormListener: function () {
            var form = document.forms[this.options.formSelector];
            var me = this;

            // single page checkout
            form.addEventListener('click', event => {
                var target = $(event.target);

                if (isSubmitButton(target) && isCreditCard()) {
                    let selector = '#' + me.options.paymentMethod + '-mollie-card-token';
                    var input = $(selector);
                    if (!input.val()) {
                        event.preventDefault();
                        event.stopPropagation();
                        me.$mollieTools.createToken().then((token) => {
                            if (token.token) {
                                input.val(token.token);
                                window.localStorage.setItem('mollieToken', token.token);
                                target.click();
                            }
                        });
                    }
                }
            });

            function isSubmitButton(target) {
                return target.is( "button" ) && target.attr('type') === 'submit';
            }

            function isCreditCard() {
                let selectedMethod = document.querySelector('input[name="paymentMethod"]:checked');
                if (selectedMethod) {
                    return selectedMethod.value === me.options.paymentMethod;
                }

                return false;
            }

        },

        createMollieComponent: function (type, baseSelector, errorSelector) {
            let mollieComponent = this.$mollieTools.createComponent(type);
            mollieComponent.mount(baseSelector);

            let componentError = document.querySelector(errorSelector);

            mollieComponent.addEventListener("change", event => {
                if (event.error && event.touched) {
                    componentError.textContent = event.error;
                } else {
                    componentError.textContent = "";
                }
            });

            return mollieComponent;
        },

        beforeTransit: function () {

            this.unmount();

        },

        addListeners: function (element, selector) {
            var me = this;
            element.addEventListener("change", event => me.toggleFieldDirtyClass(selector, event.dirty));
            element.addEventListener("focus", () => me.toggleFieldFocusClass(selector, true));
            element.addEventListener("blur", () => me.toggleFieldFocusClass(selector, false));
        },

        /**
         * For the floating labels to work we need some extra event listeners
         * to set proper classes on the form-group elements: `has-focus` and `is-dirty`
         */

        toggleFieldDirtyClass: function (fieldName, dirty) {
            const element = document.querySelector(fieldName);
            element.parentNode.classList.toggle('is-dirty', dirty);
        },

        toggleFieldFocusClass: function(fieldName, hasFocus) {
            const element = document.querySelector(fieldName);
            element.parentNode.classList.toggle('has-focus', hasFocus);
        },

        dispose: function () {
            if (this.disposed) {
                return;
            }

            delete this.$el;

            MollieCreditCardComponent.__super__.dispose.call(this);
        }
    });

    return MollieCreditCardComponent;
});
