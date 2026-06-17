document.addEventListener('DOMContentLoaded', () => {
  const sidebar = document.getElementById('sidebar');
  const sidebarOverlay = document.getElementById('sidebarOverlay');
  const mobileMenuToggle = document.getElementById('mobileMenuToggle');
  const sidebarClose = document.getElementById('sidebarClose');

  function openSidebar() {
    sidebar?.classList.add('mobile-open');
    sidebarOverlay?.classList.add('active');
    document.body.style.overflow = 'hidden';
  }

  function closeSidebar() {
    sidebar?.classList.remove('mobile-open');
    sidebarOverlay?.classList.remove('active');
    document.body.style.overflow = '';
  }

  mobileMenuToggle?.addEventListener('click', openSidebar);
  sidebarClose?.addEventListener('click', closeSidebar);
  sidebarOverlay?.addEventListener('click', closeSidebar);

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeSidebar();
  });

  // Fix: cerrar sidebar al navegar en móvil (usa .nav-link, no .sidebar-link)
  sidebar?.querySelectorAll('.nav-link').forEach((link) => {
    link.addEventListener('click', () => {
      if (window.innerWidth < 1024) {
        setTimeout(closeSidebar, 150);
      }
    });
  });

  // Toggle de submenús (movido desde inline script en sidebar.php)
  document.querySelectorAll('.nav-group-btn').forEach((btn) => {
    btn.addEventListener('click', function () {
      const submenu = this.nextElementSibling;
      const chevron = this.querySelector('.chevron');
      submenu?.classList.toggle('show');
      chevron?.classList.toggle('rotate');
    });
  });
});
