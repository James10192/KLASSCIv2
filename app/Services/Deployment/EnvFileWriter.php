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
 * Il ne sait ecrire QUE les cles que CleEnvAutorisee declare — mais soyons
 * exacts sur ce que cela protege, parce que sur-promettre puis echouer est
 * pire que ne rien promettre.
 *
 * Ce n'est PAS une frontiere de securite contre un jeton vole : la capacite
 * `cli:admin` ouvre deja /api/cli/pull et /api/cli/composer/install, qui
 * executent du code arbitraire sur l'instance. Qui detient ce jeton a deja
 * tout. La liste blanche est un garde-fou contre l'erreur d'operation et la
 * derive de perimetre — qu'un futur appelant se serve de ce chemin pour poser
 * DB_HOST ou APP_DEBUG « juste une fois ».
 *
 * L'ecriture est atomique : on ecrit un fichier voisin puis on le renomme. Une
 * ecriture en place interrompue — disque plein, processus tue — laisserait un
 * .env tronque, et l'instance ne redemarrerait pas.
 */
class EnvFileWriter
{
    /** Au-dela, les plus anciennes sont supprimees a chaque ecriture. */
    private const SAUVEGARDES_CONSERVEES = 5;

    /** Doit rester identique a la regle de validation de CLIEnvController. */
    private const ALPHABET_VALEUR = '/^[A-Za-z0-9_\-.:\/+=~]+$/';

    public function __construct(
        private readonly string $chemin,
        private readonly string $dossierSauvegardes,
    ) {}

    public static function pourApplication(): self
    {
        return new self(base_path('.env'), storage_path('app/env-backups'));
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
        // Une valeur ne peut occuper qu'UNE ligne physique. Ce n'est pas une
        // commodite, c'est ce qui rend le reste de cette methode correct.
        //
        // Une valeur multiligne s'ecrivait sur N lignes ; a la reecriture
        // suivante, la boucle ci-dessous — qui raisonne en lignes physiques —
        // remplacait la premiere et abandonnait les N-1 autres, orphelines et
        // devenues des entrees .env a part entiere. Deux appels suffisaient
        // ainsi a poser une cle que la liste blanche interdit. Et sans
        // malveillance, un secret colle avec un retour a la ligne finissait par
        // corrompre le fichier au premier remplacement.
        //
        // Refuser le probleme a la frontiere le supprime au lieu de le gerer.
        // Liste POSITIVE plutot que liste de caracteres interdits : les secrets
        // poses ici sont des jetons opaques, et une valeur qui sort de cet
        // alphabet est une erreur, pas un cas a supporter. Cela dispense de
        // tout mecanisme de guillemets — dont la relecture etait justement
        // asymetrique — et rend vraie l'affirmation « une valeur = une ligne ».
        if (preg_match(self::ALPHABET_VALEUR, $valeur) !== 1) {
            throw new RuntimeException(
                'Valeur .env invalide : seuls lettres, chiffres et _-.:/+=~ sont acceptes.'
            );
        }

        $contenu = $this->lire();
        $ligne = $cle.'='.$valeur;

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

        // Une valeur posee par ce service ne porte ni guillemet ni retour a la
        // ligne (voir la garde d'ecrire), donc la ligne physique EST la valeur.
        $valeur = trim($trouve[1]);

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

    /**
     * Sauvegarde horodatee, HORS du depot.
     *
     * Le .env de production contient APP_KEY, le mot de passe MySQL et le jeton
     * de l'API maitre. Depose a la racine du depot, une copie en clair etait
     * certes non suivie, mais pas ignoree non plus : elle apparaissait dans
     * `git status`, a un `git add -A` de l'historique, et surtout
     * `/api/cli/pull` fait un `git stash --include-untracked` qui l'aurait
     * balayee dans les objets de `.git/`. storage/ est ignore en entier.
     *
     * Retention volontairement courte : a soixante ecritures par minute, une
     * accumulation sans limite saturerait le disque d'un hebergement mutualise.
     */
    private function sauvegarder(): void
    {
        $dossier = $this->dossierSauvegardes;

        if (! is_dir($dossier) && ! @mkdir($dossier, 0700, true) && ! is_dir($dossier)) {
            throw new RuntimeException('Dossier de sauvegarde du .env impossible a creer.');
        }

        // Suffixe aleatoire : deux ecritures dans la meme seconde ecrasaient
        // silencieusement la premiere sauvegarde.
        $sauvegarde = $dossier.'/env-'.date('Ymd-His').'-'.bin2hex(random_bytes(3));

        if (@copy($this->chemin, $sauvegarde) === false) {
            throw new RuntimeException('Sauvegarde du .env impossible, ecriture annulee.');
        }

        @chmod($sauvegarde, 0600);
        $this->purger($dossier);
        $this->balayerAncienEmplacement();
    }

    /**
     * Supprime les sauvegardes laissees a la racine du depot par la version
     * precedente de ce service.
     *
     * Transitoire, et a retirer une fois les six instances passees. Chacune de
     * ces copies contient APP_KEY, le mot de passe MySQL et MASTER_API_TOKEN en
     * clair, a un endroit ou `.gitignore` ne les couvrait pas. Le motif est
     * etroit et ces fichiers n'ont jamais eu d'autre auteur que ce service.
     */
    private function balayerAncienEmplacement(): void
    {
        foreach (glob($this->chemin.'.backup-*') ?: [] as $ancienne) {
            @unlink($ancienne);
        }
    }

    private function purger(string $dossier): void
    {
        $fichiers = glob($dossier.'/env-*') ?: [];

        if (count($fichiers) <= self::SAUVEGARDES_CONSERVEES) {
            return;
        }

        sort($fichiers);

        foreach (array_slice($fichiers, 0, count($fichiers) - self::SAUVEGARDES_CONSERVEES) as $vieille) {
            @unlink($vieille);
        }
    }

    private function ecrireAtomiquement(string $contenu): void
    {
        $dossier = dirname($this->chemin);

        if (! is_writable($dossier) || (is_file($this->chemin) && ! is_writable($this->chemin))) {
            throw new RuntimeException('Fichier .env non modifiable (droits insuffisants).');
        }

        $this->sauvegarder();

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

}
