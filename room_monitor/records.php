<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_login();

$page_title = 'Records';
$active_tab = 'records';

$event_words = [
    'HOURLY'        => 'Hourly',
    'SPIKE'         => 'Spike',
    'POSSIBLE_FIRE' => 'Possible fire',
    'FIRE'          => 'Fire detected',
    'LIMIT_HIGH'    => 'Above limit',
    'LIMIT_LOW'     => 'Below limit',
    'PERIODIC'      => 'Periodic',
];
$mode_words = ['general' => 'General', 'fire' => 'Fire', 'custom' => 'Custom'];

$room   = trim((string) ($_GET['room'] ?? ''));
$mode   = (string) ($_GET['mode'] ?? '');
$event  = (string) ($_GET['event'] ?? '');
$pageNo = max(1, (int) ($_GET['page'] ?? 1));

if (!isset($mode_words[$mode]))   { $mode = ''; }
if (!isset($event_words[$event])) { $event = ''; }

$where  = [];
$params = [];
if ($room !== '')  { $where[] = 'room_name = ?'; $params[] = $room; }
if ($mode !== '')  { $where[] = 'mode = ?';      $params[] = $mode; }
if ($event !== '') { $where[] = 'event = ?';     $params[] = $event; }
$sqlWhere = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$rows = [];
$rooms = [];
$total = 0;
$pages = 1;
$db_error = false;

try {
    $stmt = db()->prepare("SELECT COUNT(*) FROM records $sqlWhere");
    $stmt->execute($params);
    $total = (int) $stmt->fetchColumn();

    $pages  = max(1, (int) ceil($total / PAGE_SIZE));
    $pageNo = min($pageNo, $pages);
    $offset = ($pageNo - 1) * PAGE_SIZE;

    $stmt = db()->prepare(
        "SELECT id, recorded_at, room_name, mode, temp, min_temp, max_temp, event, is_test
         FROM records $sqlWhere
         ORDER BY recorded_at DESC, id DESC
         LIMIT " . (int) PAGE_SIZE . " OFFSET " . (int) $offset
    );
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    $rooms = db()->query('SELECT DISTINCT room_name FROM records ORDER BY room_name')->fetchAll(PDO::FETCH_COLUMN);
} catch (Throwable $e) {
    error_log($e->getMessage());
    $db_error = true;
}

function page_url(int $n, string $room, string $mode, string $event): string
{
    $q = array_filter(['room' => $room, 'mode' => $mode, 'event' => $event], static fn($v) => $v !== '');
    $q['page'] = $n;
    return 'records.php?' . http_build_query($q);
}

require __DIR__ . '/partials/header.php';
?>
<section class="card">
    <div class="card-head">
        <h2>Records</h2>
        <form id="export-form" method="post" action="export.php"
              data-confirm="Export ALL records to a CSV file, then permanently delete all records and recent readings? Admins and settings are kept.">
            <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
            <input type="hidden" name="confirm" value="1">
            <button class="btn btn-red" type="submit">Export and clear</button>
        </form>
    </div>

    <form id="filters" method="get" action="records.php">
        <div class="form-row">
            <label>Room
                <select name="room">
                    <option value="">All rooms</option>
                    <?php foreach ($rooms as $r): ?>
                        <option value="<?= h($r) ?>"<?= $room === $r ? ' selected' : '' ?>><?= h($r) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Mode
                <select name="mode">
                    <option value="">All modes</option>
                    <?php foreach ($mode_words as $v => $t): ?>
                        <option value="<?= h($v) ?>"<?= $mode === $v ? ' selected' : '' ?>><?= h($t) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Event
                <select name="event">
                    <option value="">All events</option>
                    <?php foreach ($event_words as $v => $t): ?>
                        <option value="<?= h($v) ?>"<?= $event === $v ? ' selected' : '' ?>><?= h($t) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <button class="btn btn-neutral" type="submit">Filter</button>
            <a class="btn btn-neutral btn-ghost" href="records.php">Reset</a>
        </div>
    </form>
</section>

<section class="card">
    <?php if ($db_error): ?>
        <p class="empty">Cannot load records. Check that MySQL is running and the schema is imported.</p>
    <?php elseif (!$rows): ?>
        <p class="empty">No records found.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table id="records-table" class="table">
                <thead>
                    <tr><th>Time</th><th>Room</th><th>Mode</th><th>Temperature</th><th>Event</th><th></th></tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $r):
                    if ($r['event'] === 'HOURLY' && $r['min_temp'] !== null && $r['max_temp'] !== null) {
                        $temp_text = sprintf('%.1f (%.1f to %.1f)', (float) $r['temp'], (float) $r['min_temp'], (float) $r['max_temp']);
                    } elseif ($r['temp'] !== null) {
                        $temp_text = number_format((float) $r['temp'], 1, '.', '');
                    } else {
                        $temp_text = '--';
                    }
                ?>
                    <tr<?= (int) $r['is_test'] === 1 ? ' class="row-test"' : '' ?>>
                        <td><?= h($r['recorded_at']) ?></td>
                        <td><?= h($r['room_name']) ?></td>
                        <td><?= h($mode_words[$r['mode']] ?? $r['mode']) ?></td>
                        <td><?= h($temp_text) ?> °C</td>
                        <td><?= h($event_words[$r['event']] ?? $r['event']) ?></td>
                        <td><?php if ((int) $r['is_test'] === 1): ?><span class="badge badge-test">TEST</span><?php endif; ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div id="pager">
            <?php if ($pageNo > 1): ?>
                <a class="pager-link" href="<?= h(page_url($pageNo - 1, $room, $mode, $event)) ?>">Previous</a>
            <?php endif; ?>
            <span class="pager-info">Page <?= $pageNo ?> of <?= $pages ?> · <?= $total ?> records</span>
            <?php if ($pageNo < $pages): ?>
                <a class="pager-link" href="<?= h(page_url($pageNo + 1, $room, $mode, $event)) ?>">Next</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>