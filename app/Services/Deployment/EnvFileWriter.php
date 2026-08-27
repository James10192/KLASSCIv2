<?php

namespace App\Services\Deployment;

use RuntimeException;

/**
 * Ecriture d'une cle dans le .env de l'instance, sans casser le fichier.
 *
 * Ce service existe pour une seule raison : poser a distance des secrets
 * d'integration que le CLI est le seul chemin praticable pour installer — il
 * n'y a pas de SSH vers l'hebergement, et passer par le terminal cPanel a la
 * main sur six ecoles est une source d'erreur en soi.
 *
 * Il ne sait ecrire QUE les cles que CleEnvAutorisee declare. Ce n'est pas une
 * precaution de forme : un ecrivain de .env generique, atteignable par jeton,
 * equivaut a une prise de controle de l'instance — il suffirait de detourner
 * DB_HOST vers une machine tierce, de faire tourner APP_KEY pour rendre
 * illisible tout ce qui est chiffre, ou de rediriger MAIL_* pour intercepter
 * les reinitialisations de mot de passe.
 *
 * L'ecriture est atomique : on ecrit un fichier voisin puis on le renomme. Une
 * ecriture en place interrompue — disque plein, processus tue — laisserait un
 * .env tronque, et l'instance ne redemarrerait pas.
 */
class EnvFileWriter
{
    public function __construct(private readonly string $chemin) {}

    public static function pourApplication(): self
    {
        return new self(base_path('.env'));
    }

    /**
     * Ecrit une cle. Retourne true si la cle a ete creee, false si remplacee.
     *
     * @throws RuntimeException si le fichier est absent, illisible ou non
     *                          modifiable — jamais en silence : un secret qu'on
     *                          croit pose et qui ne l'est pas est pire que pas
     *                          de secret du tout.
     */
    public function ecrire(string $cle, string $valeur): bool
    {
        $contenu = $this->lire();
        $ligne = $cle.'='.$this->echapper($valeur);

        $lignes = preg_split('/\R/', $contenu);
        $trouvee = false;
        $resultat = [];

        foreach ($lignes as $courante) {
            // Une cle peut figurer plusieurs fois — phpdotenv retient alors la
            // premiere. On remplace donc la premiere et on supprime les
            // suivantes, sinon la valeur effective resterait l'ancienne.
            if (preg_match('/^\s*'.preg_quote($cle, '/').'\s*=/', $courante) === 1) {
                if ($trouvee) {
                    continue;
                }

                $resultat[] = $ligne;
                $trouvee = true;

                continue;
            }

            $resultat[] = $courante;
        }

        if (! $trouvee) {
            // Ligne vide de separation seulement si le fichier n'en finit pas
            // deja par une : sinon chaque ecriture creuserait un trou de plus.
            if ($resultat !== [] && trim(end($resultat)) !== '') {
                $resultat[] = '';
            }

            $resultat[] = $ligne;
        }

        $this->ecrireAtomiquement(implode(PHP_EOL, $resultat));

        return ! $trouvee;
    }

    /**
     * Empreinte de la valeur actuelle, ou null si la cle n'est pas posee.
     *
     * Sert a comparer les deux cotes d'un secret partage — l'instance et le
     * site vitrine — sans jamais transporter le secret lui-meme.
     */
    public function empreinte(string $cle): ?string
    {
        if (preg_match('/^\s*'.preg_quote($cle, '/').'\s*=(.*)$/m', $this->lire(), $trouve) !== 1) {
            return null;
        }

        $valeur = $this->desechapper(trim($trouve[1]));

        return $valeur === '' ? null : substr(hash('sha256', $valeur), 0, 12);
    }

    private function lire(): string
    {
        if (! is_file($this->chemin)) {
            throw new RuntimeException('Fichier .env introuvable sur cette instance.');
        }

        $contenu = @file_get_contents($this->chemin);

        if ($contenu === false) {
            throw new RuntimeException('Fichier .env illisible sur cette instance.');
        }

        return $contenu;
    }

    private function ecrireAtomiquement(string $contenu): void
    {
        $dossier = dirname($this->chemin);

        if (! is_writable($dossier) || (is_file($this->chemin) && ! is_writable($this->chemin))) {
            throw new RuntimeException('Fichier .env non modifiable (droits insuffisants).');
        }

        // Sauvegarde horodatee avant toute modification. Elle ne coute rien et
        // c'est la seule chose qui permette de revenir en arriere si la valeur
        // posee se revele fausse.
        $sauvegarde = $this->chemin.'.backup-'.date('Ymd-His');

        if (@copy($this->chemin, $sauvegarde) === false) {
            throw new RuntimeException('Sauvegarde du .env impossible, ecriture annulee.');
        }

        $temporaire = $this->chemin.'.tmp-'.bin2hex(random_bytes(4));

        if (@file_put_contents($temporaire, $contenu, LOCK_EX) === false) {
            @unlink($temporaire);

            throw new RuntimeException('Ecriture du .env temporaire impossible.');
        }

        @chmod($temporaire, 0600);

        // rename() est atomique sur le meme systeme de fichiers sous Unix.
        // Sous Windows il echoue si la cible existe, d'ou le retrait prealable.
        if (PHP_OS_FAMILY === 'Windows') {
            @unlink($this->chemin);
        }

        if (@rename($temporaire, $this->chemin) === false) {
            @unlink($temporaire);

            throw new RuntimeException('Remplacement du .env impossible.');
        }
    }

    /**
     * Les secrets que ce service pose n'ont ni espace ni guillemet, mais on ne
     * parie pas la-dessus : une valeur non echappee couperait la ligne et
     * rendrait le fichier incoherent.
     */
    private function echapper(string $valeur): string
    {
        if (preg_match('/^[A-Za-z0-9_\-.:\/+=]*$/', $valeur) === 1) {
            return $valeur;
        }

        return '"'.str_replace(['\\', '"'], ['\\\\', '\\"'], $valeur).'"';
    }

    private function desechapper(string $valeur): string
    {
        if (strlen($valeur) >= 2 && $valeur[0] === '"' && str_ends_with($valeur, '"')) {
            return str_replace(['\\"', '\\\\'], ['"', '\\'], substr($valeur, 1, -1));
        }

        return $valeur;
    }
}
