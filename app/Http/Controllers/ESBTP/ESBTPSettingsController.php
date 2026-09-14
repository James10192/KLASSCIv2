<?php

namespace App\Http\Controllers\ESBTP;

use App\Helpers\SettingsHelper;
use App\Http\Controllers\Controller;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\Setting;
use App\Models\SettingsBackup;
use App\Http\Middleware\CheckRequiredSettings;
use App\Domain\Notifications\PhoneNormalizer;
use App\Services\AppreciationScaleSettingsService;
use App\Services\BulletinMentionResolver;
use App\Services\BtsBulletinPolicy;
use App\Services\CataloguePiecesDossier;
use App\Services\MailPulse\MailPulseTestNotificationService;
use App\Services\Mobile\MobileProfileResolver;
use App\Services\Inscription\PortailCandidaturePublication;
use App\Services\Reinscription\PortailReinscriptionService;
use App\Services\TelephoneSettingsService;
use App\Services\TenantScolariteSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Barryvdh\DomPDF\Facade\Pdf;

class ESBTPSettingsController extends Controller
{
    /**
     * Champs d'identité de l'établissement apparus après la plupart des bases.
     *
     * La sauvegarde ne modifiait que des réglages déjà présents : ceux-ci,
     * absents, étaient acceptés puis perdus en silence. Ils sont créés à la
     * première saisie.
     */
    private const CHAMPS_ETABLISSEMENT = [
        'school_mobile',
        'school_postal_code',
        'school_website',
        'school_acronym',
    ];

