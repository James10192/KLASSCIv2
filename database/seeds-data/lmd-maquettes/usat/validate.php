<?php
$dir = __DIR__;
foreach (glob($dir . '/*.json') as $file) {
    $j = json_decode(file_get_contents($file), true);
    if (!$j) {
        echo basename($file) . " INVALID JSON\n";
        continue;
    }
    echo basename($file) . ":\n";
    $by = [];
    foreach ($j['ues'] as $u) {
        $s = (int) $u['semestre'];
        $by[$s] = ($by[$s] ?? 0) + (int) $u['credit'];
        $ec = 0;
        foreach ($u['ecues'] as $e) {
            $ec += (int) $e['credit_ecue'];
        }
        if ($ec !== (int) $u['credit']) {
            echo "  UE {$u['code']} credit {$u['credit']} vs ECUE {$ec}\n";
        }
    }
    ksort($by);
    foreach ($by as $s => $c) {
        $ok = $c === 30 ? 'OK' : 'FAIL';
        echo "  S{$s}={$c} {$ok}\n";
    }
}
