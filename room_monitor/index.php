<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_login();

$page_title = 'Dashboard';
$active_tab = 'dashboard';

$status = live_status();   // same data status.php sends
$state  = get_state();     // for the control fields

$state_words = ['running' => 'Running', 'off' => 'Off', 'idle' => 'Idle (enter a room name)'];
$led_words   = ['OFF' => 'Off', 'ON' => 'On', 'BLINK' => 'Blinking'];
$mode_words  = ['general' => 'General', 'fire' => 'Fire', 'custom' => 'Custom'];

$temp_text  = $status['temp'] !== null ? number_format((float) $status['temp'], 1, '.', '') : '--';
$has_room   = !empty($status['room']);
$is_running = $status['state'] === 'running';
$is_custom  = $status['mode'] === 'custom';

require __DIR__ . '/partials/header.php';
?>
<section class="card" id="live-card">
    <h2>Live temperature</h2>
    <div class="live-main">
        <div class="temp-wrap">
            <span id="temp-value"><?= h($temp_text) ?></span><span class="unit">°C</span>
        </div>
        <span id="temp-label" class="tone-<?= h($status['tone']) ?>"><?= h($status['label']) ?></span>
    </div>

    <div class="meta-grid">
        <div class="meta"><div class="meta-label">Mode</div><div class="meta-value" id="mode-name"><?= h($mode_words[$status['mode']] ?? '--') ?></div></div>
        <div class="meta"><div class="meta-label">Room</div><div class="meta-value" id="room-name"><?= h($status['room'] ?? '--') ?></div></div>
        <div class="meta"><div class="meta-label">System</div><div class="meta-value" id="system-state"><?= h($state_words[$status['state']] ?? '--') ?></div></div>
        <div class="meta"><div class="meta-label">LED</div><div class="meta-value" id="led-state"><?= h($led_words[$status['led']] ?? '--') ?></div></div>
        <div class="meta"><div class="meta-label">Updated</div><div class="meta-value" id="updated-at"><?= h($status['updated_at']) ?></div></div>
    </div>

    <div id="signal-note" class="note<?= $status['signal'] ? ' hidden' : '' ?>">No signal from the sensor bridge</div>
    <div id="sim-banner" class="banner<?= $status['sim_on'] ? '' : ' hidden' ?>">Simulation is ON: readings are fake</div>
</section>

<section class="card" id="controls-card">
    <h2>Controls</h2>

    <div class="control-block">
        <form id="form-mode" method="post" action="actions.php">
            <input type="hidden" name="action" value="set_mode">
            <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">

            <div class="form-row">
                <label>Mode
                    <select name="mode">
                        <?php foreach ($mode_words as $value => $text): ?>
                            <option value="<?= h($value) ?>"<?= $status['mode'] === $value ? ' selected' : '' ?>><?= h($text) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <button class="btn btn-neutral" type="submit">Save mode</button>
            </div>

            <div id="custom-fields" class="<?= $is_custom ? '' : 'hidden' ?>" style="margin-top:1rem">
                <div class="form-row">
                    <label>Lower limit (°C)
                        <input type="number" name="custom_low" step="0.1" min="0" max="60" value="<?= h($state['custom_low']) ?>">
                    </label>
                    <label>Upper limit (°C)
                        <input type="number" name="custom_high" step="0.1" min="0" max="60" value="<?= h($state['custom_high']) ?>">
                    </label>
                </div>
                <div class="form-row" style="margin-top:.9rem">
                    <label>Logging style
                        <select name="custom_log_style">
                            <option value="crossing"<?= $state['custom_log_style'] === 'crossing' ? ' selected' : '' ?>>On crossing</option>
                            <option value="periodic"<?= $state['custom_log_style'] === 'periodic' ? ' selected' : '' ?>>Periodic</option>
                        </select>
                    </label>
                    <label>Every (minutes, 1 to 60)
                        <input type="number" name="custom_interval_min" step="1" min="1" max="60" value="<?= h($state['custom_interval_min']) ?>">
                    </label>
                </div>
            </div>
        </form>
    </div>

    <div class="control-block">
        <?php if (!$has_room): ?>
            <form id="form-room" method="post" action="actions.php">
                <input type="hidden" name="action" value="set_room">
                <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
                <div class="form-row">
                    <label>Room name
                        <input type="text" name="room_name" required maxlength="100" placeholder="e.g. Room 1">
                    </label>
                    <button class="btn btn-neutral" type="submit">Start</button>
                </div>
            </form>
        <?php else: ?>
            <form id="form-change-room" method="post" action="actions.php" data-confirm="Change room? Monitoring will stop until you enter a new room name.">
                <input type="hidden" name="action" value="change_room">
                <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
                <button class="btn btn-neutral btn-ghost" type="submit">Change Room</button>
            </form>
        <?php endif; ?>
    </div>

    <div class="control-block">
        <div class="actions">
            <form id="form-toggle" method="post" action="actions.php">
                <input type="hidden" name="action" value="toggle_system">
                <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
                <?php if ($is_running): ?>
                    <button class="btn btn-red" type="submit">Turn system off</button>
                <?php else: ?>
                    <button class="btn btn-green" type="submit">Turn system on</button>
                <?php endif; ?>
            </form>

            <form id="form-silence" method="post" action="actions.php">
                <input type="hidden" name="action" value="silence">
                <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
                <button class="btn btn-neutral btn-ghost" type="submit">Silence LED</button>
            </form>
        </div>
    </div>
