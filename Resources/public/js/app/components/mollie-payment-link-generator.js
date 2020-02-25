define(function(require) {
    'use strict';

    var MolliePaymentLinkGeneratorComponent;
    var BaseComponent = require('oroui/js/app/components/base/component');
    var $ = require('jquery');
    var _ = require('underscore');

    MolliePaymentLinkGeneratorComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            baseSelector: null,
            copyButtonSelector: null,
            closeButtonSelector: null
        },

        /**
         * @property {$}
         */
        $el: null,

        /**
         * @inheritDoc
         */
        constructor: function MollieFormRefresherComponent() {
            MollieFormRefresherComponent.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.extend({}, this.options, options);
            let popupWindow = document.querySelector(this.options.baseSelector);
            if (popupWindow) {
                let copyButton = popupWindow.querySelector(this.options.copyButtonSelector);
                let closeButton = popupWindow.querySelector(this.options.closeButtonSelector);
                if (copyButton && closeButton) {
                    this.attachEventListener(copyButton, closeButton);
                }
            }

        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            delete this.$el;

            MolliePaymentLinkGeneratorComponent.__super__.dispose.call(this);
        },

        attachEventListener: function(copyButton, closeButton) {
            copyButton.addEventListener('click', function () {
                closeButton.click();
            })
        }


    });

    return MolliePaymentLinkGeneratorComponent;
});
