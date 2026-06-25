(function () {
    'use strict';

    function initPasswordStrength() {
        const passwordInput = document.getElementById('password');
        const strengthBar = document.getElementById('strengthBar');
        const strengthText = document.getElementById('strengthText');
        if (!passwordInput || !strengthBar) return;

        const patterns = [
            { re: /[a-z]/, label: 'minúscula' },
            { re: /[A-Z]/, label: 'mayúscula' },
            { re: /\d/, label: 'número' },
            { re: /[@$!%*?&._-]/, label: 'especial' },
        ];

        passwordInput.addEventListener('input', function () {
            const val = this.value;
            let passed = 0;
            patterns.forEach(function (p) { if (p.re.test(val)) passed++; });
            if (val.length >= 8) passed++;

            const pct = Math.min((passed / patterns.length) * 100, 100);
            strengthBar.style.width = pct + '%';

            if (val.length === 0) {
                strengthBar.className = 'password-strength-bar';
                if (strengthText) strengthText.textContent = '';
                return;
            }

            var level, color, text;
            if (passed <= 2) { level = 'weak'; color = '#dc3545'; text = 'Débil'; }
            else if (passed <= 3) { level = 'medium'; color = '#e5a835'; text = 'Media'; }
            else { level = 'strong'; color = '#28a745'; text = 'Fuerte'; }

            strengthBar.className = 'password-strength-bar ' + level;
            strengthBar.style.backgroundColor = color;
            if (strengthText) {
                strengthText.textContent = 'Seguridad: ' + text;
                strengthText.style.color = color;
            }
        });
    }

    function initPasswordMatch() {
        const passwordInput = document.getElementById('password');
        const password2Input = document.getElementById('password2');
        const matchText = document.getElementById('matchText');
        if (!passwordInput || !password2Input || !matchText) return;

        function checkMatch() {
            const p1 = passwordInput.value;
            const p2 = password2Input.value;
            if (p2.length === 0) {
                matchText.textContent = '';
                return;
            }
            if (p1 === p2) {
                matchText.textContent = '✓ Las contraseñas coinciden';
                matchText.style.color = '#28a745';
            } else {
                matchText.textContent = '✗ Las contraseñas no coinciden';
                matchText.style.color = '#dc3545';
            }
        }

        passwordInput.addEventListener('input', checkMatch);
        password2Input.addEventListener('input', checkMatch);
    }

    function initPasswordToggle() {
        document.querySelectorAll('.password-toggle').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var wrapper = this.closest('.input-wrapper');
                if (!wrapper) return;
                var input = wrapper.querySelector('input[type="password"], input[type="text"]');
                if (!input) return;
                var isPassword = input.type === 'password';
                input.type = isPassword ? 'text' : 'password';
                wrapper.querySelectorAll('.eye-icon, .eye-off-icon').forEach(function (icon) {
                    icon.classList.toggle('hidden');
                });
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        initPasswordStrength();
        initPasswordMatch();
        initPasswordToggle();
    });
})();
