document.addEventListener('DOMContentLoaded', function() {
  const viewPlansBtn = document.getElementById('view-plans-btn');
  const hostingPlansSection = document.getElementById('hosting-plans');
  const hostingNavItem = document.querySelector('.nav-item-hosting');
  const submenu = hostingNavItem.querySelector('.submenu-hosting');
  const contentWrapper = document.getElementById('content-wrapper');
  const mainContainer = document.querySelector('.main-container');

  // View Plans button functionality
  viewPlansBtn?.addEventListener('click', function(e) {
      e.preventDefault();
      hostingPlansSection.scrollIntoView({ behavior: 'smooth' });
  });

  // Hosting submenu functionality
  function handleSubmenuToggle(show) {
      if (show) {
          submenu.style.opacity = '1';
          submenu.style.visibility = 'visible';
          const submenuHeight = submenu.offsetHeight;
          if (contentWrapper) {
              contentWrapper.style.marginTop = `${submenuHeight}px`;
          }
          if (mainContainer) {
              mainContainer.style.marginTop = `${submenuHeight}px`;
          }
      } else {
          submenu.style.opacity = '0';
          submenu.style.visibility = 'hidden';
          if (contentWrapper) {
              contentWrapper.style.marginTop = '0';
          }
          if (mainContainer) {
              mainContainer.style.marginTop = '0';
          }
      }
  }

  hostingNavItem.addEventListener('mouseenter', () => handleSubmenuToggle(true));
  hostingNavItem.addEventListener('mouseleave', () => handleSubmenuToggle(false));
});
