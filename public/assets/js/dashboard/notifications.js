(function () {
    const BASE = document.querySelector('base')?.href || '/sys-inescolara/';

    function actualizarBadge() {
        fetch(BASE + 'notifications/get_unread')
            .then(r => r.json())
            .then(data => {
                if (!data.success) return;
                const badge = document.getElementById('notifBadge');
                if (badge) {
                    if (data.count > 0) {
                        badge.textContent = data.count > 99 ? '99+' : data.count;
                        badge.style.display = 'flex';
                    } else {
                        badge.style.display = 'none';
                    }
                }
                const dropdown = document.getElementById('notifDropdown');
                if (dropdown && data.notifications) {
                    const list = dropdown.querySelector('.notif-dropdown-list');
                    const empty = dropdown.querySelector('.notif-dropdown-empty');
                    if (list) {
                        list.innerHTML = '';
                        if (data.notifications.length === 0) {
                            if (empty) empty.style.display = 'block';
                        } else {
                            if (empty) empty.style.display = 'none';
                            data.notifications.forEach(n => {
                                const item = document.createElement('a');
                                item.className = 'notif-dropdown-item' + (n.leida ? ' read' : ' unread');
                                item.href = n.link ? BASE + n.link : '#';
                                item.innerHTML = `
                                    <div class="notif-dd-icon notif-${n.tipo || 'info'}">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            ${getIcon(n.tipo)}
                                        </svg>
                                    </div>
                                    <div class="notif-dd-body">
                                        <div class="notif-dd-title">${escHtml(n.titulo)}</div>
                                        <div class="notif-dd-time">${tiempoRelativo(n.fecha_creacion)}</div>
                                    </div>
                                `;
                                list.appendChild(item);
                            });
                        }
                    }
                }
            })
            .catch(() => {});
    }

    function getIcon(tipo) {
        switch (tipo) {
            case 'success': return '<polyline points="22 4 8 18 2 12"></polyline>';
            case 'warning': return '<path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>';
            case 'task_assigned': return '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>';
            default: return '<circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>';
        }
    }

    function escHtml(s) {
        if (!s) return '';
        const d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }

    function tiempoRelativo(fecha) {
        const ahora = new Date();
        const f = new Date(fecha.replace(' ', 'T') + '-04:00');
        const diffMs = ahora - f;
        const mins = Math.floor(diffMs / 60000);
        if (mins < 1) return 'Ahora';
        if (mins < 60) return `Hace ${mins} min`;
        const hrs = Math.floor(mins / 60);
        if (hrs < 24) return `Hace ${hrs} h`;
        const dias = Math.floor(hrs / 24);
        if (dias < 7) return `Hace ${dias} d`;
        return f.toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric' });
    }

    document.addEventListener('DOMContentLoaded', function () {
        actualizarBadge();
        setInterval(actualizarBadge, 5000);

        const btn = document.getElementById('notificationsBtn');
        const dropdown = document.getElementById('notifDropdown');
        if (btn && dropdown) {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                dropdown.classList.toggle('show');
            });
            document.addEventListener('click', function () {
                dropdown.classList.remove('show');
            });
            dropdown.addEventListener('click', function (e) {
                e.stopPropagation();
            });
        }
    });
})();

