<?php
// DB-only saver with smart handling:
// - If 'countries' is present, compare with DB to decide whether to SYNC (add+delete) or ADD-only.
//   * If the posted list looks like "only new picks" (excludes all existing), treat as ADD-only so we don't wipe.
//   * If it looks like a removal (e.g., after clicking × or Clear), we SYNC (add new + delete missing).
// - If 'countries' not present, honor 'add' and 'remove' as deltas.
// - Clear: countries present & empty & no add/remove => delete all.
header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';

function normalize_list($v): array {
    if ($v === null) return [];
    if (is_string($v)) {
        // allow comma/space separated strings
        $v = preg_split('/[,\s]+/', $v, -1, PREG_SPLIT_NO_EMPTY);
    }
    if (!is_array($v)) return [];
    $out = [];
    foreach ($v as $c) {
        if (is_array($c)) continue;
        $c = strtoupper(trim((string)$c));
        if (preg_match('/^[A-Z]{2,3}$/', $c)) $out[] = $c; // ISO-2/ISO-3
    }
    return array_values(array_unique($out));
}

// Read JSON or form
$raw  = file_get_contents('php://input');
$json = json_decode($raw, true);
$data = is_array($json) ? $json : $_POST;

$countries_raw = $data['countries'] ?? $data['allowedCountries'] ?? null; // sometimes UI sends just new picks here
$add_raw       = $data['add']       ?? null;
$remove_raw    = $data['remove']    ?? null;

$posted_countries = normalize_list($countries_raw);
$add_list         = normalize_list($add_raw);
$remove_list      = normalize_list($remove_raw);

$countries_key_present = is_array($json)
    ? (array_key_exists('countries', $json) || array_key_exists('allowedCountries', $json))
    : (isset($_POST['countries']) || isset($_POST['allowedCountries']));

try {
    // Load current DB set
    $stmt = $pdo->query("SELECT country_code FROM allowed_countries");
    $current = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];

    $pdo->beginTransaction();

    if ($countries_key_present) {
        // Heuristics:
        // additions = posted - current
        // deletions = current - posted
        $additions = array_values(array_diff($posted_countries, $current));
        $deletions = array_values(array_diff($current, $posted_countries));

        if (empty($posted_countries) && empty($add_list) && empty($remove_list)) {
            // CLEAR button (explicit empty final set)
            if (!empty($current)) {
                $pdo->exec("DELETE FROM allowed_countries");
            }
        } else {
            // Special case: UI posted only new items and omitted existing ones
            // -> avoid wiping: treat as ADD-only if the posted items are all NEW
            $posted_all_new = !empty($additions) && count($deletions) === count($current);

            if ($posted_all_new) {
                // ADD-only
                if (!empty($additions)) {
                    $ph = implode(',', array_fill(0, count($additions), '(?)'));
                    $stmt = $pdo->prepare("INSERT IGNORE INTO allowed_countries (country_code) VALUES $ph");
                    $stmt->execute($additions);
                }
            } else {
                // SYNC: add missing and delete removed
                if (!empty($additions)) {
                    $ph = implode(',', array_fill(0, count($additions), '(?)'));
                    $stmt = $pdo->prepare("INSERT IGNORE INTO allowed_countries (country_code) VALUES $ph");
                    $stmt->execute($additions);
                }
                if (!empty($deletions)) {
                    $ph = implode(',', array_fill(0, count($deletions), '?'));
                    $stmt = $pdo->prepare("DELETE FROM allowed_countries WHERE country_code IN ($ph)");
                    $stmt->execute($deletions);
                }
            }
        }
    } else {
        // Pure delta mode (explicit add/remove)
        if (!empty($add_list)) {
            $ph = implode(',', array_fill(0, count($add_list), '(?)'));
            $stmt = $pdo->prepare("INSERT IGNORE INTO allowed_countries (country_code) VALUES $ph");
            $stmt->execute($add_list);
        }
        if (!empty($remove_list)) {
            $ph = implode(',', array_fill(0, count($remove_list), '?'));
            $stmt = $pdo->prepare("DELETE FROM allowed_countries WHERE country_code IN ($ph)");
            $stmt->execute($remove_list);
        }
        if (empty($add_list) && empty($remove_list)) {
            // no-op
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Nothing to save']);
            return;
        }
    }

    // Return updated set
    $stmt = $pdo->query("SELECT country_code FROM allowed_countries ORDER BY country_code ASC");
    $updated = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];

    $pdo->commit();
    echo json_encode(['success' => true, 'countries' => $updated]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
