// assets/js/theme.js - Centralized Dark Mode Handler

(function() {
    'use strict';
    
    const html = document.documentElement;
    const THEME_KEY = 'theme';
    
    // Function to apply theme
    function applyTheme(theme) {
        if (theme === 'dark') {
            html.classList.add('dark');
        } else {
            html.classList.remove('dark');
        }
    }
    
    // Function to get current theme
    function getCurrentTheme() {
        // Check localStorage first
        const savedTheme = localStorage.getItem(THEME_KEY);
        if (savedTheme) {
            return savedTheme;
        }
        
        // Check system preference
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            return 'dark';
        }
        
        // Default to light
        return 'light';
    }
    
    // Function to set theme
    function setTheme(theme) {
        localStorage.setItem(THEME_KEY, theme);
        applyTheme(theme);
        
        // Dispatch custom event for other scripts to listen to
        window.dispatchEvent(new CustomEvent('themeChanged', { detail: { theme } }));
    }
    
    // Function to toggle theme
    function toggleTheme() {
        const currentTheme = getCurrentTheme();
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        setTheme(newTheme);
        return newTheme;
    }
    
    // Apply theme immediately on page load (before DOM ready)
    applyTheme(getCurrentTheme());
    
    // Initialize theme toggle buttons when DOM is ready
    function initializeThemeToggle() {
        const themeToggleButtons = document.querySelectorAll('#theme-toggle, .theme-toggle');
        
        themeToggleButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                toggleTheme();
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeThemeToggle);
    } else {
        initializeThemeToggle();
    }
    

    window.addEventListener('storage', function(e) {
        if (e.key === THEME_KEY) {
            applyTheme(e.newValue);
        }
    });
    
    window.ThemeManager = {
        getCurrentTheme,
        setTheme,
        toggleTheme
    };
})();