document.addEventListener('DOMContentLoaded', function () {
    const navLinks = document.getElementById('nav-links');
    const contentWrapper = document.getElementById('content-wrapper');
    const menuToggle = document.getElementById('menu-toggle');
    const hostingNavItem = document.querySelector('.nav-item-hosting');
    const submenu = document.querySelector('.submenu-hosting');

    // Toggle for the burger menu
    function toggleMenu() {
        navLinks.classList.toggle('active');
        if (navLinks.classList.contains('active')) {
            contentWrapper.style.marginTop = `${navLinks.offsetHeight}px`;
        } else {
            contentWrapper.style.marginTop = '0';
        }
    }

    // Prevent submenu dropdown in mobile view
    function handleHostingClick(event) {
        if (window.innerWidth < 1024) {
            // Mobile view: treat hosting as a simple link
            window.location.href = '#'; // Replace '#' with your desired link
            event.preventDefault();
        }
    }

    // Show/hide submenu on hover (for larger screens)
    function handleSubmenuVisibility(show) {
        if (window.innerWidth >= 1024) {
            submenu.style.opacity = show ? '1' : '0';
            submenu.style.visibility = show ? 'visible' : 'hidden';
            contentWrapper.style.marginTop = show ? `${submenu.offsetHeight}px` : '0';
        }
    }

    // Event listeners for the hosting item
    if (hostingNavItem && submenu) {
        hostingNavItem.addEventListener('mouseenter', () => handleSubmenuVisibility(true));
        hostingNavItem.addEventListener('mouseleave', () => handleSubmenuVisibility(false));
        hostingNavItem.addEventListener('click', handleHostingClick);
    }

    // Event listeners for the burger menu
    if (menuToggle) {
        menuToggle.addEventListener('click', toggleMenu);
    }

    // Adjust on resize
    window.addEventListener('resize', function () {
        if (window.innerWidth >= 1024) {
            // Reset styles for desktop view
            submenu.style.opacity = '0';
            submenu.style.visibility = 'hidden';
            contentWrapper.style.marginTop = '0';
        } else if (navLinks.classList.contains('active')) {
            contentWrapper.style.marginTop = `${navLinks.offsetHeight}px`;
        }
    });
});