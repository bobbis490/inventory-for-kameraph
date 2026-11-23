/**
 * KameraPH Theme Switcher
 * Toggles between 'dark' and 'light' modes.
 * State persisted in localStorage key: 'kameraph-theme'
 */
(function () {
    'use strict';

    const STORAGE_KEY = 'kameraph-theme';
    const DEFAULT_THEME = 'dark';

    function getTheme() {
        return localStorage.getItem(STORAGE_KEY) || DEFAULT_THEME;
    }

    function applyTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);

        const btn  = document.getElementById('themeToggleBtn');
        const icon = document.getElementById('themeIcon');

        if (icon) {
            if (theme === 'dark') {
                icon.className = 'bi bi-sun-fill';
                if (btn) btn.title = 'Switch to Light Mode';
            } else {
                icon.className = 'bi bi-moon-stars-fill';
                if (btn) btn.title = 'Switch to Night Mode';
            }
        }
    }

    function toggleTheme() {
        const current = getTheme();
        const next = (current === 'dark') ? 'light' : 'dark';
        localStorage.setItem(STORAGE_KEY, next);
        applyTheme(next);
    }

    // Apply theme immediately on page load (backup to inline head script)
    applyTheme(getTheme());

    document.addEventListener('DOMContentLoaded', function () {
        // Apply again once DOM is ready to sync icon state
        applyTheme(getTheme());

        const btn = document.getElementById('themeToggleBtn');
        if (btn) {
            btn.addEventListener('click', toggleTheme);
        }
    });
})();
