<?php

$content = file_get_contents('routes/web.php');
$lines = explode("\n", $content);
$newLines = [];
$skipLines = false;

foreach ($lines as $i => $line) {
    // Start skipping from the duplicate attendance routes
    if (strpos($line, '// Émargement enseignant (présence par code du jour)') !== false) {
        $skipLines = true;
        continue;
    }

    // Skip the duplicate attendance routes
    if ($skipLines) {
        continue;
    }

    $newLines[] = $line;
}

file_put_contents('routes/web.php', implode("\n", $newLines));
echo "Removed duplicate attendance routes at end of file\n";
