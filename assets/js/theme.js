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
        } else {
            // Explicitly set light mode default for icon state
            this.applyTheme(this.LIGHT_MODE);
        }
    },

    // Apply theme to document
    applyTheme: function (theme) {
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem(this.STORAGE_KEY, theme);

        // Update icon
        const icon = document.getElementById('darkModeIcon');
        if (icon) {
            if (theme === this.DARK_MODE) {
                icon.classList.remove('fa-moon');
                icon.classList.add('fa-sun');
            } else {
                icon.classList.remove('fa-sun');
                icon.classList.add('fa-moon');
            }
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

// Expose global function
function toggleTheme() {
    ThemeManager.toggle();
}

// Re-check state when DOM is loaded
document.addEventListener('DOMContentLoaded', function () {
    const currentTheme = localStorage.getItem('ims_theme') || 'light';
    ThemeManager.applyTheme(currentTheme);
});
