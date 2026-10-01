<?php

namespace App\Domain\Reglages;

use App\Domain\Notifications\PhoneNormalizer;
use App\Mail\Transport\MailerDeLEcole;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\Setting;
use App\Models\SettingsBackup;
use App\Services\Admissions\InscriptionWorkflowSettings;
use App\Services\Documents\CodeQrDocument;
use App\Services\Inscription\PortailCandidaturePublication;
use App\Services\Reinscription\PortailReinscriptionService;
use App\Services\RendezVous\RendezVousReglages;
use App\Services\TelephoneSettingsService;
use App\Services\TenantScolariteSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Changer la valeur d'un ou plusieurs réglages d'établissement, avec les gardes
 * de l'écran des paramètres.
 *
 * Trois appelants, une seule règle :
 *  - l'écran des paramètres : type, bornes et règles de chaque réglage
 *    (normaliserSelonLeReglage) et contrôles croisés (couleurs, dates, année
 *    visée, candidatures) ;
 *  - le CLI : `POST /api/cli/settings` (refus, bascules, contrôles croisés,
 *    écriture) et `PUT /api/cli/settings/{key}` (refus et contrôles croisés ;
 *    il garde le droit de créer une clé absente, pour le provisionnement) ;
 *  - Nanan : tout cela, plus une liste fermée de clés, sans jamais créer de clé.
 *
 * Ce que Nanan ne touche jamais, et pourquoi (refusPourNanan) :
 *  - un secret (clé d'API, mot de passe, jeton) : il ne transite pas par une conversation ;
 *  - l'envoi des e-mails (MailPulse, transport) : onglet dédié, contrôles propres ;
 *  - les barèmes JSON (assiduité, mentions, appréciations) et la table SAARI : structure validée par l'écran ;
 *  - les fichiers : le logo et la signature du directeur passent par ImageDeReglage, depuis une pièce jointe ;
 *  - ce qui ouvre des droits à un rôle (scolarité, agent d'inscription, caisse) : comptes et droits ;
 *  - la prise de rendez-vous : son propre écran et sa propre permission ;
 *  - toute clé hors de la liste ci-dessous, et toute clé absente de la base (Nanan ne crée aucun réglage).
 */
class ModificationDeReglages
{
    public const MAX = 10;

    /** Motifs de clés dont la valeur ne sort ni n'entre jamais à distance. */
    public const MOTIFS_SECRETS = ['token', 'secret', 'password', 'mot_de_passe', 'api_key', 'apikey', 'cle_api'];

    /** Réglages de l'écran que Nanan peut changer, en plus des préfixes ci-dessous. */
    private const CLES_NANAN = [
        'school_name', 'school_acronym', 'school_address', 'school_city', 'school_country', 'school_email',
        'school_phone', 'school_mobile', 'school_postal_code', 'school_website',
        'director_name', 'director_title',
        PhoneNormalizer::CLE_INDICATIF, PhoneNormalizer::CLE_PREFIXES,
        CodeQrDocument::REGLAGE_ACTIF,
        PortailReinscriptionService::REGLAGE_OUVERTURE, PortailReinscriptionService::REGLAGE_FERMETURE,
        PortailReinscriptionService::REGLAGE_ANNEE_CIBLE,
        PortailCandidaturePublication::REGLAGE_ACTIF, PortailCandidaturePublication::REGLAGE_PHYSIQUES,
    ];

    private const PREFIXES_NANAN = ['pdf_', 'bulletin_', 'lmd_', 'certificat_show_', 'conduite_'];

    /** Clés de l'écran que la liste de préfixes couvrirait, mais qui gardent leur propre écran. */
    private const EXCLUES_NANAN = ['attendance_note_rules', 'pdf_logo', 'pdf_watermark_image'];

    /**
     * Bornes des réglages numériques, déclarées une fois : l'écran les affiche
     * (min / max des champs) et les fait respecter, comme le CLI et Nanan.
     *
     * @var array<string, array{0: int|float, 1: int|float}>
     */
    public const BORNES = [
        'pdf_logo_size' => [20, 120],
        'pdf_font_size' => [8, 16],
        'pdf_margin_top' => [0, 50],
        'pdf_margin_bottom' => [0, 50],
        'pdf_margin_left' => [0, 50],
        'pdf_margin_right' => [0, 50],
        'pdf_signature_height' => [40, 200],
        'pdf_watermark_opacity' => [0.02, 0.30],
        'pdf_watermark_rotation' => [-90, 90],
    ];

    /**
     * Deux clés pour une même case : l'écran écrit les deux, le bulletin lit le
     * pluriel. Les changer séparément ferait mentir l'une ou l'autre.
     */
    private const JUMELLES = ['bulletin_show_signature' => 'bulletin_show_signatures', 'bulletin_show_signatures' => 'bulletin_show_signature'];

    /** Bascules strictement booléennes (CLI) : écrites 1 / 0. */
    private const BASCULES = [
        TenantScolariteSettings::VERIFICATION_CONTACT,
        TenantScolariteSettings::VERIFICATION_WHATSAPP_INVERSE,
    ];

    public static function estSensible(string $cle): bool
    {
        $cle = mb_strtolower($cle);
        foreach (self::MOTIFS_SECRETS as $motif) {
            if (str_contains($cle, $motif)) {
                return true;
            }
        }

        return false;
    }

    /** Refus de toute écriture à distance (CLI comme Nanan). */
    public function refusDistant(string $cle): ?string
    {
        if (in_array($cle, MailerDeLEcole::REGLAGES_RESERVES_A_L_ECRAN, true)) {
            return sprintf("« %s » décide par où partent les e-mails de l'école : il se change depuis l'écran des paramètres (onglet MailPulse).", $cle);
        }
        if (self::estSensible($cle)) {
            return "Cette clé évoque un secret : elle se change depuis l'écran de configuration.";
        }

        return null;
    }

    /** Ce que Nanan ne propose jamais de changer (voir l'en-tête de la classe). */
    public function refusPourNanan(string $cle, ?Setting $reglage): ?string
    {
        if (($refus = $this->refusDistant($cle)) !== null) {
            return $refus;
        }
        if (str_starts_with($cle, 'mailpulse') || str_starts_with($cle, 'mail_') || str_starts_with($cle, 'assistant')) {
            return "« {$cle} » touche l'envoi des messages ou l'assistant : il se règle sur son onglet de l'écran des paramètres.";
        }
        // Un chemin d'image ne s'écrit jamais en texte : il passe par le fichier.
        if (array_key_exists($cle, ImageDeReglage::DOSSIERS) || $cle === 'pdf_signature_secretary') {
            return array_key_exists($cle, ImageDeReglage::NANAN)
                ? "« {$cle} » est une image : joignez-la et utilisez proposer_image_reglage."
                : "« {$cle} » est une image : elle se change sur l'écran des paramètres.";
        }
        $permise = in_array($cle, self::CLES_NANAN, true) || in_array($cle, InscriptionWorkflowSettings::cles(), true)
            || collect(self::PREFIXES_NANAN)->contains(fn (string $p) => str_starts_with($cle, $p));
        if (! $permise || in_array($cle, self::EXCLUES_NANAN, true)) {
            return "« {$cle} » ne se change pas par Nanan : utilisez l'écran des paramètres.";
        }
        if ($reglage === null) {
            return "Réglage « {$cle} » introuvable. Nanan ne crée pas de réglage : vérifiez la clé avec lire_reglages.";
        }
        if (in_array($reglage->type, ['file', 'json', 'array'], true)) {
            return $reglage->type === 'file'
                ? "« {$cle} » est une image : joignez-la et utilisez proposer_image_reglage."
                : "« {$cle} » est un barème structuré : il se règle sur l'écran des paramètres.";
        }

        return null;
    }

    /**
     * Bascules et choix fermés, comme le CLI les a toujours lus.
     *
     * @return array{0: ?string, 1: ?string} [valeur normalisée, refus]
     */
    public function normaliserBascules(string $cle, ?string $valeur): array
    {
        if (in_array($cle, self::BASCULES, true) || in_array($cle, InscriptionWorkflowSettings::booleens(), true)) {
            $booleen = filter_var($valeur, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($booleen === null) {
                return [$valeur, sprintf('« %s » attend un booléen (1/0, true/false).', $cle)];
            }
            $valeur = $booleen ? '1' : '0';
        }

        $choix = InscriptionWorkflowSettings::choix();
        if (array_key_exists($cle, $choix) && ! array_key_exists((string) $valeur, $choix[$cle])) {
            return [$valeur, sprintf('« %s » attend une de ces valeurs : %s.', $cle, implode(', ', array_keys($choix[$cle])))];
        }

        return [$valeur, null];
    }

    /**
     * Type, bornes et règles de validation d'UN réglage : la boucle de l'écran
     * l'appelle pour chaque champ modifié, le CLI et Nanan aussi. Un champ
     * facultatif peut être vidé ; une valeur structurée (barème JSON) n'est
     * jugée que sur les règles du réglage.
     *
     * @return array{0: mixed, 1: ?string} [valeur normalisée, refus]
     */
    public function normaliserSelonLeReglage(Setting $reglage, mixed $valeur): array
    {
        if ($valeur !== null && ! is_scalar($valeur)) {
            return [$valeur, $this->refusDesRegles($reglage, $valeur)];
        }

        $valeur = is_bool($valeur) ? ($valeur ? '1' : '0') : trim((string) ($valeur ?? ''));
        [$valeur, $refus] = $this->normaliserBascules($reglage->key, $valeur);
        if ($refus !== null) {
            return [(string) $valeur, $refus];
        }

        if ($valeur === '') {
            return $reglage->is_required ? ['', "« {$reglage->key} » est obligatoire : il ne peut pas être vidé."] : ['', null];
        }

        switch ($reglage->type) {
            case 'boolean':
                $booleen = filter_var($valeur, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                if ($booleen === null) {
                    return [$valeur, "« {$reglage->key} » attend oui ou non."];
                }
                $valeur = $booleen ? '1' : '0';
                break;
            case 'integer':
                if (filter_var($valeur, FILTER_VALIDATE_INT) === false) {
                    return [$valeur, "« {$reglage->key} » attend un nombre entier."];
                }
                break;
            case 'float':
                if (! is_numeric(str_replace(',', '.', $valeur))) {
                    return [$valeur, "« {$reglage->key} » attend un nombre."];
                }
                $valeur = str_replace(',', '.', $valeur);
                break;
        }

        if (isset(self::BORNES[$reglage->key])) {
            [$min, $max] = self::BORNES[$reglage->key];
            $nombre = str_replace(',', '.', $valeur);
            if (! is_numeric($nombre) || (float) $nombre < $min || (float) $nombre > $max) {
                $f = fn ($n) => str_replace('.', ',', (string) $n);

                return [$valeur, "« {$reglage->key} » doit être compris entre {$f($min)} et {$f($max)}."];
            }
        }

        return [$valeur, (string) ($reglage->value ?? '') === $valeur ? null : $this->refusDesRegles($reglage, $valeur)];
    }

    /** Les règles enregistrées sur le réglage ; un champ facultatif reste facultatif. */
    private function refusDesRegles(Setting $reglage, mixed $valeur): ?string
    {
        if (! $reglage->validation_rules) {
            return null;
        }
        $regles = $reglage->validation_rules;
        if (! $reglage->is_required && ! in_array('nullable', $regles, true)) {
            $regles = array_values(array_diff($regles, ['required']));
            array_unshift($regles, 'nullable');
        }
        $validateur = Validator::make([$reglage->key => $valeur], [$reglage->key => $regles]);

        return $validateur->fails() ? $validateur->errors()->first($reglage->key) : null;
    }

    /**
     * Les contrôles qui portent sur plusieurs réglages à la fois, jugés sur les
     * valeurs APRÈS enregistrement : `$soumis` ne porte que ce qui change.
     *
     * @param  array<string, string>  $soumis
     */
    public function refusCroise(array $soumis): ?string
    {
        // Lecture seule : une clé absente vaut son défaut, sans être créée.
        $defauts = array_map(fn (array $d) => $d['value'], InscriptionWorkflowSettings::defaults());
        $apres = fn (string $cle) => array_key_exists($cle, $soumis) ? (string) $soumis[$cle] : (string) Setting::get($cle, $defauts[$cle] ?? '');

        if (array_key_exists('pdf_header_bg_color', $soumis) || array_key_exists('pdf_header_text_color', $soumis)) {
            if (($m = self::refusCouleurs($apres('pdf_header_bg_color'), $apres('pdf_header_text_color'), "l'en-tête des documents PDF")) !== null) {
                return $m;
            }
        }

        foreach ([PortailReinscriptionService::REGLAGE_OUVERTURE, PortailReinscriptionService::REGLAGE_FERMETURE, PortailCandidaturePublication::REGLAGE_PHYSIQUES] as $cle) {
            if (array_key_exists($cle, $soumis) && ($m = self::refusDate($soumis[$cle])) !== null) {
                return $m;
            }
        }

        if (array_key_exists(PortailReinscriptionService::REGLAGE_ANNEE_CIBLE, $soumis)
            && ($m = self::refusAnneeCible($soumis[PortailReinscriptionService::REGLAGE_ANNEE_CIBLE])) !== null) {
            return $m;
        }

        if (array_key_exists(PortailCandidaturePublication::REGLAGE_ACTIF, $soumis)
            && ($m = self::refusCandidaturesSansAnnee($soumis[PortailCandidaturePublication::REGLAGE_ACTIF], $apres(PortailReinscriptionService::REGLAGE_ANNEE_CIBLE))) !== null) {
            return $m;
        }

        if (array_key_exists(PhoneNormalizer::CLE_INDICATIF, $soumis) || array_key_exists(PhoneNormalizer::CLE_PREFIXES, $soumis)) {
            $m = app(TelephoneSettingsService::class)->incoherenceDuChangementDePays(
                $soumis[PhoneNormalizer::CLE_INDICATIF] ?? null,
                $soumis[PhoneNormalizer::CLE_PREFIXES] ?? null
            );
            if ($m !== null) {
                return $m;
            }
        }

        $parcours = array_merge(InscriptionWorkflowSettings::cles(), [RendezVousReglages::ENABLED]);
        if (array_intersect(array_keys($soumis), $parcours) !== []) {
            return InscriptionWorkflowSettings::incoherence($apres);
        }

        return null;
    }

    /** Un texte de la couleur de son fond est invisible : on refuse la stricte égalité. */
    public static function refusCouleurs(?string $fond, ?string $texte, string $ou): ?string
    {
        $fond = mb_strtolower(trim((string) $fond));
        $texte = mb_strtolower(trim((string) $texte));
        if ($fond === '' || $texte === '' || $fond !== $texte) {
            return null;
        }

        return "Le texte et le fond de {$ou} ont la même couleur ({$fond}) : le texte serait invisible à l'impression. Choisissez une couleur de texte contrastée.";
    }

    /** Une borne de fenêtre se lit comme le portail la relira (PortailReinscriptionService). */
    public static function refusDate(mixed $valeur): ?string
    {
        $valeur = is_string($valeur) ? trim($valeur) : '';
        if ($valeur === '' || PortailReinscriptionService::interpreterDateIso($valeur) !== null) {
            return null;
        }

        return "La date « {$valeur} » est invalide. Format attendu : AAAA-MM-JJ.";
    }

    /** L'année visée doit exister ; vide vaut « l'année courante ». */
    public static function refusAnneeCible(mixed $valeur): ?string
    {
        $valeur = is_scalar($valeur) ? trim((string) $valeur) : '';
        if ($valeur === '' || ESBTPAnneeUniversitaire::whereKey((int) $valeur)->exists()) {
            return null;
        }

        return "L'année universitaire choisie pour les inscriptions n'existe pas.";
    }

    /** Ouvrir les candidatures exige l'année visée (sinon le portail refuse tout). */
    public static function refusCandidaturesSansAnnee(mixed $actif, mixed $annee): ?string
    {
        if ((string) $actif !== '1') {
            return null;
        }
        if (trim((string) (is_scalar($annee) ? $annee : '')) !== '') {
            return null;
        }

        return "Choisissez l'année visée par les inscriptions avant d'ouvrir les candidatures des nouveaux étudiants : sans elle, le portail refuse toutes les candidatures.";
    }

    /**
     * Ce que Nanan montrerait, sans rien écrire.
     *
     * @param  array<string, mixed>  $changements  clé => nouvelle valeur
     * @return array{refus: string[], lignes: list<array{cle: string, libelle: string, avant: string, apres: string}>, ecritures: array<string, string>, etat: array<string, ?string>, inchangees: string[]}
     */
    public function examiner(array $changements): array
    {
        $examen = ['refus' => [], 'lignes' => [], 'ecritures' => [], 'etat' => [], 'inchangees' => []];
        if ($changements === []) {
            $examen['refus'][] = 'Quel réglage changer, et pour quelle valeur ?';

            return $examen;
        }
        if (count($changements) > self::MAX) {
            $examen['refus'][] = 'Plus de '.self::MAX.' réglages à la fois : découpez la demande.';

            return $examen;
        }

        foreach (self::JUMELLES as $cle => $jumelle) {
            if (array_key_exists($cle, $changements) && ! array_key_exists($jumelle, $changements) && Setting::where('key', $jumelle)->exists()) {
                $changements[$jumelle] = $changements[$cle];
            }
        }
        ksort($changements);
        $reglages = Setting::whereIn('key', array_keys($changements))->get()->keyBy('key');
        foreach ($changements as $cle => $valeur) {
            $reglage = $reglages->get($cle);
            if (($refus = $this->refusPourNanan((string) $cle, $reglage)) !== null) {
                $examen['refus'][] = $refus;

                continue;
            }
            [$normalisee, $refus] = $this->normaliserSelonLeReglage($reglage, $valeur);
            if ($refus !== null) {
                $examen['refus'][] = $refus;

                continue;
            }
            $avant = (string) ($reglage->value ?? '');
            $examen['etat'][$cle] = $reglage->value === null ? null : $avant;
            if ($avant === $normalisee) {
                $examen['inchangees'][] = $cle;

                continue;
            }
            $examen['ecritures'][$cle] = $normalisee;
            $examen['lignes'][] = ['cle' => $cle, 'libelle' => (string) ($reglage->description ?: $cle), 'avant' => $avant, 'apres' => $normalisee];
        }

        if ($examen['refus'] === [] && $examen['ecritures'] === []) {
            $examen['refus'][] = 'Ces réglages ont déjà ces valeurs : rien à changer.';
        }
        if ($examen['refus'] === [] && ($croise = $this->refusCroise($examen['ecritures'])) !== null) {
            $examen['refus'][] = $croise;
        }

        return $examen;
    }

    /**
     * Écrit des valeurs déjà examinées, d'un bloc, après une sauvegarde des
     * réglages comme le fait l'écran. Lève si un réglage a changé entre-temps.
     *
     * @param  array<string, ?string>  $etat  valeurs lues à l'examen
     */
    public function appliquer(array $ecritures, array $etat, ?int $userId, string $source): void
    {
        DB::transaction(function () use ($ecritures, $etat, $userId, $source) {
            $reglages = Setting::whereIn('key', array_keys($ecritures))->lockForUpdate()->get()->keyBy('key');
            foreach (array_keys($ecritures) as $cle) {
                if (! $reglages->has($cle) || (string) $reglages->get($cle)->value !== (string) ($etat[$cle] ?? '')) {
                    throw new ReglageModifieEntreTemps("« {$cle} » a changé depuis la proposition.");
                }
            }
            SettingsBackup::create([
                'backup_name' => 'Auto Backup - '.now()->format('Y-m-d H:i:s'),
                'description' => "Sauvegarde automatique avant modification ({$source})",
                'settings_data' => Setting::all()->toArray(),
                'backup_type' => 'automatic',
                'backup_date' => now(),
                'created_by' => $userId,
            ]);
            foreach ($ecritures as $cle => $valeur) {
                $this->ecrire($reglages->get($cle), $valeur, $userId, $source);
            }
        });
        Setting::clearCache();
    }

    /**
     * L'écriture d'une valeur, journalisée avec l'ancienne : un réglage remis à
     * la main doit pouvoir être remis en arrière sans deviner.
     */
    public function ecrire(Setting $reglage, ?string $valeur, ?int $userId, string $source): void
    {
        $avant = $reglage->value;
        // update() sur l'instance : l'événement `saved` purge le cache de la clé.
        $reglage->update(['value' => $valeur, 'updated_by' => $userId]);

        Log::warning('[reglages] valeur modifiee a distance', [
            'key' => $reglage->key,
            'avant' => $avant,
            'apres' => $valeur,
            'source' => $source,
            'user_id' => $userId,
        ]);
    }
}
