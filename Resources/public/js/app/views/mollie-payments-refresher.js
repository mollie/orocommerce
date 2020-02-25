define([
    'jquery',
    'underscore',
    'oroui/js/mediator',
    'oroui/js/app/views/base/view'
], function($, _, mediator, BaseView) {
    'use strict';

    var paymentsRefresherView;

    paymentsRefresherView = BaseView.extend({
        autoRender: true,

        /**
         * Renders a tabs view
         */
        initialize: function (options) {
            this.websiteChooserSelector = options.websiteChooserSelector;
            this.websiteProfileChangeMarkerName = options.websiteProfileChangeMarkerName;
            this.formSaveMarkerSelector = options.formSaveMarkerSelector;

            $(this.websiteChooserSelector).change(_.bind(this.onWebsiteChange, this));

            return paymentsRefresherView.__super__.initialize.call(this, options);
        },

        /**
         * Disposes the view
         */
        dispose: function () {
            if (this.disposed) {
                // the view is already removed
                return;
            }

            paymentsRefresherView.__super__.dispose.call(this);
        },

        onWebsiteChange: function(e) {
            // ... retrieve the corresponding form.
            var $target = e.currentTarget,
                $form = $($target).closest('form'),
                data = $form.serializeArray(),
                url = $form.attr('action');

            // Set flag in request that website profile data should be refreshed
            data.push({ name: 'formUpdateMarker', value: $target.getAttribute('name') + '' });
            data.push({ name: this.websiteProfileChangeMarkerName, value: 1 });

            var event = {formEl: $form, data: data, reloadManually: true};
            mediator.trigger('integrationFormReload:before', event);

            if (event.reloadManually) {
                mediator.execute('submitPage', {
                    url: url, type: $form.attr('method'), data: $.param(data),
                    complete: _.bind(this.onPageSubmitComplete, this)
                });
            }
        },

        onPageSubmitComplete: function() {
            var $mollieSaveMarkerSelector = this.formSaveMarkerSelector;
            mediator.once('page:afterChange', function () {
                setTimeout(function() {
                    var $mollieSaveMarker = $($mollieSaveMarkerSelector);
                    $mollieSaveMarker.val($mollieSaveMarker.val() + '1');
                }, 1);
            });
        }
    });

    return paymentsRefresherView;
});