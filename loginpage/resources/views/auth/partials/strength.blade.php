<div class="strength"><div id="strength-bar"></div></div>
<p id="strength-label" class="strength-label"></p>

<script>
    const pw = document.getElementById('password');
    const bar = document.getElementById('strength-bar');
    const label = document.getElementById('strength-label');
    const levels = [
        { text: '', color: 'transparent' },
        { text: 'Weak', color: '#ff6b6b' },
        { text: 'Fair', color: '#f59e0b' },
        { text: 'Good', color: '#4a9bff' },
        { text: 'Strong', color: '#4ade80' },
    ];

    pw.addEventListener('input', () => {
        const v = pw.value;
        let s = 0;
        if (v.length >= 8) s++;
        if (/[a-z]/.test(v) && /[A-Z]/.test(v)) s++;
        if (/\d/.test(v)) s++;
        if (/[^A-Za-z0-9]/.test(v)) s++;
        if (v.length && s === 0) s = 1;
        if (!v.length) s = 0;

        bar.style.width = (s * 25) + '%';
        bar.style.background = levels[s].color;
        label.textContent = levels[s].text;
        label.style.color = levels[s].color;
    });
</script>