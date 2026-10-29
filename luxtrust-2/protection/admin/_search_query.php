<?php
// admin/_search_query.php
// Reusable helpers to build a safe WHERE clause for visitor search.
// Usage:
//   require __DIR__ . '/_search_query.php';
//   [$whereSql, $params] = build_visitor_where($_GET);
//   $sql = "SELECT * FROM visitors " . $whereSql . " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";

declare(strict_types=1);

/**
 * Parse a general query string like:
 *   "ip:1.2.3.4 ua:Chrome country:US bot:1 blocked:0 score:20..80 date:2025-08-01..2025-08-17 reason:captcha"
 * as well as free text fragments that match IP, UA, or reason via LIKE.
 *
 * Also supports standalone GET params:
 *   ip, ua, country, bot, blocked, score_min, score_max, date_from, date_to
 * Operator:
 *   op=and|or   (default and)
 */
function build_visitor_where(array $in): array {
    $clauses = [];
    $params = [];

    $op = strtolower(trim((string)($in['op'] ?? 'and')));
    $logic = ($op === 'or') ? ' OR ' : ' AND ';

    $add = function(string $sql, string $paramKey, $paramVal) use (&$clauses, &$params) {
        $clauses[] = $sql;
        $params[$paramKey] = $paramVal;
    };

    // Field helpers
    $norm = fn($s) => trim((string)$s);

    // Field-specific GET params
    if (!empty($in['ip']))       $add("ip_address LIKE :ip", ':ip', str_replace('*', '%', $norm($in['ip'])));
    if (!empty($in['ua']))       $add("user_agent LIKE :ua", ':ua', '%'.$norm($in['ua']).'%');
    if (!empty($in['country']))  $add("country = :country", ':country', strtoupper(substr($norm($in['country']),0,2)));
    if (isset($in['bot']) && $in['bot'] !== '')       $add("is_bot = :bot", ':bot', (int)($in['bot'] ? 1 : 0));
    if (isset($in['blocked']) && $in['blocked'] !== '') $add("blocked = :blocked", ':blocked', (int)($in['blocked'] ? 1 : 0));

    // Score range
    if (isset($in['score_min']) && $in['score_min'] !== '') $add("behavioral_score >= :score_min", ':score_min', (int)$in['score_min']);
    if (isset($in['score_max']) && $in['score_max'] !== '') $add("behavioral_score <= :score_max", ':score_max', (int)$in['score_max']);

    // Date range
    if (!empty($in['date_from'])) $add("created_at >= :date_from", ':date_from', $norm($in['date_from'])." 00:00:00");
    if (!empty($in['date_to']))   $add("created_at <= :date_to",   ':date_to',   $norm($in['date_to'])." 23:59:59");

    // Free-form "q" parser
    $q = $norm($in['q'] ?? '');
    if ($q !== '') {
        // Tokenize by whitespace
        $tokens = preg_split('/\s+/', $q, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $freeClauses = [];

        foreach ($tokens as $idx => $t) {
            $kv = explode(':', $t, 2);
            if (count($kv) === 2) {
                [$k,$v] = [strtolower($kv[0]), trim($kv[1])];
                if ($v === '') continue;

                switch ($k) {
                    case 'ip':
                        $pk = ":q_ip_$idx";
                        $freeClauses[] = "ip_address LIKE $pk";
                        $params[$pk] = str_replace('*','%',$v);
                        break;
                    case 'ua':
                        $pk = ":q_ua_$idx";
                        $freeClauses[] = "user_agent LIKE $pk";
                        $params[$pk] = "%$v%";
                        break;
                    case 'country':
                        $pk = ":q_country_$idx";
                        $freeClauses[] = "country = $pk";
                        $params[$pk] = strtoupper(substr($v,0,2));
                        break;
                    case 'reason':
                        $pk = ":q_reason_$idx";
                        $freeClauses[] = "block_reason LIKE $pk";
                        $params[$pk] = "%$v%";
                        break;
                    case 'id':
                        $pk = ":q_id_$idx";
                        $freeClauses[] = "id = $pk";
                        $params[$pk] = (int)$v;
                        break;
                    case 'bot':
                        $pk = ":q_bot_$idx";
                        $freeClauses[] = "is_bot = $pk";
                        $params[$pk] = (int)($v ? 1 : 0);
                        break;
                    case 'blocked':
                        $pk = ":q_blocked_$idx";
                        $freeClauses[] = "blocked = $pk";
                        $params[$pk] = (int)($v ? 1 : 0);
                        break;
                    case 'score':
                        // score:50..80 or score:>=50 or score:<=90 or score:70
                        if (preg_match('/^(\d+)\.\.(\d+)$/', $v, $m)) {
                            $pk1 = ":q_score_min_$idx"; $pk2 = ":q_score_max_$idx";
                            $freeClauses[] = "(behavioral_score BETWEEN $pk1 AND $pk2)";
                            $params[$pk1] = (int)$m[1];
                            $params[$pk2] = (int)$m[2];
                        } elseif (preg_match('/^(>=|<=|>|<)\s*(\d+)$/', $v, $m)) {
                            $op2 = $m[1]; $pk = ":q_score_cmp_$idx";
                            $freeClauses[] = "behavioral_score $op2 $pk";
                            $params[$pk] = (int)$m[2];
                        } elseif (ctype_digit($v)) {
                            $pk = ":q_score_eq_$idx";
                            $freeClauses[] = "behavioral_score = $pk";
                            $params[$pk] = (int)$v;
                        }
                        break;
                    case 'date':
                        // date:YYYY-MM-DD..YYYY-MM-DD
                        if (preg_match('/^(\d{4}-\d{2}-\d{2})\.\.(\d{4}-\d{2}-\d{2})$/', $v, $m)) {
                            $pk1 = ":q_date_from_$idx"; $pk2 = ":q_date_to_$idx";
                            $freeClauses[] = "(created_at BETWEEN $pk1 AND $pk2)";
                            $params[$pk1] = $m[1] . " 00:00:00";
                            $params[$pk2] = $m[2] . " 23:59:59";
                        }
                        break;
                    case 'fp':
                    case 'fingerprint':
                        $pk = ":q_fp_$idx";
                        $freeClauses[] = "browser_fingerprint = $pk";
                        $params[$pk] = $v;
                        break;
                    case 'device':
                        $pk = ":q_device_$idx";
                        $freeClauses[] = "device_id = $pk";
                        $params[$pk] = $v;
                        break;
                    case 'ja3':
                        $pk = ":q_ja3_$idx";
                        $freeClauses[] = "tls_ja3 = $pk";
                        $params[$pk] = $v;
                        break;
                    default:
                        // unknown field: treat as free text
                        $pk = ":q_free_$idx";
                        $freeClauses[] = "(ip_address LIKE $pk OR user_agent LIKE $pk OR block_reason LIKE $pk)";
                        $params[$pk] = "%$t%";
                }
            } else {
                // Free word: search in ip, UA, reason
                $pk = ":q_any_$idx";
                $freeClauses[] = "(ip_address LIKE $pk OR user_agent LIKE $pk OR block_reason LIKE $pk)";
                $params[$pk] = "%$t%";
            }
        }

        if ($freeClauses) {
            // default within-q logic is AND between tokens when op=and, OR when op=or
            $clauses[] = '(' . implode($logic, $freeClauses) . ')';
        }
    }

    $sql = $clauses ? (' WHERE ' . implode($logic, $clauses)) : '';
    return [$sql, $params];
}

/** Safe sort whitelist. */
function build_sort(string $sort): string {
    $sort = strtolower(trim($sort));
    $map = [
        'created_desc' => 'created_at DESC',
        'created_asc'  => 'created_at ASC',
        'score_desc'   => 'behavioral_score DESC',
        'score_asc'    => 'behavioral_score ASC',
        'ip_asc'       => 'ip_address ASC',
        'ip_desc'      => 'ip_address DESC',
    ];
    return $map[$sort] ?? $map['created_desc'];
}
