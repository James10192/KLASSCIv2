<?php

/**
 * Script diagnostique simple pour vérifier les parents liés à un étudiant.
 *
 * Usage  : php scripts/check_student_parents.php
 * Pré-requis : le serveur MariaDB/MySQL doit être accessible depuis cette machine.
 */

$dbHost = envDefault('DB_HOST', '127.0.0.1');
$dbPort = envDefault('DB_PORT', '3306');
$dbName = envDefault('DB_DATABASE', 'esbtp-abidjan-db');
$dbUser = envDefault('DB_USERNAME', 'laravel');
$dbPass = envDefault('DB_PASSWORD', 'devpass');

$matricule = $argv[1] ?? 'MESBTP23-0039';

$dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $dbHost, $dbPort, $dbName);

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    fwrite(STDERR, "Connexion échouée : {$e->getMessage()}\n");
    exit(1);
}

echo "Connexion réussie.\n";

$sql = <<<SQL
SELECT e.id AS etudiant_id,
       e.nom,
       e.prenoms,
       e.matricule,
       p.id AS parent_id,
       p.nom AS parent_nom,
       p.prenoms AS parent_prenoms,
       pivot.relation,
       pivot.is_tuteur
FROM esbtp_etudiants e
LEFT JOIN esbtp_etudiant_parent pivot ON pivot.etudiant_id = e.id
LEFT JOIN esbtp_parents p ON p.id = pivot.parent_id
WHERE e.matricule = :matricule
SQL;

$stmt = $pdo->prepare($sql);
$stmt->execute(['matricule' => $matricule]);
$rows = $stmt->fetchAll();

if (empty($rows)) {
    echo "Aucun étudiant trouvé avec le matricule {$matricule}.\n";
    exit(0);
}

echo "Étudiant : {$rows[0]['nom']} {$rows[0]['prenoms']} (ID {$rows[0]['etudiant_id']})\n";

$hasParent = false;
foreach ($rows as $row) {
    if (!$row['parent_id']) {
        continue;
    }
    $hasParent = true;
    $relation = $row['relation'] ?: 'Non renseignée';
    $isTuteur = (int) $row['is_tuteur'] === 1 ? 'Oui' : 'Non';
    echo "- Parent ID {$row['parent_id']} : {$row['parent_nom']} {$row['parent_prenoms']} | Relation : {$relation} | Tuteur : {$isTuteur}\n";
}

if (!$hasParent) {
    echo "Aucun parent associé.\n";
}

/**
 * Lecture d'une variable d'environnement avec fallback.
 */
function envDefault(string $key, string $default): string
{
    $value = getenv($key);
    return $value !== false ? $value : $default;
}

