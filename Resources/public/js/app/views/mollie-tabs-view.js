define(function (require) {
    'use strict';

    var tabsView,
        BaseView = require('oroui/js/app/views/base/view');

    tabsView = BaseView.extend({
        autoRender: true,

        events: {
            'click .nav-link': 'onTabClick'
        },

        contentContainer: null,
        selectedRefundTab: null,

        /**
         * Renders a tabs view
         */
        initialize: function (options) {
            this.contentContainer = document.querySelector(options.contentContainerSelector) || document;
            this.selectedRefundTab = document.querySelector(options.selectedRefundTabSelector);

            this.activateTab();

            return tabsView.__super__.initialize.call(this, options);
        },

        /**
         * Disposes the view
         */
        dispose: function () {
            if (this.disposed) {
                // the view is already removed
                return;
            }

            delete this.contentContainer;
            tabsView.__super__.dispose.call(this);
        },

        activateTab: function() {
            var hash = window.location.hash.indexOf('#mollie') === 0 ? window.location.hash : null;
            var activeTab = hash || this.el.querySelector('.nav-link').getAttribute("href");
            this.setActiveTab(activeTab);
        },

        onTabClick: function (e) {
            var $target = e.currentTarget.getAttribute("href");
            this.setActiveTab($target);
        },

        setActiveTab: function (activeTab) {
            var activeTabLink = this.el.querySelector('[href="'+activeTab+'"]');
            if (!activeTabLink) {
                activeTabLink = this.el.querySelector('.nav-link');
                activeTab = activeTabLink.getAttribute("href");
            }

            this.el.querySelectorAll('.nav-link').forEach(function (navLink) {
                navLink.classList.remove("active");
            });

            if (activeTabLink) {
                activeTabLink.classList.add("active");
            }

            this.contentContainer.querySelectorAll('[data-target]').forEach(function (navLink) {
                navLink.classList.add("hide");
            });
            this.contentContainer.querySelector('[data-target="'+activeTab+'"]').classList.remove("hide");
            this.setSelectedForm(activeTab);

            window.location.hash = activeTab;
        },

        setSelectedForm: function(activeTab) {
            if (this.selectedRefundTab) {
                this.selectedRefundTab.value = activeTab;
            }
        },
    });

    return tabsView;
});