function toggleMenu() {
  const navLinks = document.getElementById('nav-links');
  const contentWrapper = document.getElementById('content-wrapper');

  // Toggle the active class on the menu
  navLinks.classList.toggle('active');

  // Push the content wrapper down based on the menu's height
    if (navLinks.classList.contains('active')) {
       contentWrapper.style.marginTop = `${navLinks.offsetHeight}px`;
     } else {
       contentWrapper.style.marginTop = '0';
  }
}

document.getElementById('menu-toggle').addEventListener('click', toggleMenu);

window.addEventListener('resize', function () {
  const navLinks = document.getElementById('nav-links');
  const contentWrapper = document.getElementById('content-wrapper');


  if (navLinks.classList.contains('active') && window.innerWidth < 768) {
        contentWrapper.style.marginTop = `${navLinks.offsetHeight}px`;
  } else {
        contentWrapper.style.marginTop = '0';
    }
});