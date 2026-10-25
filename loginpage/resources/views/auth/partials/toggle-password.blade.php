<style>
    .pw-wrap { position:relative; }
    .pw-wrap input { padding-right:44px !important; }
    .pw-toggle {
        position:absolute; right:12px; top:50%; transform:translateY(-50%);
        background:none; border:none; cursor:pointer; padding:0;
        color:#7c8aa8; display:flex; align-items:center;
    }
    .pw-toggle:hover { color:#4a9bff; }
    .pw-toggle svg { width:20px; height:20px; }
</style>

<script>
    (function () {
        const eye = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
        const eyeOff = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>';

        document.querySelectorAll('input[type="password"]').forEach(function (input) {
            const wrap = document.createElement('div');
            wrap.className = 'pw-wrap';
            input.parentNode.insertBefore(wrap, input);
            wrap.appendChild(input);

            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'pw-toggle';
            btn.setAttribute('aria-label', 'Show password');
            btn.innerHTML = eye;
            wrap.appendChild(btn);

            btn.addEventListener('click', function () {
                const show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                btn.innerHTML = show ? eyeOff : eye;
                btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            });
        });
    })();
</script>