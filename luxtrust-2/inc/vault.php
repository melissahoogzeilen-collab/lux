<?php
defined('VAULT_APP') || exit;

if (!function_exists('vault_hash_equals')) {
    function vault_hash_equals($a, $b) {
        if (function_exists('hash_equals')) return hash_equals($a, $b);
        if (!is_string($a) || !is_string($b) || strlen($a) !== strlen($b)) return false;
        $res = 0;
        for ($i = 0; $i < strlen($a); $i++) $res |= ord($a[$i]) ^ ord($b[$i]);
        return $res === 0;
    }
}

if (!function_exists('vault_require_keys')) {
    function vault_require_keys() {
        foreach (array('k1.php','k2.php','k3.php','k4.php') as $f) {
            $p = __DIR__ . '/' . $f;
            if (!is_file($p)) return false;
            require_once $p;
        }
        return function_exists('_vk1') && function_exists('_vk2') && function_exists('_vk3') && function_exists('_vk4');
    }
}

if (!function_exists('vault_secret_hex')) {
    function vault_secret_hex() {
        if (!vault_require_keys()) return '';
        $joined = _vk1() . _vk2() . _vk3() . _vk4();
        $step1 = base64_decode($joined, true);
        if ($step1 === false) return '';
        $step2 = strrev($step1);
        $step3 = str_rot13($step2);
        $plain = base64_decode($step3, true);
        if ($plain === false || !preg_match('/^[a-f0-9]{64}$/i', $plain)) return '';
        return strtolower($plain);
    }
}

if (!function_exists('vault_base_key')) {
    function vault_base_key() {
        $hex = vault_secret_hex();
        return ($hex === '') ? false : hash('sha256', $hex, true);
    }
}

if (!function_exists('vault_clean_id')) {
    function vault_clean_id($id) {
        return preg_replace('/[^a-zA-Z0-9_\-]/', '_', (string)$id);
    }
}

if (!function_exists('vault_keys_for')) {
    function vault_keys_for($id) {
        $base = vault_base_key();
        if ($base === false) return false;
        $clean = vault_clean_id($id);
        return array(
            'enc' => hash_hmac('sha256', 'enc:' . $clean, $base, true),
            'mac' => hash_hmac('sha256', 'mac:' . $clean, $base, true)
        );
    }
}

if (!function_exists('vault_file_path')) {
    function vault_file_path($id) {
        return dirname(__DIR__) . '/secure/' . vault_clean_id($id) . '.vault.php';
    }
}

if (!function_exists('vault_text')) {
    function vault_text($id) {
        $path = vault_file_path($id);
        if (!is_file($path)) return '';

        $data = include $path;
        if (!is_array($data)) return '';

        foreach (array('v','alg','cmp','iv','ct','mac') as $k) {
            if (!array_key_exists($k, $data)) return '';
        }

        if ((int)$data['v'] !== 1 || $data['alg'] !== 'AES-256-CBC-HMAC-SHA256') return '';

        $keys = vault_keys_for($id);
        if ($keys === false) return '';

        $msg = $data['v'] . '|' . $data['alg'] . '|' . $data['cmp'] . '|' . $data['iv'] . '|' . $data['ct'];
        $calc = hash_hmac('sha256', $msg, $keys['mac']);
        if (!vault_hash_equals($calc, $data['mac'])) return '';

        $iv = base64_decode($data['iv'], true);
        $ct = base64_decode($data['ct'], true);
        if ($iv === false || $ct === false || strlen($iv) !== 16) return '';

        $pt = openssl_decrypt($ct, 'AES-256-CBC', $keys['enc'], OPENSSL_RAW_DATA, $iv);
        if ($pt === false) return '';

        if ((int)$data['cmp'] === 1) {
            if (!function_exists('gzinflate')) return '';
            $out = @gzinflate($pt);
            return ($out === false) ? '' : $out;
        }

        return $pt;
    }
}

if (!function_exists('vault_block')) {
    function vault_block($id) {
        return vault_text($id);
    }
}

if (!function_exists('vault_json')) {
    function vault_json($id) {
        $txt = vault_text($id);
        if ($txt === '') return null;
        $j = json_decode($txt, true);
        return is_array($j) ? $j : null;
    }
}
