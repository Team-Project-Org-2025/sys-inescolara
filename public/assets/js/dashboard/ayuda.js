(function () {
  'use strict';

  var btnStart = document.getElementById('btnStartTour');
  if (!btnStart) {
    return;
  }

  function openSidebarOnMobile() {
    if (window.innerWidth < 1024) {
      var sidebar = document.getElementById('sidebar');
      var overlay = document.getElementById('sidebarOverlay');
      if (sidebar) sidebar.classList.add('mobile-open');
      if (overlay) overlay.classList.add('active');
    }
  }

  function step(selector, title, description, opts) {
    var el = selector ? document.querySelector(selector) : null;
    if (!el) {
      return null;
    }
    var popover = { title: title, description: description };
    if (opts && opts.side) popover.side = opts.side;
    if (opts && opts.align) popover.align = opts.align;
    var s = { element: el, popover: popover };
    return s;
  }

  function buildSteps() {
    return [
      step('.sidebar-logo', 'Bienvenido a SYSINECOLARA', 'Este es el sistema de gestión del Vivero Institucional. A través de este recorrido te mostraremos las principales áreas para que te familiarices con el sistema.'),
      step('.sidebar-nav > a.nav-link', 'Inicio', 'Desde aquí accedes al panel principal con los indicadores y resúmenes del sistema.'),
      step('.sidebar-section-label', 'Secciones del menú', 'El menú lateral está organizado en secciones: Inventario, Comercial, Operaciones, Herramientas y Sistema, según los permisos de tu usuario.'),
      step('.nav-group-btn', 'Submenús desplegables', 'Cada grupo agrupa módulos relacionados. Haz clic para expandir y ver las opciones disponibles.'),
      step('a.nav-link[href$="dashboard/asistente"]', 'Asistente IA', 'Asistente inteligente para consultar información del sistema en lenguaje natural.', { side: 'right' }),
      step('a.nav-link[href$="dashboard/reports"]', 'Reportes', 'Genera reportes y estadísticas de las operaciones del vivero.', { side: 'right' }),
      step('.help-link', 'Ayuda', 'Este es el botón de ayuda. En esta página encuentras preguntas frecuentes y el recorrido que estás viendo.'),
      step('#notificationsBtn', 'Notificaciones', 'Aquí verás las alertas y notificaciones del sistema, como stock bajo o tareas pendientes.'),
      step('#userDropdownBtn', 'Tu perfil', 'Accede a tu perfil para actualizar tus datos o cambiar la contraseña.'),
      step('.logout-link', 'Cerrar sesión', 'Finaliza tu sesión de forma segura al terminar de trabajar.'),
    ].filter(Boolean);
  }

  function startTour() {
    if (typeof window.driver === 'undefined' || !window.driver.js || !window.driver.js.driver) {
      return;
    }
    var driver = window.driver.js.driver;
    var steps = buildSteps();
    if (steps.length === 0) {
      return;
    }
    openSidebarOnMobile();
    var driverObj = driver({
      animate: true,
      smoothScroll: false,
      allowClose: true,
      allowKeyboardControl: true,
      overlayColor: 'rgba(0, 0, 0, 0.55)',
      overlayClickBehavior: 'close',
      stagePadding: 6,
      stageRadius: 10,
      skipMissingElement: true,
      showProgress: true,
      showButtons: ['next', 'previous', 'close'],
      nextBtnText: 'Siguiente',
      prevBtnText: 'Atrás',
      doneBtnText: 'Finalizar',
      closeBtnText: 'Cerrar',
      progressText: '{{current}} de {{total}}',
      onHighlightStarted: function (el) {
        if (!el) return;
        var scrollable = el.closest('.sidebar-nav');
        if (scrollable && (scrollable.scrollHeight > scrollable.clientHeight)) {
          var top = el.offsetTop - scrollable.offsetTop - scrollable.clientHeight / 2 + el.offsetHeight / 2;
          scrollable.scrollTop = Math.max(0, Math.min(top, scrollable.scrollHeight - scrollable.clientHeight));
        }
      },
      steps: steps,
    });
    driverObj.drive();
  }

  btnStart.addEventListener('click', startTour);
})();
