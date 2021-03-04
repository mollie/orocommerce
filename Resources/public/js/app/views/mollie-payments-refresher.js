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
            this.backendUrl = options.backendUrl;

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
            } else {
                this.getEnabledMethods($(this.websiteChooserSelector).val());
            }
        },

        getEnabledMethods: function(selectedProfile) {
            let url = this.backendUrl.replace('mollieProfileId', selectedProfile);
            $.ajax({
                url: url,
                type: 'GET',
                success: this.successHandler.bind(this),
            });
        },

        /**
         * @param {{success: boolean, activeMethods: string}} response
         */
        successHandler: function(response) {
            let activeMethods = $('.mollie-enabled-methods');
            let activeMethodsWrapper = $('.mollie-enabled-methods-wrapper');
            if (activeMethods && response.success && activeMethodsWrapper) {
                activeMethods.text(response.activeMethods);
                activeMethodsWrapper.removeClass('mollie-hidden');
            }
        },

        onPageSubmitComplete: function() {
            this.getEnabledMethods($('select.mollie-website-chooser').val());
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