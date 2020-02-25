define(function(require) {
    'use strict';

    var MollieFormRefresherComponent;
    var BaseComponent = require('oroui/js/app/components/base/component');
    var mediator = require('oroui/js/mediator');
    var $ = require('jquery');
    var _ = require('underscore');

    MollieFormRefresherComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            refreshForm: false,
            formUpdateMarker: 'mollie_form'
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
            this.$el = this.options._sourceElement;

            if (this.options.refreshForm) {
                this.doFormRefresh();
            }
        },

        doFormRefresh: function() {
            var $form = this.$el.closest('form'),
                data = $form.serializeArray(),
                url = $form.attr('action');

            // Set flag in request that website profile data should be refreshed
            data.push({ name: 'formUpdateMarker', value: this.options.formUpdateMarker + '' });

            var event = {formEl: $form, data: data, reloadManually: true};
            mediator.trigger('integrationFormReload:before', event);

            if (event.reloadManually) {
                mediator.execute('submitPage', {
                    url: url, type: $form.attr('method'), data: $.param(data),
                });
            }
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            delete this.$el;

            MollieFormRefresherComponent.__super__.dispose.call(this);
        }
    });

    return MollieFormRefresherComponent;
});