    private const CHAMPS_ETABLISSEMENT_LIBELLES = [
        'school_mobile' => "Téléphone mobile de l'établissement",
        'school_postal_code' => 'Code postal',
        'school_website' => 'Site web',
        'school_acronym' => "Sigle de l'établissement",
    ];

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:superAdmin|secretaire');
    }

    /**
     * Afficher la page des paramètres
     */
    public function index()
    {
        $this->ensureAttendanceNoteSettings();
        $this->ensureBtsBulletinPolicySettings();
        $this->ensureBulletinStyleSetting();
        $appreciationScaleSettings = app(AppreciationScaleSettingsService::class);
        $appreciationScaleSettings->ensureDefaults();
        $this->ensureMailPulseSettings();
        $telephoneSettings = app(TelephoneSettingsService::class);
        $telephoneSettings->ensureDefaults();
        $telephoneReglages = $telephoneSettings->etatAffichable();
        $allSettings = Setting::orderBy('category')->orderBy('sort_order')->get();
        $settings = $allSettings->groupBy('category');
        $flatSettings = $allSettings; // Collection plate pour l'accès direct par clé
        $missingSettings = CheckRequiredSettings::getAllMissingSettings();
        $this->ensureMentionSettings();
        $backupStats = SettingsBackup::getStats();
        $appreciationScales = $appreciationScaleSettings->scales();

        return view('esbtp.settings.index', compact(
            'settings',
            'flatSettings',
            'missingSettings',
            'backupStats',
            'appreciationScales',
            'telephoneReglages'
        ));
    }

    /**
     * Mettre à jour les paramètres
     */
    public function update(Request $request)
    {
        // Validation d'entree pure : elle precede la transaction. La faire
        // dans la boucle d'ecriture obligerait a sortir par un `return` au
        // milieu d'une transaction ouverte — ni `commit` ni `rollBack`, verrous
        // InnoDB retenus jusqu'a la fin de la connexion. Le formulaire couvrant
        // toute la page, une seule date mal formee abandonnerait en silence
        // bulletins, PDF, MailPulse et tronc commun.
        if (($refus = $this->refuserLesBornesInvalides($request)) !== null) {
            return $refus;
        }

        try {
            DB::beginTransaction();
            $this->ensureAttendanceNoteSettings();
            $this->ensureMentionSettings();
            $this->ensureBtsBulletinPolicySettings();
            $this->ensureBulletinStyleSetting();
            $appreciationScaleSettings = app(AppreciationScaleSettingsService::class);
            $appreciationScaleSettings->ensureDefaults();
            $this->ensureMailPulseSettings();
            $telephoneSettings = app(TelephoneSettingsService::class);
            $telephoneSettings->ensureDefaults();

            $pdfColorDefaults = [
                'pdf_primary_color' => '#0453cb',
                'pdf_secondary_color' => '#64748b',
                'pdf_accent_color' => '#f59e0b',
                'pdf_text_color' => '#1f2937',
                'pdf_header_bg_color' => '#0453cb',
                'pdf_header_text_color' => '#ffffff'
            ];

            foreach ($pdfColorDefaults as $key => $defaultValue) {
                Setting::firstOrCreate(
                    ['key' => $key],
                    [
                        'value' => $defaultValue,
                        'type' => 'string',
                        'group' => 'pdf',
                        'category' => 'pdf',
                        'description' => 'Couleur PDF',
                        'is_required' => false,
                        'default_value' => $defaultValue,
                        'validation_rules' => ['nullable', 'string', 'max:20'],
                        'sort_order' => 50
                    ]
                );
            }

            // Phase 9 — Settings avancées PDF (mise en page, footer, watermark)
            $pdfAdvancedDefaults = [
                'pdf_logo_size' => ['value' => '60', 'type' => 'integer', 'desc' => 'Hauteur max du logo (px)'],
                'pdf_footer_custom_text' => ['value' => '', 'type' => 'string', 'desc' => 'Texte personnalisé du pied de page'],
                'pdf_show_pagination' => ['value' => '1', 'type' => 'boolean', 'desc' => 'Afficher la pagination dans le footer'],
                'pdf_show_director_signature' => ['value' => '1', 'type' => 'boolean', 'desc' => 'Afficher la mention "Directeur" dans le footer'],
                'pdf_show_generator_name' => ['value' => '1', 'type' => 'boolean', 'desc' => 'Afficher le nom de l\'utilisateur qui a généré le PDF (Généré par X)'],
                'pdf_signature_height' => ['value' => '80', 'type' => 'integer', 'desc' => 'Hauteur des images de signature (px, défaut 80)'],
                'pdf_watermark_opacity' => ['value' => '0.05', 'type' => 'float', 'desc' => 'Opacité du filigrane (0.02 à 0.30)'],
                'pdf_watermark_rotation' => ['value' => '-30', 'type' => 'integer', 'desc' => 'Rotation du filigrane (-90 à 90 degrés)'],
                'pdf_watermark' => ['value' => '', 'type' => 'string', 'desc' => 'Texte du filigrane (vide = désactivé)'],
                'pdf_font_size' => ['value' => '12', 'type' => 'integer', 'desc' => 'Taille de police du corps (px)'],
                'pdf_margin_top' => ['value' => '20', 'type' => 'integer', 'desc' => 'Marge haut (mm)'],
                'pdf_margin_bottom' => ['value' => '20', 'type' => 'integer', 'desc' => 'Marge bas (mm)'],
                'pdf_margin_left' => ['value' => '15', 'type' => 'integer', 'desc' => 'Marge gauche (mm)'],
                'pdf_margin_right' => ['value' => '15', 'type' => 'integer', 'desc' => 'Marge droite (mm)'],
            ];

            foreach ($pdfAdvancedDefaults as $key => $attrs) {
                Setting::firstOrCreate(
                    ['key' => $key],
                    [
                        'value' => $attrs['value'],
                        'type' => $attrs['type'],
                        'group' => 'pdf',
                        'category' => 'pdf',
                        'description' => $attrs['desc'],
                        'is_required' => false,
                        'default_value' => $attrs['value'],
                        'validation_rules' => null,
                        'sort_order' => 60
                    ]
                );
            }

            $bulletinSemesterDefaults = [
                'bulletin_semester1_weight' => '1',
                'bulletin_semester2_weight' => '1',
            ];

            foreach ($bulletinSemesterDefaults as $key => $defaultValue) {
                Setting::firstOrCreate(
                    ['key' => $key],
                    [
                        'value' => $defaultValue,
                        'type' => 'integer',
                        'group' => 'bulletin',
                        'category' => 'bulletin',
                        'description' => 'Ponderation des semestres',
                        'is_required' => false,
                        'default_value' => $defaultValue,
                        'validation_rules' => ['nullable', 'numeric', 'min:0'],
                        'sort_order' => 120
                    ]
                );
            }

            // Créer une sauvegarde automatique avant la mise à jour
            $backup = SettingsBackup::create([
                'backup_name' => 'Auto Backup - ' . now()->format('Y-m-d H:i:s'),
                'description' => 'Sauvegarde automatique avant mise à jour des paramètres',
                'settings_data' => Setting::all()->toArray(),
                'backup_type' => 'automatic',
                'backup_date' => now(),
                'created_by' => auth()->id()
            ]);

            $updatedSettings = [];
            $errors = [];

            // Indicatif + préfixes vont par paire : la boucle générique plus bas
            // traite chaque champ isolément et ne peut donc pas voir qu'un
            // changement de pays sans ses préfixes laisse l'instance en état
            // d'accepter des numéros qui n'existent pas. Contrôle avant tout
            // enregistrement — un rollback partiel serait pire que le refus.
            $incoherenceTelephone = $telephoneSettings->incoherenceDuChangementDePays(
                $request->input('setting_'.PhoneNormalizer::CLE_INDICATIF),
                $request->input('setting_'.PhoneNormalizer::CLE_PREFIXES)
            );

            if ($incoherenceTelephone !== null) {
                $errors[PhoneNormalizer::CLE_PREFIXES] = $incoherenceTelephone;
            }

            // Barème d'assiduité à tranches (JSON) : validation structurelle dédiée via
            // le value object (contiguïté, dernière tranche ouverte, bornes) — la boucle
            // générique setting_ ne sait pas valider ce shape. Traité + skippé ensuite.


            if ($request->has('setting_attendance_note_rules')) {
                $raw = $request->input('setting_attendance_note_rules');
                $decoded = is_string($raw) ? json_decode($raw, true) : $raw;
                $ruleErrors = \App\Support\Attendance\AttendanceNoteRule::validationErrors($decoded);

                if ($ruleErrors !== []) {
                    $errors['attendance_note_rules'] = 'Barème d\'assiduité invalide : '.implode(' ; ', $ruleErrors);
                } else {
                    // Ré-encodage canonique depuis le value object (normalise l'ordre/les floats).
                    $canonical = json_encode(\App\Support\Attendance\AttendanceNoteRule::fromArray($decoded)->toArray());
                    // Save via Eloquent (pas query-builder) pour que le hook `saved`
                    // invalide le cache par-clé (setting_attendance_note_rules) — sinon
                    // on dépend d'un clearCache() distant, source de barème périmé.
                    $ruleSetting = Setting::where('key', 'attendance_note_rules')->first();
                    if ($ruleSetting) {
                        $ruleSetting->update([
                            'value' => $canonical,
                            'updated_by' => auth()->id(),
                        ]);
                        $updatedSettings[] = 'attendance_note_rules';
                    }
                }
            }

            if ($request->has('mention_rules')) {
                $rules = BulletinMentionResolver::normalize($request->input('mention_rules', []));
                $ruleSetting = Setting::where('key', BulletinMentionResolver::SETTING_KEY)->first();
                if ($ruleSetting) {
                    $ruleSetting->update([
                        'value' => json_encode($rules),
                        'updated_by' => auth()->id(),
                    ]);
                    $updatedSettings[] = BulletinMentionResolver::SETTING_KEY;
                }
            }

            $appreciationScaleSettings->processForm($request, $updatedSettings, $errors);

            // D'abord, traiter toutes les checkboxes (défaut à '0' si décochées)
            // Ensure certificat column settings exist (create with default=1 if first save)
            $certificatColumnDefaults = [
                'certificat_show_classe'  => '1',
                'certificat_show_niveau'  => '1',
                'certificat_show_filiere' => '1',
            ];
            foreach ($certificatColumnDefaults as $key => $defaultValue) {
                Setting::firstOrCreate(
                    ['key' => $key],
                    [
                        'value'            => $defaultValue,
                        'type'             => 'boolean',
                        'group'            => 'documents',
                        'category'         => 'documents',
                        'description'      => 'Colonne certificat de scolarité',
                        'is_required'      => false,
                        'default_value'    => $defaultValue,
                        'validation_rules' => ['nullable', 'in:0,1'],
                        'sort_order'       => 200,
                    ]
                );
            }

            // Créer les settings de note de conduite si inexistants
            $conduiteDefaults = [
                'bulletin_conduite_enabled' => ['value' => '0', 'type' => 'boolean', 'description' => 'Activer la note de conduite sur le bulletin'],
                'conduite_note_defaut' => ['value' => '16', 'type' => 'float', 'description' => 'Note de conduite par défaut (/20)'],
                'conduite_heures_par_point' => ['value' => '4', 'type' => 'float', 'description' => 'Heures d\'absence pour retrancher 1 point'],
                'bulletin_show_absences_par_matiere' => ['value' => '1', 'type' => 'boolean', 'description' => 'Afficher les absences par matière sur le bulletin'],
            ];

            foreach ($conduiteDefaults as $key => $attrs) {
                Setting::firstOrCreate(
                    ['key' => $key],
                    [
                        'value' => $attrs['value'],
                        'type' => $attrs['type'],
                        'group' => 'bulletin',
                        'category' => 'bulletin',
                        'description' => $attrs['description'],
                        'is_required' => false,
                        'default_value' => $attrs['value'],
                        'validation_rules' => null,
                        'sort_order' => 150,
                    ]
                );
            }

            // Assiduité / saisie manuelle d'heures — toggle tenant
            Setting::firstOrCreate(
                ['key' => 'scolarite.split_roles'],
                [
                    'value' => '0',
                    'type' => 'boolean',
                    'group' => 'scolarite',
                    'category' => 'scolarite',
                    'description' => 'Affiche les roles Responsable scolarite et Service scolarite dans le personnel et active leurs dashboards',
                    'is_required' => false,
                    'default_value' => '0',
                    'validation_rules' => null,
                    'sort_order' => 154,
                ]
            );

            Setting::firstOrCreate(
                ['key' => 'scolarite.clerk_lmd_access'],
                [
                    'value' => '0',
                    'type' => 'boolean',
                    'group' => 'scolarite',
                    'category' => 'scolarite',
                    'description' => 'Le service scolarite accede au module LMD (notes, resultats, bulletins, domaines, UE). A activer sur les etablissements universitaires.',
                    'is_required' => false,
                    'default_value' => '0',
                    'validation_rules' => null,
                    'sort_order' => 155,
                ]
            );

            Setting::firstOrCreate(
                ['key' => TenantScolariteSettings::CLERK_PEDAGOGIE],
                [
                    'value' => '0',
                    'type' => 'boolean',
                    'group' => 'scolarite',
                    'category' => 'scolarite',
                    'description' => 'Le service scolarite cree et valide inscriptions et etudiants, et pilote domaines/UE/parcours. Sans planning ni personnel enseignant.',
                    'is_required' => false,
                    'default_value' => '0',
                    'validation_rules' => null,
                    'sort_order' => 156,
                ]
            );

            Setting::firstOrCreate(
                ['key' => TenantScolariteSettings::MANAGE_TEACHERS],
                [
                    'value' => '0',
                    'type' => 'boolean',
                    'group' => 'scolarite',
                    'category' => 'scolarite',
                    'description' => 'Le responsable et le service scolarite creent et modifient les enseignants (fiches professeurs). Pas le personnel unifie.',
                    'is_required' => false,
                    'default_value' => '0',
                    'validation_rules' => null,
                    'sort_order' => 157,
                ]
            );

            Setting::firstOrCreate(
                ['key' => 'documents.print_requires_approval'],
                [
                    'value' => '0',
                    'type' => 'boolean',
                    'group' => 'scolarite',
                    'category' => 'scolarite',
                    'description' => 'Solde impaye puis accord du responsable avant tout PDF certificat, attestation ou bulletin',
                    'is_required' => false,
                    'default_value' => '0',
                    'validation_rules' => null,
                    'sort_order' => 154,
                ]
            );

            Setting::firstOrCreate(
                ['key' => 'caisse.pre_inscription.enabled'],
                [
                    'value' => '1',
                    'type' => 'boolean',
                    'group' => 'scolarite',
                    'category' => 'scolarite',
                    'description' => 'Autorise la caisse a faire les pre-inscriptions. Laissez active pour Yakro et Abidjan, desactivez pour ISLG et USAT.',
                    'is_required' => false,
                    'default_value' => '1',
                    'validation_rules' => null,
                    'sort_order' => 155,
                ]
            );

            Setting::firstOrCreate(
                ['key' => TenantScolariteSettings::CONFIRMER_STATUT_ETABLISSEMENT],
                [
                    'value' => '0',
                    'type' => 'boolean',
                    'group' => 'scolarite',
                    'category' => 'scolarite',
                    'description' => 'Demande a l agent de confirmer si l etudiant est nouveau ou deja passe par l etablissement. Necessaire la premiere annee KLASSCI, quand l historique n existe pas.',
                    'is_required' => false,
                    'default_value' => '0',
                    'validation_rules' => null,
                    'sort_order' => 157,
                ]
            );

            Setting::firstOrCreate(
                ['key' => 'inscriptions.split_role'],
                [
                    'value' => '0',
                    'type' => 'boolean',
                    'group' => 'scolarite',
                    'category' => 'scolarite',
                    'description' => 'Affiche le role Agent d inscription dans le personnel et active son dashboard. Additif, Yakro et Abidjan restent sur secretaire tant que cette option est desactivee.',
                    'is_required' => false,
                    'default_value' => '0',
                    'validation_rules' => null,
                    'sort_order' => 156,
                ]
            );

            Setting::firstOrCreate(
                ['key' => 'attendance_manual_hours_global_enabled'],
                [
                    'value' => '0',
                    'type' => 'boolean',
                    'group' => 'attendance',
                    'category' => 'bulletin',
                    'description' => "Active le mode global (sans matière) pour la saisie manuelle d'heures sur /esbtp/attendances/create",
                    'is_required' => false,
                    'default_value' => '0',
                    'validation_rules' => null,
                    'sort_order' => 155,
                ]
            );

            // Créer les settings tronc commun si inexistants
            $troncCommunDefaults = [
                'tronc_commun_enabled' => ['value' => '0', 'description' => 'Activer le tronc commun'],
                'tronc_commun_mga_include_s1' => ['value' => '1', 'description' => 'Reporter les notes S1 dans la MGA'],
                'tronc_commun_report_paiements' => ['value' => '1', 'description' => 'Reporter automatiquement les paiements du tronc commun'],
                'tronc_commun_report_notes' => ['value' => '1', 'description' => 'Conserver les notes du S1 accessibles depuis la spécialisation'],
                'tronc_commun_bulletin_show_origin' => ['value' => '1', 'description' => 'Mentionner la classe de tronc commun sur le bulletin'],
                'tronc_commun_matieres_communes' => ['value' => '1', 'description' => 'Détecter les matières partagées entre TC et spécialisation'],
                'tronc_commun_planning_semestre_strict' => ['value' => '0', 'description' => 'Restreindre le planning par semestre'],
            ];

            foreach ($troncCommunDefaults as $key => $attrs) {
                Setting::firstOrCreate(
                    ['key' => $key],
                    [
                        'value' => $attrs['value'],
                        'type' => 'boolean',
                        'group' => 'tronc_commun',
                        'category' => 'tronc_commun',
                        'description' => $attrs['description'],
                        'is_required' => false,
                        'default_value' => $attrs['value'],
                        'validation_rules' => null,
                        'sort_order' => 160,
                    ]
                );
            }

            $basculesGerees = array_merge([
                'bulletin_show_logo', 'bulletin_show_header', 'bulletin_show_republic_info',
                'bulletin_show_ministry_info', 'bulletin_show_school_info', 'bulletin_show_cycle_info',
                'bulletin_show_edition_date', 'bulletin_show_student_info', 'bulletin_show_matricule',
                'bulletin_show_birth_date', 'bulletin_show_redoublant', 'bulletin_show_subjects_table',
                'bulletin_show_teachers', 'bulletin_show_absences', 'bulletin_show_statistics',
                'bulletin_show_signature', 'bulletin_show_attendance_note', 'bulletin_show_council_decision',
                'bulletin_show_highest_average', 'bulletin_show_lowest_average', 'bulletin_show_class_average',
                'bulletin_auto_calculate_mention',
                'certificat_show_classe', 'certificat_show_niveau', 'certificat_show_filiere',
                'bulletin_conduite_enabled', 'bulletin_show_absences_par_matiere',
                'attendance_manual_hours_global_enabled',
                // Bascules a cle pointee : les constantes, jamais les chaines.
                // Une faute de frappe passerait les tests et remettrait la
                // bascule a zero en silence — c'est l'incident de la PR #591.
                TenantScolariteSettings::SPLIT_ROLES,
                TenantScolariteSettings::PRINT_REQUIRES_APPROVAL,
                TenantScolariteSettings::CASHIER_PRE_ENROLLMENT,
                TenantScolariteSettings::AGENT_INSCRIPTION_ROLE,
                TenantScolariteSettings::REINSCRIPTION_EN_LIGNE,
                TenantScolariteSettings::CONFIRMER_STATUT_ETABLISSEMENT,
                TenantScolariteSettings::CLERK_LMD_ACCESS,
                TenantScolariteSettings::CLERK_PEDAGOGIE,
                TenantScolariteSettings::MANAGE_TEACHERS,
                PortailCandidaturePublication::REGLAGE_ACTIF,
                // Case a cocher, donc ici et non dans $reglagesTexte : une case
                // decochee n'est pas envoyee par le navigateur, et seule cette
                // liste-ci sait lire son absence comme un « non ».
                CataloguePiecesDossier::REGLAGE_RESTITUTION_ANNULATION,
                // Shell mobile (barre d'onglets sur telephone) : seme par
                // migration, lu par MobileProfileResolver, sans cette ligne
                // la case de la page n'aurait jamais ete enregistree.
                MobileProfileResolver::REGLAGE_ACTIF,
            ], array_keys($troncCommunDefaults));

            // Reglages a cle pointee qui ne sont PAS des cases a cocher. La
            // distinction ne peut PAS se lire sur la colonne `type` : plusieurs
            // bascules historiques y sont enregistrees en « string », et les
            // traiter comme du texte les empecherait de repasser a zero.
            $reglagesTexte = [
                PortailReinscriptionService::REGLAGE_OUVERTURE,
                PortailReinscriptionService::REGLAGE_FERMETURE,
                PortailReinscriptionService::REGLAGE_ANNEE_CIBLE,
                PortailCandidaturePublication::REGLAGE_PHYSIQUES,
                // Les reglages a champ texte du catalogue des pieces a fournir.
                // Sans ces lignes, la page les afficherait sans jamais les
                // enregistrer : une ecole qui reclame vingt-quatre photos
                // resterait bloquee au plafond de vingt, sans autre recours
                // que du SQL. Les cles viennent des constantes du service,
                // jamais de chaines ecrites ici — lecon de la PR #591.
                CataloguePiecesDossier::REGLAGE_EXEMPLAIRES_MAX,
                CataloguePiecesDossier::REGLAGE_FORME_DEFAUT,
                CataloguePiecesDossier::REGLAGE_ECHEANCE_DEFAUT,
                CataloguePiecesDossier::REGLAGE_EPUISEMENT,
            ];

            $reglagesPointes = Setting::whereIn('key', array_merge($basculesGerees, $reglagesTexte))->get();

            $treatMissingCheckboxesAsOff = $request->boolean('settings_save_display');
            // Les cles pointees (scolarite.split_roles, caisse.pre_inscription.enabled...)
            // ne peuvent pas passer par $request->boolean() / exists() / input() : Laravel
            // y voit un acces imbrique, et PHP a de toute facon remplace le point par un
            // underscore dans $_POST. Sans ce contournement, toute sauvegarde de la page
            // remettait ces bascules a 0 quel que soit l'etat reel des cases.
            $rawInput = $request->all();

            foreach ($reglagesPointes as $setting) {
                $estBascule = ! in_array($setting->key, $reglagesTexte, true);
                $estSoumis = $this->estSoumis($rawInput, $setting->key);
                $soumis = $this->valeurSoumise($rawInput, $setting->key);

                // Une case decochee n'est pas envoyee par le navigateur : son
                // absence VAUT « off », mais seulement quand le formulaire qui
                // la porte a bien ete soumis. Un champ texte absent, lui, ne
                // vaut jamais « vide » : on ne le touche pas.
                if (! $estSoumis && ! ($estBascule && $treatMissingCheckboxesAsOff)) {
                    continue;
                }

                $setting->update([
                    'value' => $estBascule
                        ? (filter_var($soumis, FILTER_VALIDATE_BOOLEAN) ? '1' : '0')
                        : (is_string($soumis) ? trim($soumis) : ''),
                    'updated_by' => auth()->id(),
                ]);

                $updatedSettings[] = $setting->key;
            }

            if ($request->exists('bulletin_show_signature') || $request->exists('bulletin_show_signatures')) {
                $signatureOn = $request->boolean('bulletin_show_signature') || $request->boolean('bulletin_show_signatures');
                foreach (['bulletin_show_signature', 'bulletin_show_signatures'] as $signatureKey) {
                    $signatureSetting = Setting::firstOrCreate(
                        ['key' => $signatureKey],
                        [
                            'value' => $signatureOn ? '1' : '0',
                            'type' => 'boolean',
                            'group' => 'bulletin',
                            'category' => 'bulletin',
                            'description' => 'Afficher les signatures du bulletin',
                            'is_required' => false,
                            'default_value' => '1',
                        ]
                    );
                    $signatureSetting->update([
                        'value' => $signatureOn ? '1' : '0',
                        'updated_by' => auth()->id(),
                    ]);
                    $updatedSettings[] = $signatureKey;
                }
            }

            // Traiter les paramètres de rappels (ESBTPSystemSetting)
            $reminderSettings = [
                'reminder_inscription_enabled', 'reminder_inscription_first_delay',
                'reminder_inscription_frequency', 'reminder_inscription_max_count',
                'reminder_paiement_enabled', 'reminder_paiement_first_delay',
                'reminder_paiement_frequency', 'reminder_paiement_max_count'
            ];

            foreach ($reminderSettings as $key) {
                if ($request->has($key)) {
                    $value = $request->input($key);

                    // Pour les checkboxes
                    if (in_array($key, ['reminder_inscription_enabled', 'reminder_paiement_enabled'])) {
                        $value = $request->has($key) ? '1' : '0';
                        $type = 'boolean';
                    } else {
                        $type = 'integer';
                    }

                    \App\Models\ESBTPSystemSetting::setValue($key, $value, $type);
                    $updatedSettings[] = $key;
                } elseif (in_array($key, ['reminder_inscription_enabled', 'reminder_paiement_enabled'])) {
                    // Checkbox non cochée
                    \App\Models\ESBTPSystemSetting::setValue($key, '0', 'boolean');
                    $updatedSettings[] = $key;
                }
            }

            // Ensuite traiter les autres champs (texte, nombre, etc.)
            foreach ($request->all() as $key => $value) {
                if (strpos($key, 'setting_') === 0) {
                    $settingKey = str_replace('setting_', '', $key);

                    // Skip les checkboxes déjà traitées
                    if (in_array($settingKey, $reglagesPointes->pluck('key')->toArray())) {
                        continue;
                    }

                    // Barème assiduité JSON : déjà validé + sauvegardé plus haut.
                    if (in_array($settingKey, ['attendance_note_rules', BulletinMentionResolver::SETTING_KEY], true)) {
                        continue;
                    }

                    if ($appreciationScaleSettings->shouldSkipGenericSetting($request, $settingKey)) {
                        continue;
                    }
                    
                    $setting = Setting::where('key', $settingKey)->first();

                    if ($setting) {
                        if ($settingKey === 'mailpulse_api_key' && is_string($value)) {
                            $value = trim($value);
                        }

                        if ($settingKey === 'mailpulse_api_key' && ($value === null || $value === '')) {
                            continue;
                        }

                        // Lot 17b — Champs établissement nullable :
                        // si la valeur est vide ET le champ n'est pas marqué `is_required`,
                        // on skip la validation (sinon les règles legacy ['required', ...]
                        // dans la DB rejettent les champs facultatifs laissés vides).
                        $isEmpty = $value === null || $value === '';

                        // Idempotence : si la valeur soumise est identique à celle en DB,
                        // on ne valide pas (évite de pénaliser sur des seeds pourris où
                        // un setting est marqué is_required=1 mais a une value vide
                        // — ex: current_academic_year qui ne sert plus, l'année courante
                        // venant de esbtp_annee_universitaires.is_current). Pas de
                        // modification = pas de raison de re-valider.
                        // La comparaison reste sur le BRUT. Une version comparait
                        // aussi au caste pour absorber les settings integer qui
                        // portaient '' en base (affiches « 0 », renvoyes « 0 »,
                        // jamais egaux au brut, donc echec permanent de la
                        // page) : mais castValue rend un TABLEAU pour les types
                        // json, et le (string) plantait tout l'enregistrement
                        // en rollback muet. La donnee sale est reparee par
                        // migration (les integer vides valent '0') ; la garde
                        // n'a plus a la compenser.
                        $currentValue = (string) ($setting->value ?? '');
                        $newValue = $value === null ? '' : (string) $value;
                        if ($currentValue === $newValue) {
                            continue;
                        }

                        if ($isEmpty && ! $setting->is_required) {
                            // Permet d'écraser une valeur existante par '' (vidage volontaire).
                            $setting->update([
                                'value' => '',
                                'updated_by' => auth()->id()
                            ]);
                            $updatedSettings[] = $settingKey;
                            continue;
                        }

                        // Valider la valeur selon les règles définies
                        if ($setting->validation_rules) {
                            // Lot 17b — Forcer `nullable` en tête de liste sauf si le champ
                            // est explicitement `is_required` (sinon Laravel évalue
                            // `email|string|...` avant `nullable` et rejette '').
                            $rules = $setting->validation_rules;
                            if (! $setting->is_required && ! in_array('nullable', $rules, true)) {
                                $rules = array_values(array_diff($rules, ['required']));
                                array_unshift($rules, 'nullable');
                            }

                            $validator = Validator::make(
                                [$settingKey => $value],
                                [$settingKey => $rules]
                            );

                            if ($validator->fails()) {
                                $errors[$settingKey] = $validator->errors()->first($settingKey);
                                continue;
                            }
                        }

                        // Traitement spécial selon le type
                        $processedValue = $this->processSettingValue($value, $setting->type, $request);

                        $setting->update([
                            'value' => $processedValue,
                            'updated_by' => auth()->id()
                        ]);

                        $updatedSettings[] = $settingKey;
                    } elseif (in_array($settingKey, self::CHAMPS_ETABLISSEMENT, true)) {
                        // Ces champs sont plus récents que la plupart des bases :
                        // sans cette branche, l'administrateur saisissait son site
                        // web, voyait « enregistré », et rien n'était gardé.
                        //
                        // firstOrCreate, comme le reste de cette méthode : deux
                        // enregistrements simultanés heurteraient la clé unique et
                        // feraient échouer tout le formulaire.
                        $valeur = is_string($value) ? trim($value) : $value;

                        if ($valeur !== null && $valeur !== '') {
                            Setting::firstOrCreate(
                                ['key' => $settingKey],
                                [
                                    'value' => $valeur,
                                    'type' => 'string',
                                    'group' => 'establishment',
                                    'category' => 'establishment',
                                    'description' => self::CHAMPS_ETABLISSEMENT_LIBELLES[$settingKey] ?? $settingKey,
                                    'is_required' => false,
                                    'created_by' => auth()->id(),
                                    'updated_by' => auth()->id(),
                                ]
                            );

                            $updatedSettings[] = $settingKey;
                        }
                    }
                }
            }

            // Traiter les fichiers uploadés
            foreach ($request->allFiles() as $key => $file) {
                if (strpos($key, 'setting_') === 0) {
                    $settingKey = str_replace('setting_', '', $key);
                    $setting = Setting::where('key', $settingKey)->first();

                    Log::info('Traitement fichier uploadé', [
                        'key' => $key,
                        'settingKey' => $settingKey,
                        'setting_found' => $setting !== null,
                        'setting_type' => $setting ? $setting->type : null,
                        'file_name' => $file->getClientOriginalName(),
                        'file_size' => $file->getSize()
                    ]);

                    if ($setting && $setting->type === 'file') {
                        // Valider le fichier
                        $validator = Validator::make(
                            [$settingKey => $file],
                            [$settingKey => 'image|mimes:jpeg,png,jpg,gif|max:2048']
                        );

                        if ($validator->fails()) {
                            $errors[$settingKey] = $validator->errors()->first($settingKey);
                            continue;
                        }

                        // Supprimer l'ancien fichier s'il existe
                        if ($setting->value && Storage::disk('public')->exists($setting->value)) {
                            Storage::disk('public')->delete($setting->value);
                        }

                        // Stocker le nouveau fichier dans le bon dossier selon le type
                        $folder = $this->getStorageFolderForSetting($settingKey);
                        $path = $file->store($folder, 'public');

                        $setting->update([
                            'value' => $path,
                            'updated_by' => auth()->id()
                        ]);

                        Log::info('Fichier uploadé avec succès', [
                            'settingKey' => $settingKey,
                            'path' => $path,
                            'folder' => $folder
                        ]);

                        $updatedSettings[] = $settingKey;
                    }
                }
            }

            if (!empty($errors)) {
                DB::rollBack();
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Certaines configurations contiennent des erreurs.',
                        'errors' => $errors,
                    ], 422);
                }

                return redirect()->back()
                    ->withErrors($errors)
                    ->withInput()
                    ->with('error', 'Certaines configurations contiennent des erreurs.');
            }

            // Vider les caches
            Setting::clearCache();
            CheckRequiredSettings::clearCache();

            DB::commit();

            Log::info('Paramètres mis à jour', [
                'user_id' => auth()->id(),
                'updated_settings' => $updatedSettings,
                'backup_id' => $backup->id
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Paramètres MailPulse enregistrés.',
                    'updated_count' => count($updatedSettings),
                ]);
            }

            return redirect()->route('esbtp.settings.index')
                ->with('success', 'Paramètres mis à jour avec succès.')
                ->with('updated_count', count($updatedSettings));

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Erreur lors de la mise à jour des paramètres', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de la mise à jour des paramètres.',
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Erreur lors de la mise à jour des paramètres.')
                ->withInput();
        }
    }


    /**
     * Valeur soumise pour un reglage, que sa cle contienne un point ou non.
     *
     * PHP remplace les points par des tirets bas dans $_POST : le champ
     * `reinscriptions.en_ligne.enabled` arrive sous le nom
     * `reinscriptions_en_ligne_enabled`. On accepte les deux orthographes.
     *
     * Cette methode ne dit PAS si le champ etait present : une valeur nulle et
     * une absence rendent toutes deux null. Utiliser estSoumis() pour cela —
     * une requete JSON peut envoyer un champ a null, et le confondre avec une
     * absence remettrait silencieusement une bascule a zero.
     */
    private function valeurSoumise(array $rawInput, string $cle): mixed
    {
        $cleFormulaire = str_replace('.', '_', $cle);

        return $rawInput[$cle] ?? $rawInput[$cleFormulaire] ?? null;
    }

    /** Le champ figurait-il dans la requete, quelle que soit sa valeur ? */
    private function estSoumis(array $rawInput, string $cle): bool
    {
        return array_key_exists($cle, $rawInput)
            || array_key_exists(str_replace('.', '_', $cle), $rawInput);
    }

    /**
     * Les bornes de la fenetre de reinscription sont des dates.
     *
     * La verification delegue a PortailReinscriptionService : c'est le service
     * qui les relit, et deux analyseurs de severites differentes creeraient un
     * ecart entre ce que l'ecole croit avoir enregistre et ce que le portail
     * applique — une date qui deborde y serait acceptee puis reportee de
     * plusieurs mois, en silence.
     */
    private function refuserLesBornesInvalides(Request $request)
    {
        $rawInput = $request->all();

        $reglagesDate = [
            PortailReinscriptionService::REGLAGE_OUVERTURE,
            PortailReinscriptionService::REGLAGE_FERMETURE,
            // Le debut des inscriptions sur place, annonce au candidat a la fin
            // du formulaire public. Sans cette ligne, une date qui deborde
            // serait acceptee ici puis rejetee a la lecture : l'ecole croirait
            // avoir annonce une date, le portail n'en annoncerait aucune.
            PortailCandidaturePublication::REGLAGE_PHYSIQUES,
        ];

        // Un texte de la couleur de son fond est invisible.
        //
        // Sur esbtp-abidjan, pdf_header_bg_color et pdf_header_text_color
        // valaient tous les deux #ffffff : le bandeau des exports — nom de
        // l'ecole, titre du document, chiffres — etait imprime en blanc sur
        // blanc. Personne ne l'avait vu a la saisie, parce que rien ne l'avait
        // dit : l'ecran acceptait la paire sans broncher.
        //
        // On refuse la stricte egalite, pas la ressemblance : juger de la
        // lisibilite d'un gris sur un autre gris demande un calcul de contraste,
        // qui a sa place dans le rendu, pas dans un formulaire. Ici on ferme
        // simplement la porte au cas ou l'invisibilite est certaine.
        $pairesDeCouleurs = [
            ['pdf_header_bg_color', 'pdf_header_text_color', "l'en-tete des documents PDF"],
        ];

        foreach ($pairesDeCouleurs as [$cleFond, $cleTexte, $ou]) {
            if (! $this->estSoumis($rawInput, $cleFond) && ! $this->estSoumis($rawInput, $cleTexte)) {
                continue;
            }

            $fond = $this->estSoumis($rawInput, $cleFond)
                ? $this->valeurSoumise($rawInput, $cleFond)
                : Setting::get($cleFond);
            $texte = $this->estSoumis($rawInput, $cleTexte)
                ? $this->valeurSoumise($rawInput, $cleTexte)
                : Setting::get($cleTexte);

            $fond = mb_strtolower(trim((string) $fond));
            $texte = mb_strtolower(trim((string) $texte));

            if ($fond === '' || $texte === '' || $fond !== $texte) {
                continue;
            }

            return $this->refus(
                $request,
                "Le texte et le fond de {$ou} ont la meme couleur ({$fond}) : le texte serait invisible a l'impression. Choisissez une couleur de texte contrastee."
            );
        }

        foreach ($reglagesDate as $cle) {
            if (! $this->estSoumis($rawInput, $cle)) {
                continue;
            }

            $valeur = $this->valeurSoumise($rawInput, $cle);
            $valeur = is_string($valeur) ? trim($valeur) : '';

            if ($valeur === '' || PortailReinscriptionService::interpreterDateIso($valeur) !== null) {
                continue;
            }

            $message = "La date « {$valeur} » est invalide. Format attendu : AAAA-MM-JJ.";

            return $this->refus($request, $message);
        }

        if (($refus = $this->refuserAnneeCibleInconnue($request)) !== null) {
            return $refus;
        }

        return $this->refuserCandidaturesSansAnnee($request);
    }

    /**
     * Ouvrir les candidatures exige de designer l'annee visee.
     *
     * Les deux canaux ne lisent pas ce champ de la meme facon, et c'est
     * volontaire : la reinscription retombe sur l'annee courante quand il est
     * vide (reconduire un dossier dans l'annee en cours a un sens), la
     * candidature refuse (ranger une cohorte de nouveaux sous l'annee
     * sortante n'en a aucun, et personne ne s'en apercevrait avant les
     * bulletins).
     *
     * Ce refus est donc ce qui empeche cette asymetrie de se decouvrir cote
     * public. Sans lui : l'ecole coche l'interrupteur, laisse le selecteur sur
     * son option vide, enregistre — la sauvegarde reussit, l'interrupteur est
     * vert, aucun journal ne bronche — et CHAQUE bachelier lit « l'etablissement
     * n'a pas termine la configuration de cette rentree ». Le seul signal
     * arrive par telephone, en pleine rentree.
     *
     * Ici plutot que dans le service : c'est au moment ou l'ecole enregistre
     * qu'on peut encore lui dire quoi faire.
     */
    private function refuserCandidaturesSansAnnee(Request $request)
    {
        $rawInput = $request->all();
        $cleCanal = PortailCandidaturePublication::REGLAGE_ACTIF;

        // La case n'etait pas dans le formulaire soumis : on ne juge pas un
        // reglage que cette page n'a pas presente.
        if (! $this->estSoumis($rawInput, $cleCanal)) {
            return null;
        }

        if ((string) $this->valeurSoumise($rawInput, $cleCanal) !== '1') {
            return null;
        }

        $cleAnnee = PortailReinscriptionService::REGLAGE_ANNEE_CIBLE;

        // L'annee peut venir du formulaire courant OU d'un enregistrement
        // precedent : les deux comptent, seul le resultat final importe.
        $annee = $this->estSoumis($rawInput, $cleAnnee)
            ? $this->valeurSoumise($rawInput, $cleAnnee)
            : SettingsHelper::get($cleAnnee, '');

        if (trim((string) (is_scalar($annee) ? $annee : '')) !== '') {
            return null;
        }

        return $this->refus(
            $request,
            "Choisissez l'année visée par les inscriptions avant d'ouvrir les candidatures des nouveaux étudiants : sans elle, le portail refuse toutes les candidatures."
        );
    }

    /**
     * L'annee visee par les inscriptions doit exister.
     *
     * Une valeur pointant dans le vide ne casse rien visiblement du cote de la
     * reinscription : elle retombe sur l'annee courante. L'ecole croirait donc
     * ouvrir sa rentree tout en ouvrant l'annee en cours, ou personne n'est
     * eligible — et elle chercherait longtemps pourquoi.
     */
    private function refuserAnneeCibleInconnue(Request $request)
    {
        $cle = PortailReinscriptionService::REGLAGE_ANNEE_CIBLE;
        $rawInput = $request->all();

        if (! $this->estSoumis($rawInput, $cle)) {
            return null;
        }

        $valeur = $this->valeurSoumise($rawInput, $cle);
        $valeur = is_scalar($valeur) ? trim((string) $valeur) : '';

        // Vide est le cas normal : « l'annee courante ».
        if ($valeur === '' || ESBTPAnneeUniversitaire::whereKey((int) $valeur)->exists()) {
            return null;
        }

        return $this->refus($request, "L'année universitaire choisie pour les inscriptions n'existe pas.");
    }

    private function refus(Request $request, string $message)
    {
        return $request->expectsJson()
            ? response()->json(['success' => false, 'message' => $message], 422)
            : back()->withInput()->with('error', $message);
    }

    private function ensureBulletinStyleSetting(): void
    {
        Setting::firstOrCreate(
            ['key' => 'bulletin_style'],
            [
                'value' => 'yakro',
                'type' => 'string',
                'group' => 'bulletin',
                'category' => 'bulletin',
                'description' => 'Modele de bulletin (yakro ou abidjan)',
                'is_required' => false,
                'is_active' => true,
                'default_value' => 'yakro',
                'validation_rules' => ['nullable', 'in:yakro,abidjan'],
            ]
        );
    }
    private function ensureBtsBulletinPolicySettings(): void
    {
        $defaults = BtsBulletinPolicy::settingDefinitions();

        foreach ($defaults as $key => $default) {
            $metadata = $default + [
                'group' => 'bulletin',
                'category' => 'bulletin',
                'is_required' => false,
                'is_active' => true,
                'default_value' => $default['value'],
            ];

            $setting = Setting::firstOrNew(['key' => $key]);

            if ($setting->exists) {
                unset($metadata['value']);
            }

            $setting->fill($metadata);
            $setting->save();
        }
    }

    /**
     * Créer une nouvelle configuration
     */
    public function store(Request $request)
    {
        $request->validate([
            'key' => 'required|string|unique:settings,key',
            'value' => 'required',
            'type' => 'required|in:string,integer,boolean,json,file',
            'category' => 'required|string',
            'description' => 'nullable|string',
            'is_required' => 'boolean',
            'validation_rules' => 'nullable|string'
        ]);

        try {
            $setting = Setting::create([
                'key' => $request->key,
                'value' => $this->processSettingValue($request->value, $request->type),
                'type' => $request->type,
                'category' => $request->category,
                'description' => $request->description,
                'is_required' => $request->boolean('is_required'),
                'validation_rules' => $request->validation_rules ? json_decode($request->validation_rules, true) : null,
                'is_active' => true,
                'created_by' => auth()->id()
            ]);

            Setting::clearCache();
            CheckRequiredSettings::clearCache();

            return redirect()->route('esbtp.settings.index')
                ->with('success', 'Configuration créée avec succès.');

        } catch (\Exception $e) {
            Log::error('Erreur lors de la création de la configuration', [
                'error' => $e->getMessage(),
                'data' => $request->all()
            ]);

            return redirect()->back()
                ->with('error', 'Erreur lors de la création de la configuration.')
                ->withInput();
        }
    }

    /**
     * Supprimer une configuration
     */
    public function destroy($id)
    {
        try {
            $setting = Setting::findOrFail($id);

            // Vérifier si la configuration est requise
            if ($setting->is_required) {
                return redirect()->back()
                    ->with('error', 'Impossible de supprimer une configuration requise.');
            }

            $setting->delete();

            Setting::clearCache();
            CheckRequiredSettings::clearCache();

            return redirect()->route('esbtp.settings.index')
                ->with('success', 'Configuration supprimée avec succès.');

        } catch (\Exception $e) {
            Log::error('Erreur lors de la suppression de la configuration', [
                'setting_id' => $id,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()
                ->with('error', 'Erreur lors de la suppression de la configuration.');
        }
    }

    /**
     * Gestion des sauvegardes
     */
    public function backups()
    {
        $backups = SettingsBackup::with(['creator', 'restorer'])
            ->orderBy('backup_date', 'desc')
            ->paginate(20);

        $stats = SettingsBackup::getStats();

        return view('esbtp.settings.backups', compact('backups', 'stats'));
    }

    /**
     * Créer une sauvegarde manuelle
     */
    public function createBackup(Request $request)
    {
        $request->validate([
            'backup_name' => 'required|string|max:255',
            'description' => 'nullable|string'
        ]);

        try {
            $backup = SettingsBackup::createManualBackup(
                $request->backup_name,
                $request->description,
                auth()->id()
            );

            return redirect()->route('esbtp.settings.backups')
                ->with('success', 'Sauvegarde créée avec succès.');

        } catch (\Exception $e) {
            Log::error('Erreur lors de la création de la sauvegarde', [
                'error' => $e->getMessage()
            ]);

            return redirect()->back()
                ->with('error', 'Erreur lors de la création de la sauvegarde.');
        }
    }

    /**
     * Restaurer une sauvegarde
     */
    public function restoreBackup(Request $request, $id)
    {
        $request->validate([
            'restore_notes' => 'nullable|string'
        ]);

        try {
            $backup = SettingsBackup::findOrFail($id);

            $backup->restore(auth()->id(), $request->restore_notes);

            return redirect()->route('esbtp.settings.index')
                ->with('success', 'Sauvegarde restaurée avec succès.');

        } catch (\Exception $e) {
            Log::error('Erreur lors de la restauration de la sauvegarde', [
                'backup_id' => $id,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()
                ->with('error', 'Erreur lors de la restauration de la sauvegarde.');
        }
    }

    /**
     * Comparer une sauvegarde avec les configurations actuelles
     */
    public function compareBackup($id)
    {
        try {
            $backup = SettingsBackup::findOrFail($id);
            $differences = $backup->compareWithCurrent();

            return view('esbtp.settings.compare', compact('backup', 'differences'));

        } catch (\Exception $e) {
            Log::error('Erreur lors de la comparaison de la sauvegarde', [
                'backup_id' => $id,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()
                ->with('error', 'Erreur lors de la comparaison de la sauvegarde.');
        }
    }

    /**
     * Archiver une sauvegarde
     */
    public function archiveBackup($id)
    {
        try {
            $backup = SettingsBackup::findOrFail($id);
            $backup->archive();

            return redirect()->route('esbtp.settings.backups')
                ->with('success', 'Sauvegarde archivée avec succès.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Erreur lors de l\'archivage de la sauvegarde.');
        }
    }

    /**
     * Nettoyer les anciennes sauvegardes
     */
    public function cleanupBackups()
    {
        try {
            SettingsBackup::cleanupOldBackups();

            return redirect()->route('esbtp.settings.backups')
                ->with('success', 'Nettoyage des sauvegardes effectué avec succès.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Erreur lors du nettoyage des sauvegardes.');
        }
    }

    /**
     * Réinitialiser une configuration à sa valeur par défaut
     */
    public function resetToDefault($id)
    {
        try {
            $setting = Setting::findOrFail($id);

            if ($setting->default_value !== null) {
                $setting->update([
                    'value' => $setting->default_value,
                    'updated_by' => auth()->id()
                ]);

                Setting::clearCache();
                CheckRequiredSettings::clearCache();

                return redirect()->back()
                    ->with('success', 'Configuration réinitialisée à sa valeur par défaut.');
            } else {
                return redirect()->back()
                    ->with('error', 'Aucune valeur par défaut définie pour cette configuration.');
            }

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Erreur lors de la réinitialisation de la configuration.');
        }
    }

    /**
     * Exporter les configurations
     */
    public function export()
    {
        try {
            $settings = Setting::all();
            $exportData = [
                'export_date' => now()->toISOString(),
                'exported_by' => auth()->user()->name,
                'settings' => $settings->toArray()
            ];

            $filename = 'esbtp_settings_' . now()->format('Y-m-d_H-i-s') . '.json';

            return response()->json($exportData)
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Erreur lors de l\'exportation des configurations.');
        }
    }

    /**
     * Importer les configurations
     */
    public function import(Request $request)
    {
        $request->validate([
            'settings_file' => 'required|file|mimes:json'
        ]);

        try {
            $file = $request->file('settings_file');
            $content = json_decode(file_get_contents($file->path()), true);

            if (!isset($content['settings'])) {
                return redirect()->back()
                    ->with('error', 'Format de fichier invalide.');
            }

            DB::beginTransaction();

            // Créer une sauvegarde avant l'importation
            $backup = SettingsBackup::createManualBackup(
                'Pre-Import Backup - ' . now()->format('Y-m-d H:i:s'),
                'Sauvegarde automatique avant importation',
                auth()->id()
            );

            $imported = 0;
            foreach ($content['settings'] as $settingData) {
                Setting::updateOrCreate(
                    ['key' => $settingData['key']],
                    array_merge($settingData, ['updated_by' => auth()->id()])
                );
                $imported++;
            }

            Setting::clearCache();
            CheckRequiredSettings::clearCache();

            DB::commit();

        return redirect()->route('esbtp.settings.index')
                ->with('success', "Importation réussie. {$imported} configurations importées.");

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Erreur lors de l\'importation des configurations', [
                'error' => $e->getMessage()
            ]);

            return redirect()->back()
                ->with('error', 'Erreur lors de l\'importation des configurations.');
        }
    }

    /**
     * Traiter la valeur selon le type
     */
    private function processSettingValue($value, $type, $request = null)
    {
        switch ($type) {
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'integer':
                return (int) $value;
            case 'json':
                return is_string($value) ? json_decode($value, true) : $value;
            case 'file':
                // Pour les fichiers, la valeur est traitée séparément dans la méthode update
                return $value;
            default:
                return $value;
        }
    }

    /**
     * Déterminer le dossier de stockage selon la clé du paramètre
     */
    private function getStorageFolderForSetting($settingKey)
    {
        // Mapper les clés de paramètres aux dossiers appropriés
        $settingFolders = [
            'school_logo' => 'logos',
            'school_favicon' => 'logos',
            'bulletin_logo' => 'logos',
            'header_logo' => 'logos',
            'signature_image' => 'documents',
            'watermark_image' => 'documents',
        ];

        return $settingFolders[$settingKey] ?? 'settings';
    }

    /**
     * Vérifier l'état des configurations
     */
    public function checkStatus()
    {
        $missingSettings = CheckRequiredSettings::getAllMissingSettings();
        $totalSettings = Setting::count();
        $activeSettings = Setting::where('is_active', true)->count();
        $requiredSettings = Setting::where('is_required', true)->count();

        return response()->json([
            'status' => empty($missingSettings) ? 'ok' : 'missing_required',
            'total_settings' => $totalSettings,
            'active_settings' => $activeSettings,
            'required_settings' => $requiredSettings,
            'missing_settings' => $missingSettings,
            'missing_count' => count($missingSettings)
        ]);
    }

    /**
     * Tester l'envoi des rappels automatiques (mode simulation)
     */
    public function testReminders()
    {
        try {
            // Exécuter la commande en mode test
            \Illuminate\Support\Facades\Artisan::call('reminders:send-inscription-paiement', ['--test' => true]);

            // Récupérer la sortie de la commande
            $output = \Illuminate\Support\Facades\Artisan::output();

            // Parser la sortie pour extraire les statistiques
            $inscriptionsFound = \App\Models\ESBTPInscription::where('status', 'en_attente')->count();
            $paiementsFound = \App\Models\ESBTPPaiement::where('status', 'en_attente')->count();

            // Simuler le comptage de rappels qui auraient été envoyés
            $inscriptionsSent = 0;
            $paiementsSent = 0;

            $firstDelayInscription = (int) \App\Models\ESBTPSystemSetting::getValue('reminder_inscription_first_delay', 3);
            $firstDelayPaiement = (int) \App\Models\ESBTPSystemSetting::getValue('reminder_paiement_first_delay', 2);

            foreach (\App\Models\ESBTPInscription::where('status', 'en_attente')->get() as $inscription) {
                $daysPending = now()->diffInDays($inscription->created_at);
                if ($daysPending >= $firstDelayInscription) {
                    $inscriptionsSent++;
                }
            }

            foreach (\App\Models\ESBTPPaiement::where('status', 'en_attente')->get() as $paiement) {
                $daysPending = now()->diffInDays($paiement->created_at);
                if ($daysPending >= $firstDelayPaiement) {
                    $paiementsSent++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Test exécuté avec succès',
                'data' => [
                    'inscriptions_found' => $inscriptionsFound,
                    'inscriptions_sent' => $inscriptionsSent,
                    'paiements_found' => $paiementsFound,
                    'paiements_sent' => $paiementsSent,
                    'output' => $output
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur test rappels: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du test: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lance le test MailPulse depuis les settings, avec destinataires de test uniquement.
     */
    public function testMailPulseNotification(Request $request, MailPulseTestNotificationService $service): JsonResponse
    {
        $payload = $request->validate([
            'event' => ['required', 'string', 'in:payment_received,payment_submitted,payment_rejected,absence_reported,grade_published,fee_reminder,registration_confirmed,re_registration_confirmed,bulletin_published,low_grades_alert,low_attendance_alert'],
            'channel' => ['required', 'string', 'in:email,whatsapp,both'],
            'dryRun' => ['sometimes', 'boolean'],
        ]);

        try {
            $result = $service->send(
                $payload['event'],
                $payload['channel'],
                (bool) ($payload['dryRun'] ?? true)
            );
        } catch (ValidationException $e) {
            return response()->json([
                'ok' => false,
                'message' => 'Configuration de test MailPulse incomplète.',
                'errors' => $e->errors(),
            ], 422);
        }

        return response()->json($result, $result['ok'] ? 200 : 502);
    }

    /**
     * Enregistre uniquement les réglages MailPulse depuis l'onglet dédié.
     */
    public function saveMailPulseSettings(Request $request): JsonResponse
    {
        $this->ensureMailPulseSettings();

        $validator = Validator::make($request->all(), [
            'setting_mailpulse_enabled' => ['nullable', 'in:0,1'],
            'setting_mailpulse_base_url' => ['nullable', 'url', 'max:255'],
            'setting_mailpulse_api_key' => ['nullable', 'string', 'max:500'],
            'setting_mailpulse_contacts_endpoint' => ['nullable', 'string', 'max:120'],
            'setting_mailpulse_messages_endpoint' => ['nullable', 'string', 'max:120'],
            'setting_mailpulse_sender_email' => ['nullable', 'email', 'max:255'],
            'setting_mailpulse_sender_name' => ['nullable', 'string', 'max:120'],
            'setting_mailpulse_default_language' => ['nullable', 'string', 'min:2', 'max:8'],
            'setting_mailpulse_timeout' => ['nullable', 'integer', 'min:5', 'max:120'],
            'setting_mailpulse_test_email' => ['nullable', 'email', 'max:255'],
            'setting_mailpulse_test_phone' => ['nullable', 'string', 'max:30'],
            'setting_mailpulse_test_phones' => ['nullable', 'string', 'max:1000'],
            'setting_mailpulse_test_email_recipients' => ['nullable', 'string', 'max:5000'],
            'setting_mailpulse_test_phone_recipients' => ['nullable', 'string', 'max:5000'],
            'setting_mailpulse_test_email_enabled' => ['nullable', 'in:0,1'],
            'setting_mailpulse_test_whatsapp_enabled' => ['nullable', 'in:0,1'],
            'setting_mailpulse_real_workflows_enabled' => ['nullable', 'in:0,1'],
        ]);

        $validator->after(function ($validator) use ($request) {
            $emailError = $this->mailPulseRecipientValidationError(
                $request->input('setting_mailpulse_test_email_recipients'),
                'email'
            );
            if ($emailError !== null) {
                $validator->errors()->add('setting_mailpulse_test_email_recipients', $emailError);
            }

            $phoneError = $this->mailPulseRecipientValidationError(
                $request->input('setting_mailpulse_test_phone_recipients'),
                'phone'
            );
            if ($phoneError !== null) {
                $validator->errors()->add('setting_mailpulse_test_phone_recipients', $phoneError);
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Certaines configurations MailPulse contiennent des erreurs.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $mailPulseKeys = [
            'mailpulse_enabled',
            'mailpulse_base_url',
            'mailpulse_api_key',
            'mailpulse_contacts_endpoint',
            'mailpulse_messages_endpoint',
            'mailpulse_sender_email',
            'mailpulse_sender_name',
            'mailpulse_default_language',
            'mailpulse_timeout',
            'mailpulse_test_email',
            'mailpulse_test_phone',
            'mailpulse_test_phones',
            'mailpulse_test_email_recipients',
            'mailpulse_test_phone_recipients',
            'mailpulse_test_email_enabled',
            'mailpulse_test_whatsapp_enabled',
            'mailpulse_real_workflows_enabled',
        ];

        try {
            DB::beginTransaction();

            $updatedSettings = [];
            $apiKeyReceived = false;
            $apiKeyReceivedLength = 0;
            foreach ($mailPulseKeys as $settingKey) {
                $requestKey = 'setting_' . $settingKey;
                if (! $request->has($requestKey)) {
                    continue;
                }

                $value = $request->input($requestKey, '');
                if (is_string($value)) {
                    $value = trim($value);
                }

                if ($settingKey === 'mailpulse_api_key' && $value === '') {
                    continue;
                }

                if ($settingKey === 'mailpulse_api_key') {
                    $apiKeyReceived = true;
                    $apiKeyReceivedLength = strlen((string) $value);
                }

                Setting::updateOrCreate(
                    ['key' => $settingKey],
                    [
                        'value' => (string) $value,
                        'type' => $this->mailPulseSettingType($settingKey),
                        'group' => 'mailpulse',
                        'category' => 'mailpulse',
                        'description' => $this->mailPulseSettingDescription($settingKey),
                        'is_active' => true,
                        'updated_by' => auth()->id(),
                    ]
                );
                $updatedSettings[] = $settingKey;
            }

            $this->syncMailPulseLegacyRecipients($request, $updatedSettings);

            Setting::clearCache();
            CheckRequiredSettings::clearCache();

            DB::commit();

            Setting::clearCache();
            $apiKeyState = $this->mailPulseApiKeyState();
            if ($apiKeyReceived && ! $apiKeyState['configured']) {
                Log::error('MailPulse API key received but not persisted', [
                    'user_id' => auth()->id(),
                    'received_length' => $apiKeyReceivedLength,
                    'updated_keys' => array_values(array_unique($updatedSettings)),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => "La clé API a été reçue, mais elle n'est pas persistée côté serveur. Relancez après cache clear, sinon vérifiez la table settings.",
                    'updated_count' => count(array_unique($updatedSettings)),
                    'updated_keys' => array_values(array_unique($updatedSettings)),
                    'api_key_received' => true,
                    'api_key_received_length' => $apiKeyReceivedLength,
                    'api_key_configured' => false,
                    'api_key_source' => $apiKeyState['source'],
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => $apiKeyState['configured']
                    ? 'Paramètres MailPulse enregistrés. Clé API active.'
                    : "Paramètres MailPulse enregistrés, mais aucune clé API active n'est stockée.",
                'updated_count' => count(array_unique($updatedSettings)),
                'updated_keys' => array_values(array_unique($updatedSettings)),
                'api_key_received' => $apiKeyReceived,
                'api_key_received_length' => $apiKeyReceivedLength,
                'api_key_configured' => $apiKeyState['configured'],
                'api_key_source' => $apiKeyState['source'],
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Erreur sauvegarde MailPulse', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => "Erreur pendant l'enregistrement MailPulse.",
            ], 500);
        }
    }

    private function mailPulseApiKeyState(): array
    {
        $value = Setting::where('key', 'mailpulse_api_key')
            ->where('is_active', true)
            ->value('value');

        if (is_string($value) && trim($value) !== '') {
            return [
                'configured' => true,
                'source' => 'settings',
            ];
        }

        $configValue = config('services.mailpulse.api_key', '');
        if (is_string($configValue) && trim($configValue) !== '') {
            return [
                'configured' => true,
                'source' => 'env',
            ];
        }

        return [
            'configured' => false,
            'source' => 'none',
        ];
    }

    private function mailPulseRecipientValidationError(?string $json, string $type): ?string
    {
        if ($json === null || trim($json) === '') {
            return null;
        }

        $decoded = json_decode($json, true);
        if (! is_array($decoded)) {
            return 'Le format des destinataires MailPulse est invalide.';
        }

        foreach ($decoded as $recipient) {
            if (! is_array($recipient)) {
                return 'Chaque destinataire MailPulse doit contenir une valeur et son statut.';
            }

            $value = trim((string) ($recipient['value'] ?? ''));
            if ($value === '') {
                continue;
            }

            if ($type === 'email' && ! filter_var($value, FILTER_VALIDATE_EMAIL)) {
                return 'Un email de test MailPulse est invalide.';
            }

            if ($type === 'phone' && PhoneNormalizer::toE164($value) === null) {
                return 'Un numéro WhatsApp de test est invalide. Indiquez-le en entier, avec son indicatif pays.';
            }
        }

        return null;
    }

    private function mailPulseSettingType(string $settingKey): string
    {
        return match ($settingKey) {
            'mailpulse_enabled',
            'mailpulse_test_email_enabled',
            'mailpulse_test_whatsapp_enabled',
            'mailpulse_real_workflows_enabled' => 'boolean',
            'mailpulse_timeout' => 'integer',
            default => 'string',
        };
    }

    private function mailPulseSettingDescription(string $settingKey): string
    {
        return match ($settingKey) {
            'mailpulse_enabled' => 'Activer les envois MailPulse pour ce tenant',
            'mailpulse_base_url' => 'URL de base MailPulse',
            'mailpulse_api_key' => 'Clé API MailPulse',
            'mailpulse_contacts_endpoint' => 'Endpoint MailPulse contacts',
            'mailpulse_messages_endpoint' => 'Endpoint MailPulse messages',
            'mailpulse_sender_email' => 'Email expéditeur MailPulse',
            'mailpulse_sender_name' => 'Nom expéditeur MailPulse',
            'mailpulse_default_language' => 'Langue par défaut MailPulse',
            'mailpulse_timeout' => 'Timeout MailPulse en secondes',
            'mailpulse_test_email' => 'Email de test MailPulse',
            'mailpulse_test_phone' => 'Téléphone de test MailPulse',
            'mailpulse_test_phones' => 'Téléphones de test MailPulse',
            'mailpulse_test_email_recipients' => 'Emails de test MailPulse avec activation',
            'mailpulse_test_phone_recipients' => 'Téléphones de test MailPulse avec activation',
            'mailpulse_test_email_enabled' => 'Activer les tests email MailPulse',
            'mailpulse_test_whatsapp_enabled' => 'Activer les tests WhatsApp MailPulse',
            'mailpulse_real_workflows_enabled' => 'Activer MailPulse sur les workflows parents réels',
            default => $settingKey,
        };
    }

    private function syncMailPulseLegacyRecipients(Request $request, array &$updatedSettings): void
    {
        $emails = $this->mailPulseActiveRecipientValues(
            $request->input('setting_mailpulse_test_email_recipients'),
            'email'
        );
        if ($emails !== []) {
            Setting::where('key', 'mailpulse_test_email')->update([
                'value' => $emails[0],
                'updated_by' => auth()->id(),
            ]);
            $updatedSettings[] = 'mailpulse_test_email';
        }

        $phones = $this->mailPulseActiveRecipientValues(
            $request->input('setting_mailpulse_test_phone_recipients'),
            'phone'
        );
        if ($phones !== []) {
            Setting::where('key', 'mailpulse_test_phone')->update([
                'value' => $phones[0],
                'updated_by' => auth()->id(),
            ]);
            Setting::where('key', 'mailpulse_test_phones')->update([
                'value' => implode("\n", $phones),
                'updated_by' => auth()->id(),
            ]);
            $updatedSettings[] = 'mailpulse_test_phone';
            $updatedSettings[] = 'mailpulse_test_phones';
        }
    }

    private function mailPulseActiveRecipientValues(?string $json, string $type): array
    {
        if ($json === null || trim($json) === '') {
            return [];
        }

        $decoded = json_decode($json, true);
        if (! is_array($decoded)) {
            return [];
        }

        $values = [];
        foreach ($decoded as $recipient) {
            if (! is_array($recipient) || ! ($recipient['enabled'] ?? true)) {
                continue;
            }

            $value = trim((string) ($recipient['value'] ?? ''));
            if ($value === '') {
                continue;
            }

            if ($type === 'email' && filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $values[strtolower($value)] = $value;
            }

            if ($type === 'phone') {
                $phone = PhoneNormalizer::toE164($value);
                if ($phone !== null) {
                    $values[$phone] = $phone;
                }
            }
        }

        return array_values($values);
    }

    /**
     * Génère un PDF d'aperçu en utilisant les overrides fournis dans la requête
     * (sans persister). Permet à l'admin tenant de prévisualiser ses paramètres
     * PDF dans une nouvelle tab avant de sauvegarder.
     *
     * Phase 9 — Customisation PDF tenant.
     */
    public function pdfPreview(Request $request)
    {
        $overrides = $this->extractPdfOverrides($request);

        $pdf = Pdf::loadView('pdf.preview-sample', [
            'overrides' => $overrides,
        ])->setPaper('A4', 'portrait');

        return new \Illuminate\Http\Response(
            $pdf->output(),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="apercu-pdf-' . now()->format('Ymd-His') . '.pdf"',
                'X-Robots-Tag' => 'noindex, nofollow',
            ]
        );
    }

    /**
     * Convertit les inputs `pdf_*` du formulaire en array d'overrides matchant
     * les clés de SettingsHelper::getPdfSettings() (sans préfixe `pdf_`).
     * Booléens normalisés, marges/sizes castés en int, opacity en float.
     */
    private function extractPdfOverrides(Request $request): array
    {
        $defaults = \App\Helpers\SettingsHelper::getPdfDefaults();
        $overrides = [];

        foreach ($defaults as $key => $defaultValue) {
            // Le form existant prefixe les inputs avec `setting_` (form principal /esbtp/settings).
            // Le form du bouton "Aperçu" peut envoyer la clé directement.
            $value = $request->input($key, $request->input('setting_' . $key));
            if ($value === null) {
                continue;
            }
            $shortKey = str_starts_with($key, 'pdf_') ? substr($key, 4) : $key;

            // Normalisation par type. show_generator_name ajouté aux booléens
            // (oubli initial : le toggle "Généré par X" ne s'appliquait pas en preview).
            $booleanKeys = ['show_logo', 'show_director_signature', 'show_pagination', 'show_generator_name'];
            $intKeys = ['logo_size', 'signature_height', 'font_size', 'margin_top', 'margin_bottom', 'margin_left', 'margin_right', 'watermark_rotation'];

            $overrides[$shortKey] = match (true) {
                in_array($shortKey, $booleanKeys, true)
                    => in_array($value, ['1', 1, true, 'true', 'on'], true),
                in_array($shortKey, $intKeys, true)
                    => (int) $value,
                $shortKey === 'watermark_opacity'
                    => (float) $value,
                default => (string) $value,
            };
        }

        return $overrides;
    }

    private function ensureAttendanceNoteSettings(): void
    {
        // Barème 5 paliers (étendu 03/06/2026). Le palier legacy "two_or_more" reste
        // créé pour rétrocompat des appels code qui le lisent encore.
        $attendanceDefaults = [
            'attendance_note_zero_unjustified' => ['value' => '0.13', 'description' => 'Barème assiduité pour 0 heure d’absence', 'sort_order' => 121],
            'attendance_note_one_unjustified' => ['value' => '0.00', 'description' => 'Barème assiduité pour jusqu’à 1 heure d’absence non justifiée', 'sort_order' => 122],
            'attendance_note_two_unjustified' => ['value' => '-0.13', 'description' => 'Barème assiduité pour 2 à moins de 3 heures d’absence non justifiée', 'sort_order' => 123],
            'attendance_note_three_to_four_unjustified' => ['value' => '-0.39', 'description' => 'Barème assiduité pour 3 à moins de 5 heures d’absence non justifiée', 'sort_order' => 124],
            'attendance_note_five_or_more_unjustified' => ['value' => '-0.50', 'description' => 'Barème assiduité à partir de 5 heures d’absence non justifiée', 'sort_order' => 125],
            'attendance_note_two_or_more_unjustified' => ['value' => '-0.13', 'description' => 'Barème assiduité legacy (2 heures ou plus, conservé pour rétrocompatibilité)', 'sort_order' => 126],
        ];

        foreach ($attendanceDefaults as $key => $attrs) {
            Setting::firstOrCreate(
                ['key' => $key],
                [
                    'value' => $attrs['value'],
                    'type' => 'float',
                    'group' => 'bulletin',
                    'category' => 'bulletin',
                    'description' => $attrs['description'],
                    'is_required' => false,
                    'default_value' => $attrs['value'],
                    'validation_rules' => ['nullable', 'numeric', 'min:-20', 'max:20'],
                    'sort_order' => $attrs['sort_order'],
                ]
            );
        }

        // Règle d'assiduité à tranches d'heures configurables (JSON). Seedée depuis
        // les valeurs legacy DE CE TENANT → parité de comportement au premier save
        // (les 5 clés paliers ci-dessus restent la source du seed + le fallback).
        if (! Setting::where('key', 'attendance_note_rules')->exists()) {
            $seed = \App\Support\Attendance\AttendanceNoteRule::fromLegacySettings([
                'zero_unjustified' => (float) \App\Helpers\SettingsHelper::get('attendance_note_zero_unjustified', '0.13'),
                'one_unjustified' => (float) \App\Helpers\SettingsHelper::get('attendance_note_one_unjustified', '0.00'),
                'two_unjustified' => (float) \App\Helpers\SettingsHelper::get('attendance_note_two_unjustified', \App\Helpers\SettingsHelper::get('attendance_note_two_or_more_unjustified', '-0.13')),
                'three_to_four_unjustified' => (float) \App\Helpers\SettingsHelper::get('attendance_note_three_to_four_unjustified', '-0.39'),
                'five_or_more_unjustified' => (float) \App\Helpers\SettingsHelper::get('attendance_note_five_or_more_unjustified', '-0.50'),
            ])->toArray();

            Setting::create([
                'key' => 'attendance_note_rules',
                'value' => json_encode($seed),
                'type' => 'json',
                'group' => 'bulletin',
                'category' => 'bulletin',
                'description' => "Barème d'assiduité configurable : tranches d'heures (justifiées / non justifiées) et note par tranche",
                'is_required' => false,
                'default_value' => json_encode($seed),
                'validation_rules' => null,
                'sort_order' => 127,
            ]);
        }
    }

    private function ensureMentionSettings(): void
    {
        if (! Setting::where('key', BulletinMentionResolver::SETTING_KEY)->exists()) {
            $rules = BulletinMentionResolver::fromLegacySettings(
                static fn (string $key, mixed $default) => \App\Helpers\SettingsHelper::get($key, $default)
            );

            Setting::create([
                'key' => BulletinMentionResolver::SETTING_KEY,
                'value' => json_encode($rules),
                'type' => 'json',
                'group' => 'bulletin',
                'category' => 'bulletin',
                'description' => 'Règles de mentions bulletin (libellé, min, max, source)',
                'is_required' => false,
                'default_value' => json_encode(BulletinMentionResolver::catalog()),
                'validation_rules' => null,
                'sort_order' => 51,
            ]);
        }

        foreach ([
            BulletinMentionResolver::AUTH_TEXT_KEY => [
                'value' => BulletinMentionResolver::AUTH_TEXT_DEFAULT,
                'description' => 'Texte anti-duplicata en pied de bulletin',
            ],
            BulletinMentionResolver::EDITION_LABEL_KEY => [
                'value' => BulletinMentionResolver::EDITION_LABEL_DEFAULT,
                'description' => 'Libellé de la date d\'édition en pied de bulletin',
            ],
            BulletinMentionResolver::FAIT_A_KEY => [
                'value' => '',
                'description' => 'Lieu du « Fait à » sur le bulletin',
            ],
            BulletinMentionResolver::FAIT_LE_MODE_KEY => [
                'value' => 'edition',
                'description' => 'Source de la date du « Fait le » (edition, empty, custom)',
            ],
            BulletinMentionResolver::FAIT_LE_DATE_KEY => [
                'value' => '',
                'description' => 'Date personnalisée du « Fait le »',
            ],
        ] as $key => $attrs) {
            Setting::firstOrCreate(
                ['key' => $key],
                [
                    'value' => $attrs['value'],
                    'type' => 'string',
                    'group' => 'bulletin',
                    'category' => 'bulletin',
                    'description' => $attrs['description'],
                    'is_required' => false,
                    'default_value' => $attrs['value'],
                    'validation_rules' => ['nullable', 'string'],
                    'sort_order' => 52,
                ]
            );
        }
    }

    private function ensureMailPulseSettings(): void
    {
        $mailPulseSettings = [
            'mailpulse_enabled' => ['value' => '0', 'type' => 'boolean', 'description' => 'Activer les envois MailPulse pour ce tenant', 'rules' => ['nullable', 'in:0,1'], 'sort' => 300],
            'mailpulse_base_url' => ['value' => 'https://mailpulse-two.vercel.app', 'type' => 'string', 'description' => 'URL de base MailPulse', 'rules' => ['nullable', 'url', 'max:255'], 'sort' => 301],
            'mailpulse_api_key' => ['value' => '', 'type' => 'string', 'description' => 'Cle API MailPulse', 'rules' => ['nullable', 'string', 'max:500'], 'sort' => 302],
            'mailpulse_contacts_endpoint' => ['value' => '/api/v1/contacts', 'type' => 'string', 'description' => 'Endpoint MailPulse contacts', 'rules' => ['nullable', 'string', 'max:120'], 'sort' => 303],
            'mailpulse_messages_endpoint' => ['value' => '/api/v1/messages', 'type' => 'string', 'description' => 'Endpoint MailPulse messages', 'rules' => ['nullable', 'string', 'max:120'], 'sort' => 304],
            'mailpulse_sender_email' => ['value' => '', 'type' => 'string', 'description' => 'Email expediteur MailPulse', 'rules' => ['nullable', 'email', 'max:255'], 'sort' => 305],
            'mailpulse_sender_name' => ['value' => 'KLASSCI', 'type' => 'string', 'description' => 'Nom expediteur MailPulse', 'rules' => ['nullable', 'string', 'max:120'], 'sort' => 306],
            'mailpulse_default_language' => ['value' => 'fr', 'type' => 'string', 'description' => 'Langue par defaut MailPulse', 'rules' => ['nullable', 'string', 'min:2', 'max:8'], 'sort' => 307],
            'mailpulse_timeout' => ['value' => '20', 'type' => 'integer', 'description' => 'Timeout MailPulse en secondes', 'rules' => ['nullable', 'integer', 'min:5', 'max:120'], 'sort' => 308],
            'mailpulse_test_email' => ['value' => '', 'type' => 'string', 'description' => 'Email de test MailPulse', 'rules' => ['nullable', 'email', 'max:255'], 'sort' => 309],
            'mailpulse_test_phone' => ['value' => '', 'type' => 'string', 'description' => 'Telephone de test MailPulse', 'rules' => ['nullable', 'string', 'max:30'], 'sort' => 310],
            'mailpulse_test_phones' => ['value' => '', 'type' => 'string', 'description' => 'Telephones de test MailPulse', 'rules' => ['nullable', 'string', 'max:1000'], 'sort' => 311],
            'mailpulse_test_email_recipients' => ['value' => '', 'type' => 'string', 'description' => 'Emails de test MailPulse avec activation', 'rules' => ['nullable', 'string', 'max:5000'], 'sort' => 312],
            'mailpulse_test_phone_recipients' => ['value' => '', 'type' => 'string', 'description' => 'Telephones de test MailPulse avec activation', 'rules' => ['nullable', 'string', 'max:5000'], 'sort' => 313],
            'mailpulse_test_email_enabled' => ['value' => '1', 'type' => 'boolean', 'description' => 'Activer les tests email MailPulse', 'rules' => ['nullable', 'in:0,1'], 'sort' => 314],
            'mailpulse_test_whatsapp_enabled' => ['value' => '1', 'type' => 'boolean', 'description' => 'Activer les tests WhatsApp MailPulse', 'rules' => ['nullable', 'in:0,1'], 'sort' => 315],
            'mailpulse_real_workflows_enabled' => ['value' => '0', 'type' => 'boolean', 'description' => 'Activer MailPulse sur les workflows parents reels', 'rules' => ['nullable', 'in:0,1'], 'sort' => 316],
        ];

        foreach ($mailPulseSettings as $key => $attrs) {
            Setting::firstOrCreate(
                ['key' => $key],
                [
                    'value' => $attrs['value'],
                    'type' => $attrs['type'],
                    'group' => 'mailpulse',
                    'category' => 'mailpulse',
                    'description' => $attrs['description'],
                    'is_required' => false,
                    'default_value' => $attrs['value'],
                    'validation_rules' => $attrs['rules'],
                    'sort_order' => $attrs['sort'],
                ]
            );
        }
    }

}