</section>

<section class="card">
    <details id="test-panel">
        <summary>Test panel</summary>

        <div class="control-block">
            <form id="form-sim" method="post" action="actions.php">
                <input type="hidden" name="action" value="test_sim">
                <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
                <div class="actions">
                    <?php if ($status['sim_on']): ?>
                        <button class="btn btn-red" type="submit" name="sim_on" value="0">Turn simulation off</button>
                    <?php else: ?>
                        <button class="btn btn-green" type="submit" name="sim_on" value="1">Turn simulation on</button>
                    <?php endif; ?>
                    <span class="hint">Simulation is <?= $status['sim_on'] ? 'ON' : 'OFF' ?>. When on, the real sensor is ignored.</span>
                </div>
            </form>
        </div>

        <div class="control-block">
            <form id="form-scenarios" method="post" action="actions.php">
                <input type="hidden" name="action" value="test_scenario">
                <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
                <div class="actions">
                    <button class="btn btn-neutral btn-ghost" type="submit" name="scenario" value="normal">Normal (29)</button>
                    <button class="btn btn-neutral btn-ghost" type="submit" name="scenario" value="spike">Spike</button>
                    <button class="btn btn-neutral btn-ghost" type="submit" name="scenario" value="elevated">Elevated (36)</button>
                    <button class="btn btn-neutral btn-ghost" type="submit" name="scenario" value="fire">Fire (42)</button>
                    <button class="btn btn-neutral btn-ghost" type="submit" name="scenario" value="too_low">Too Low</button>
                    <button class="btn btn-neutral btn-ghost" type="submit" name="scenario" value="too_high">Too High</button>
                </div>
            </form>
        </div>

        <div class="control-block">
            <form id="form-manual" method="post" action="actions.php">
                <input type="hidden" name="action" value="test_manual">
                <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
                <div class="form-row">
                    <label>Manual temperature (°C)
                        <input type="number" name="temp" step="0.1" min="0" max="60" required>
                    </label>
                    <button class="btn btn-neutral" type="submit">Send</button>
                </div>
            </form>
        </div>

        <div class="control-block">
            <div class="actions">
                <form id="form-force-hourly" method="post" action="actions.php">
                    <input type="hidden" name="action" value="test_force_hourly">
                    <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
                    <button class="btn btn-neutral btn-ghost" type="submit">Force hourly record</button>
                </form>

                <form id="form-clear-test" method="post" action="actions.php" data-confirm="Delete all TEST records and readings?">
                    <input type="hidden" name="action" value="test_clear">
                    <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
                    <button class="btn btn-red" type="submit">Clear test data</button>
                </form>
            </div>
        </div>
    </details>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>