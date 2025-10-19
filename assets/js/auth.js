// assets/js/auth.js - Updates navbar based on login status

(function() {
    'use strict';
    
    function checkAuthStatus() {
        fetch('api/check_session.php')
            .then(response => response.json())
            .then(data => {
                if (data.loggedIn) {
                    updateNavbarForLoggedIn(data.user);
                } else {
                    updateNavbarForLoggedOut();
                }
            })
            .catch(error => {
                console.error('Auth check failed:', error);
                updateNavbarForLoggedOut();
            });
    }
    
    function updateNavbarForLoggedIn(user) {
        const loginLink = document.getElementById('login-link');
        const profileLink = document.getElementById('profile-link');
        const profileAvatar = document.getElementById('profile-avatar');
        
        if (loginLink && profileLink) {
            loginLink.classList.add('hidden');
            profileLink.classList.remove('hidden');
            
            if (user.profile_picture) {
                profileAvatar.src = user.profile_picture;
            }
            profileAvatar.title = `${user.username}'s Profile`;
            
            addLogoutButton(user.username);
        }
    }
    
    function updateNavbarForLoggedOut() {
        const loginLink = document.getElementById('login-link');
        const profileLink = document.getElementById('profile-link');
        const logoutBtn = document.getElementById('logout-btn');
        const userGreeting = document.getElementById('user-greeting');
        
        if (loginLink && profileLink) {
            loginLink.classList.remove('hidden');
            profileLink.classList.add('hidden');
            if (logoutBtn) logoutBtn.remove();
            if (userGreeting) userGreeting.remove();
        }
    }
    
    function addLogoutButton(username) {
        if (document.getElementById('logout-btn')) return;
        
        const navContainer = document.querySelector('header nav > div > div:last-child');
        
        if (navContainer) {
            const greeting = document.createElement('span');
            greeting.id = 'user-greeting';
            greeting.className = 'hidden md:inline text-sm text-gray-800 dark:text-gray-100 font-medium mr-2';
            greeting.textContent = `Hi, ${username}!`;
            
            const logoutBtn = document.createElement('button');
            logoutBtn.id = 'logout-btn';
            logoutBtn.className = 'bg-red-600 dark:bg-red-500 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-red-700 dark:hover:bg-red-600 transition shadow-md';
            logoutBtn.innerHTML = '<i class="fas fa-sign-out-alt mr-1"></i> Logout';
            
            logoutBtn.addEventListener('click', function() {
                if (confirm('Are you sure you want to logout?')) {
                    logoutBtn.disabled = true;
                    logoutBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Logging out...';
                    window.location.href = 'api/logout.php';
                }
            });
            
            const profileLink = document.getElementById('profile-link');
            navContainer.insertBefore(greeting, profileLink);
            navContainer.insertBefore(logoutBtn, profileLink);
        }
    }
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', checkAuthStatus);
    } else {
        checkAuthStatus();
    }
    
    window.AuthManager = { checkAuthStatus };
})();