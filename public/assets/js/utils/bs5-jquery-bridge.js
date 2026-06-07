// Bootstrap 5 jQuery compatibility bridge
// Adds jQuery methods (modal, tooltip, etc.) that delegate to Bootstrap 5's native API

(function ($) {
    if (!$ || !$.fn) return;

    // --- Modal ---
    $.fn.modal = function (option) {
        return this.each(function () {
            const el = this;
            let bsModal = bootstrap.Modal.getInstance(el);
            if (!bsModal) {
                const opts = typeof option === 'object' ? option : {};
                bsModal = new bootstrap.Modal(el, opts);
            }
            if (typeof option === 'string') {
                if (option === 'show' || option === 'hide' || option === 'toggle') {
                    bsModal[option]();
                }
            }
        });
    };

    // --- Tooltip ---
    $.fn.tooltip = function (option) {
        return this.each(function () {
            const el = this;
            if (typeof option === 'string') {
                const inst = bootstrap.Tooltip.getInstance(el);
                if (inst && inst[option]) inst[option]();
            } else {
                new bootstrap.Tooltip(el, option || {});
            }
        });
    };

    // --- Popover ---
    $.fn.popover = function (option) {
        return this.each(function () {
            const el = this;
            if (typeof option === 'string') {
                const inst = bootstrap.Popover.getInstance(el);
                if (inst && inst[option]) inst[option]();
            } else {
                new bootstrap.Popover(el, option || {});
            }
        });
    };

    // --- Toast ---
    $.fn.toast = function (option) {
        return this.each(function () {
            const el = this;
            let inst = bootstrap.Toast.getInstance(el);
            if (!inst) {
                inst = new bootstrap.Toast(el, typeof option === 'object' ? option : {});
            }
            if (typeof option === 'string') {
                if (option === 'show' || option === 'hide') inst[option]();
            }
        });
    };

    // --- Tab ---
    $.fn.tab = function (option) {
        return this.each(function () {
            const el = this;
            if (typeof option === 'string') {
                const inst = bootstrap.Tab.getInstance(el);
                if (inst && inst[option]) inst[option]();
            } else {
                new bootstrap.Tab(el);
            }
        });
    };

    // --- Collapse ---
    $.fn.collapse = function (option) {
        return this.each(function () {
            const el = this;
            let inst = bootstrap.Collapse.getInstance(el);
            if (!inst) {
                inst = new bootstrap.Collapse(el, typeof option === 'object' ? option : {});
            }
            if (typeof option === 'string') {
                if (option === 'show' || option === 'hide' || option === 'toggle') inst[option]();
            }
        });
    };

    // --- Dropdown ---
    $.fn.dropdown = function (option) {
        return this.each(function () {
            const el = this;
            if (typeof option === 'string') {
                const inst = bootstrap.Dropdown.getInstance(el);
                if (inst && inst[option]) inst[option]();
            } else {
                new bootstrap.Dropdown(el, option || {});
            }
        });
    };

})(jQuery);
