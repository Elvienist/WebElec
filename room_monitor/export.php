<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('POST only.');
}

require_login();
csrf_check();

function export_fail(string $message): void
{
    flash_set('error', $message);
    header('Location: records.php');
    exit;
}

// Stop spreadsheet formulas from running if a cell starts with = + - @
function csv_safe(string $value): string
{
    return ($value !== '' && strpos('=+-@', $value[0]) !== false) ? "'" . $value : $value;
}

function csv_temp($value): string
{
    return $value === null ? '' : number_format((float) $value, 1, '.', '');
}

if (($_POST['confirm'] ?? '') !== '1') {
    export_fail('Export was not confirmed.');
}

try {
    $pdo = db();

    // 1. Anything to export? Remember the highest id.
    $row = $pdo->query('SELECT COUNT(*) AS c, MAX(id) AS m FROM records')->fetch();
    if ((int) $row['c'] === 0) {
        export_fail('Nothing to export');
    }
    $maxId = (int) $row['m'];

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM records WHERE id <= ?');
    $stmt->execute([$maxId]);
    $expected = (int) $stmt->fetchColumn();

    // 2. Build the CSV on the server first.
    $dir = __DIR__ . '/exports';
    if (!is_dir($dir) && !mkdir($dir, 0775, true)) {
        export_fail('Cannot create the exports folder. Nothing was deleted.');
    }
    $name = 'records_' . date('Ymd_His') . '.csv';
    $path = $dir . '/' . $name;

    $fh = fopen($path, 'wb');
    if (!$fh) {
        export_fail('Cannot write the export file. Nothing was deleted.');
    }
    fwrite($fh, "\xEF\xBB\xBF");
    fputcsv($fh, ['Time', 'Room', 'Mode', 'Temperature', 'Min', 'Max', 'Event', 'Test'], ',', '"', '\\');

    $sel = $pdo->prepare(
        'SELECT recorded_at, room_name, mode, temp, min_temp, max_temp, event, is_test
         FROM records WHERE id <= ? ORDER BY recorded_at ASC, id ASC'
    );
    $sel->execute([$maxId]);
    $written = 0;
    while ($r = $sel->fetch()) {
        fputcsv($fh, [
            $r['recorded_at'],
            csv_safe((string) $r['room_name']),
            $r['mode'],
            csv_temp($r['temp']),
            csv_temp($r['min_temp']),
            csv_temp($r['max_temp']),
            $r['event'],
            (int) $r['is_test'] === 1 ? 'TEST' : '',
        ], ',', '"', '\\');
        $written++;
    }
    fclose($fh);

    // 3. Verify the saved file before deleting anything.
    $counted = -1;
    if (is_file($path) && filesize($path) > 0) {
        $check = fopen($path, 'rb');
        fread($check, 3); // skip BOM
        $counted = -1;    // header line is not a data row
        while (fgetcsv($check, 0, ',', '"', '\\') !== false) {
            $counted++;
        }
        fclose($check);
    }
    if ($written !== $expected || $counted !== $expected) {
        export_fail('The export file could not be verified. Nothing was deleted.');
    }

    // 4. Send the download, then delete what was exported.
    ignore_user_abort(true);
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $name . '"');
    header('Content-Length: ' . filesize($path));
    header('Cache-Control: no-store');
    readfile($path);
    flush();

    $pdo->prepare('DELETE FROM records WHERE id <= ?')->execute([$maxId]);
    $pdo->exec('DELETE FROM readings');
    flash_set('success', "Exported $expected records to exports/$name and cleared them.");
    exit;
} catch (Throwable $e) {
    error_log($e->getMessage());
    if (!headers_sent()) {
        export_fail('Export failed. Nothing was deleted.');
    }
    exit;
}