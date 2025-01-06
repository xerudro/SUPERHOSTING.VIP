document.addEventListener('DOMContentLoaded', function () {
    const hostingNavItem = document.querySelector('.nav-item-hosting');
    const submenu = hostingNavItem?.querySelector('.submenu-hosting');
    const hostingLink = hostingNavItem?.querySelector('a');
    const burgerMenuToggle = document.getElementById('menu-toggle');

    // Disable submenu toggle on smaller screens
    function disableSubmenuOnSmallScreens() {
        if (window.innerWidth < 768) {
            hostingLink.removeEventListener('mouseenter', showSubmenu);
            hostingLink.removeEventListener('focus', showSubmenu);
            hostingLink.removeEventListener('mouseleave', hideSubmenu);
            hostingLink.removeEventListener('blur', hideSubmenu);
            hostingLink.href = hostingLink.dataset.href; // Restore normal link behavior
        } else {
            hostingLink.addEventListener('mouseenter', showSubmenu);
            hostingLink.addEventListener('focus', showSubmenu);
            hostingLink.addEventListener('mouseleave', hideSubmenu);
            hostingLink.addEventListener('blur', hideSubmenu);
        }
    }

    // Show submenu
    function showSubmenu() {
        submenu.style.opacity = '1';
        submenu.style.visibility = 'visible';
    }

    // Hide submenu
    function hideSubmenu() {
        submenu.style.opacity = '0';
        submenu.style.visibility = 'hidden';
    }

    // Ensure submenu behavior adapts to screen size
    window.addEventListener('resize', disableSubmenuOnSmallScreens);
    disableSubmenuOnSmallScreens();
});
