/**
 * Theme Management for Inventory Management System
 * Handles toggling between Light and Dark modes with localStorage persistence.
 */

const ThemeManager = {
    // Keys
    STORAGE_KEY: 'ims_theme',
    DARK_MODE: 'dark',
    LIGHT_MODE: 'light',

    // Initialize theme
    init: function () {
        const savedTheme = localStorage.getItem(this.STORAGE_KEY);
        // Check system preference if no saved theme
        const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;

        if (savedTheme) {
            this.applyTheme(savedTheme);
        } else if (prefersDark) {
            this.applyTheme(this.DARK_MODE);
        }
    },

    // Apply theme to document
    applyTheme: function (theme) {
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem(this.STORAGE_KEY, theme);

        // Update toggle switch if it exists on the page
        const toggle = document.getElementById('darkModeSwitch');
        if (toggle) {
            toggle.checked = (theme === this.DARK_MODE);
        }
    },

    // Toggle between modes
    toggle: function () {
        const currentTheme = document.documentElement.getAttribute('data-theme');
        const newTheme = currentTheme === this.DARK_MODE ? this.LIGHT_MODE : this.DARK_MODE;
        this.applyTheme(newTheme);
    }
};

// Run initialization immediately to prevent FOUC
ThemeManager.init();

// Expose global function for the switch
function toggleTheme() {
    ThemeManager.toggle();
}

// Re-check toggle state when DOM is loaded (in case script ran before switch existed)
document.addEventListener('DOMContentLoaded', function () {
    const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
    const toggle = document.getElementById('darkModeSwitch');
    if (toggle) {
        toggle.checked = (currentTheme === 'dark');
    }
});
