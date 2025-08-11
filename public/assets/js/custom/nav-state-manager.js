/**
 * Navigation State Manager
 * Handles persistence of the sidebar navigation state (collapsed or expanded)
 */
const NavStateManager = {
    storageKey: 'navState',
    classes: {
        collapsed: 'semi-nav'
    },

    init: function() {
        this.$nav = $('nav');
        this.restoreState();
        this.setupEventListeners();
    },

    setupEventListeners: function() {
        $(document).on('click', '.header-toggle', () => {
            this.toggleState();
        });

        $('.toggle-semi-nav').on('click', () => {
            this.setExpanded();
        });
    },

    toggleState: function() {
        this.$nav.toggleClass(this.classes.collapsed);
        this.saveState();
    },

    setExpanded: function() {
        this.$nav.removeClass(this.classes.collapsed);
        this.saveState();
    },

    setCollapsed: function() {
        this.$nav.addClass(this.classes.collapsed);
        this.saveState();
    },

    saveState: function() {
        const state = this.$nav.hasClass(this.classes.collapsed) ? 'semi-nav' : 'full-nav';
        localStorage.setItem(this.storageKey, state);
    },

    restoreState: function() {
        const savedState = localStorage.getItem(this.storageKey);

        if (savedState === 'semi-nav') {
            this.$nav.addClass(this.classes.collapsed);
        } else {
            this.$nav.removeClass(this.classes.collapsed);
        }
    }
};

$(document).ready(function() {
    NavStateManager.init();
});