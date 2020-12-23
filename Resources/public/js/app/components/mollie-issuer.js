define(function(require) {
    'use strict';

    var _ = require('underscore');
    var BaseComponent = require('oroui/js/app/components/base/component');

    const mediator = require('oroui/js/mediator');

    const MollieIssuerComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            paymentMethod: null,
            selectors: {
                form: 'oro_workflow_transition',
                issuerList: 'input[type="radio"]:checked',
            }
        },

        /**
         * @property {jQuery}
         */
        $el: null,

        /**
         * {@inheritdoc}
         */
        constructor: function MollieIssuerComponent() {
            MollieIssuerComponent.__super__.constructor.apply(this, arguments);
        },

        /**
         * {@inheritdoc}
         */
        initialize: function(options) {
            this.options = _.extend({}, this.options, options);
            this.$el = this.options._sourceElement;

            mediator.on('checkout:payment:before-transit', this.beforeTransit, this);
        },

        getSelectedIssuer: function () {
            let issuerListContainer = document.querySelector('#' + this.options.paymentMethod + '-issuer-list');

            if (!issuerListContainer) {
                return null;
            }

            let select = issuerListContainer.querySelector('select');
            if (select) {
                return select.options[select.selectedIndex].value;
            }

            return issuerListContainer.querySelector(this.options.selectors.issuerList).value;
        },

        beforeTransit: function(eventData) {
            if (eventData.data.paymentMethod === this.options.paymentMethod) {
                window.localStorage.setItem('mollieIssuer', this.getSelectedIssuer());
            }
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            delete this.$el;

            MollieIssuerComponent.__super__.dispose.call(this);
        }
    });

    return MollieIssuerComponent;
});
