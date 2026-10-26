<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

// Already logged in: go to the dashboard.
if (!empty($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    try {
        if ($username !== '' && $password !== '' && attempt_login($username, $password)) {
            header('Location: index.php');
            exit;
        }
        flash_set('error', 'Invalid username or password.');
    } catch (Throwable $e) {
        error_log($e->getMessage());
        flash_set('error', 'Cannot reach the database. Check that MySQL is running and the schema is imported.');
    }

    header('Location: login.php');
    exit;
}

$page_title = 'Login';
$active_tab = '';
require __DIR__ . '/partials/header.php';
?>
<section class="card card-narrow">
    <div class="auth-head">
        <div class="auth-logo" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M14 14.76V3.5a2.5 2.5 0 0 0-5 0v11.26a4.5 4.5 0 1 0 5 0z"/></svg>
        </div>
        <h2>Admin login</h2>
        <p class="auth-sub">Sign in to monitor the room temperature.</p>
    </div>

    <form id="form-login" method="post" action="login.php" novalidate>
        <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">

        <div class="field">
            <label for="login-username">Username</label>
            <input id="login-username" type="text" name="username" required maxlength="50"
                   autocomplete="username" autocapitalize="none" spellcheck="false" autofocus
                   aria-describedby="username-error">
            <p id="username-error" class="field-error hidden" role="alert"></p>
        </div>

        <div class="field">
            <label for="login-password">Password</label>
            <div class="pw-wrap">
                <input id="login-password" type="password" name="password" required
                       autocomplete="current-password" aria-describedby="caps-note password-error">
                <button class="pw-toggle" id="pw-toggle" type="button" aria-label="Show password" aria-pressed="false">Show</button>
            </div>
            <p id="caps-note" class="hint caps-note hidden" role="status">Caps Lock is on</p>
            <p id="password-error" class="field-error hidden" role="alert"></p>
        </div>

        <button class="btn btn-neutral btn-block" id="login-submit" type="submit"><span class="btn-label">Log in</span></button>
    </form>

    <p class="auth-foot">No public sign-up. Ask an existing admin to add you.</p>
</section>

<script>
(function () {
    var form = document.getElementById('form-login');
    var user = document.getElementById('login-username');
    var pw = document.getElementById('login-password');
    var toggle = document.getElementById('pw-toggle');
    var caps = document.getElementById('caps-note');
    var submit = document.getElementById('login-submit');
    var label = submit ? submit.querySelector('.btn-label') : null;
    var userErr = document.getElementById('username-error');
    var pwErr = document.getElementById('password-error');
    var submitting = false;
    if (!form || !user || !pw || !submit || !label) return;

    function showError(input, box, message) {
        box.textContent = message;
        box.classList.remove('hidden');
        input.setAttribute('aria-invalid', 'true');
    }
    function clearError(input, box) {
        box.textContent = '';
        box.classList.add('hidden');
        input.removeAttribute('aria-invalid');
    }
    function resetButton() {
        submitting = false;
        submit.classList.remove('is-loading');
        submit.removeAttribute('aria-busy');
        label.textContent = 'Log in';
    }

    toggle.addEventListener('click', function () {
        var show = pw.type === 'password';
        pw.type = show ? 'text' : 'password';
        toggle.textContent = show ? 'Hide' : 'Show';
        toggle.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
        toggle.setAttribute('aria-pressed', show ? 'true' : 'false');
        pw.focus();
    });

    function checkCaps(e) {
        if (e.getModifierState) {
            caps.classList.toggle('hidden', !e.getModifierState('CapsLock'));
        }
    }
    pw.addEventListener('keydown', checkCaps);
    pw.addEventListener('keyup', checkCaps);
    pw.addEventListener('blur', function () { caps.classList.add('hidden'); });

    user.addEventListener('input', function () { clearError(user, userErr); });
    pw.addEventListener('input', function () { clearError(pw, pwErr); });

    form.addEventListener('submit', function (e) {
        if (submitting) { e.preventDefault(); return; }

        var first = null;
        if (user.value.trim() === '') {
            showError(user, userErr, 'Enter your username.');
            first = user;
        }
        if (pw.value === '') {
            showError(pw, pwErr, 'Enter your password.');
            first = first || pw;
        }
        if (first) {
            e.preventDefault();
            first.focus();
            return;
        }

        submitting = true;
        submit.classList.add('is-loading');
        submit.setAttribute('aria-busy', 'true');
        label.textContent = 'Signing in...';
        // Safety: if the server never answers, let the person try again.
        setTimeout(resetButton, 12000);
    });

    // Back button: restore a clean form.
    window.addEventListener('pageshow', function () {
        resetButton();
        pw.type = 'password';
        toggle.textContent = 'Show';
        toggle.setAttribute('aria-pressed', 'false');
    });
})();
</script>
<?php require __DIR__ . '/partials/footer.php'; ?>