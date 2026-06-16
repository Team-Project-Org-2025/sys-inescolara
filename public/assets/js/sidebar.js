/**
 * Funcionalidad del Sidebar para el Dashboard
 * Controla apertura/cierre en dispositivos móviles
 */

document.addEventListener('DOMContentLoaded', () => {
  const sidebar = document.getElementById('sidebar');
  const sidebarOverlay = document.getElementById('sidebarOverlay');
  const mobileMenuToggle = document.getElementById('mobileMenuToggle');
  const sidebarClose = document.getElementById('sidebarClose');

  /**
   * Abre el sidebar en dispositivos móviles
   */
  function openSidebar() {
    sidebar?.classList.add('mobile-open');
    sidebarOverlay?.classList.add('active');
    document.body.style.overflow = 'hidden';
  }

  /**
   * Cierra el sidebar en dispositivos móviles
   */
  function closeSidebar() {
    sidebar?.classList.remove('mobile-open');
    sidebarOverlay?.classList.remove('active');
    document.body.style.overflow = '';
  }

  // Event Listeners
  mobileMenuToggle?.addEventListener('click', openSidebar);
  sidebarClose?.addEventListener('click', closeSidebar);
  sidebarOverlay?.addEventListener('click', closeSidebar);

  // Cerrar con tecla Escape
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      closeSidebar();
    }
  });

  // Cerrar sidebar al hacer clic en un enlace (mejor experiencia móvil)
  const sidebarLinks = sidebar?.querySelectorAll('.sidebar-link');
  sidebarLinks?.forEach((link) => {
    link.addEventListener('click', () => {
      if (window.innerWidth < 1024) {
        // Pequeño retraso para que se vea la transición antes de navegar
        setTimeout(closeSidebar, 150);
      }
    });
  });
});

