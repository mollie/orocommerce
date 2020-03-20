define(function(require) {
    'use strict';

    const _ = require('underscore');
    const BaseComponent = require('oroui/js/app/components/base/component');

    const MollieApplepayComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            selectors: {
                parentContainer: '[data-item-container]',
                applePay: '[data-mollie-applepay]',
                form: '[data-content="payment_method_form"]',
            }
        },

        /**
         * @property {jQuery}
         */
        $el: null,

        /**
         * @inheritDoc
         */
        constructor: function MollieApplepayComponent() {
            MollieApplepayComponent.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.extend({}, this.options, options);
            this.$el = this.options._sourceElement;
            this.showAvailable();
        },

        showAvailable: function() {
            var form = this.$el
                .find(this.options.selectors.form);

            if (!window.ApplePaySession || !ApplePaySession.canMakePayments()) {
                form
                    .find(this.options.selectors.parentContainer)
                    .not(form.find(this.options.selectors.parentContainer).has(this.options.selectors.applePay))
                    .show();
                return;
            }

            form
                .find(this.options.selectors.parentContainer)
                .show();
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            delete this.$el;

            MollieApplepayComponent.__super__.dispose.call(this);
        }
    });

    return MollieApplepayComponent;
});
