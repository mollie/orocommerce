define(function (require) {
    'use strict';

    var accordionView,
        BaseView = require('oroui/js/app/views/base/view');

    accordionView = BaseView.extend({
        autoRender: true,

        events: {
            'click .mollie-payment-method-nav': 'onHeaderClick',
            'click .mollie-remove-image': 'onRemoveImageClick',
        },

        /**
         * Renders a tabs view
         */
        initialize: function (options) {
            return accordionView.__super__.initialize.call(this, options);
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