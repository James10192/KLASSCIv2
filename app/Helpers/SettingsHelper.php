<?php

namespace App\Helpers;

use App\Models\Setting;

class SettingsHelper
{
    /**
     * Récupère un paramètre avec une valeur par défaut
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get($key, $default = null)
    {
        return Setting::get($key, $default);
    }

    /**
     * Définit un paramètre
     *
     * @param string $key
     * @param mixed $value
     * @param string $group
     * @return bool
     */
    public static function set($key, $value, $group = 'general')
    {
        return Setting::set($key, $value, $group);
    }

    /**
     * Définit un paramètre ou le crée s'il n'existe pas
     *
     * @param string $key
     * @param mixed $value
     * @param string $group
     * @param string $type
     * @return bool
     */
    public static function setOrCreate($key, $value, $group = 'general', $type = 'string')
    {
        return Setting::setOrCreate($key, $value, $group, $type);
    }

    /**
     * Récupère tous les paramètres
     *
     * @return array
     */
    public static function all()
    {
        try {
            $settings = Setting::all();
            $result = [];

            foreach ($settings as $setting) {
                $result[$setting->key] = $setting->value;
            }

            return $result;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Récupère tous les paramètres d'un groupe
     *
     * @param string $group
     * @return array
     */
    public static function getGroup($group)
    {
        try {
            $settings = Setting::where('group', $group)->get();
            $result = [];

            foreach ($settings as $setting) {
                $result[$setting->key] = $setting->value;
            }

            return $result;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Récupère les informations de l'établissement
     *
     * @return array
     */
    public static function getSchoolInfo()
    {
        return [
            'name' => self::get('school_name', config('app.name', 'KLASSCI')),
            'acronym' => self::get('school_acronym', config('app.name', 'KLASSCI')),
            'address' => self::get('school_address', ''),
            'city' => self::get('school_city', ''),
            'postal_code' => self::get('school_postal_code', ''),
            'country' => self::get('school_country', 'Côte d\'Ivoire'),
            'phone' => self::get('school_phone', ''),
            'mobile' => self::get('school_mobile', ''),
            'email' => self::get('school_email', ''),
            'website' => self::get('school_website', ''),
            'logo' => self::get('school_logo', ''),
            'director_name' => self::get('director_name', ''),
            'director_title' => self::get('director_title', 'Directeur Général'),
        ];
    }

    /**
     * Chemin du fichier logo configuré par l'établissement, ou null.
     *
     * Ne retombe JAMAIS sur la marque KLASSCI : cette méthode répond à la
     * question « cette école a-t-elle un logo à elle ? », et le site vitrine en
     * dépend pour n'afficher que de vrais logos d'établissement. Le repli
     * générique appartient à resolveLogoBase64(), qui doit toujours rendre une
     * image parce qu'un bulletin sans en-tête n'est pas présentable.
     *
     * Plusieurs candidats sont tentés : les tenants historiques stockent le
     * chemin tantôt avec le préfixe `storage/`, tantôt sans, tantôt réduit au
     * seul nom de fichier.
     *
     * Mémoïsé par valeur du réglage, et non par un simple drapeau : ainsi un
     * changement de logo en cours de processus (assistant de configuration,
     * suite de tests) n'est pas masqué par un cache resté sur l'ancien fichier.
     */
    public static function resolveLogoPath(): ?string
    {
        static $memo = [];

        $logoPath = (string) self::get('school_logo', '');

        if (array_key_exists($logoPath, $memo)) {
            return $memo[$logoPath];
        }

        return $memo[$logoPath] = self::premierFichierLisible(self::candidatsLogo($logoPath));
    }

    /**
     * Les emplacements où un logo d'établissement a pu être écrit, du plus
     * probable au plus ancien.
     *
     * @return list<string>
     */
    private static function candidatsLogo(string $logoPath): array
    {
        if ($logoPath === '') {
            return [];
        }

        $normalized = str_replace('\\', '/', ltrim($logoPath, '/'));
        $relative = preg_replace('#^storage/#', '', $normalized);
        $basename = basename($relative);

        return [
            storage_path('app/public/' . $normalized),
            storage_path('app/public/' . $relative),
            storage_path('app/public/logos/' . $basename),
            public_path('storage/' . $normalized),
            public_path('storage/' . $relative),
            public_path('storage/logos/' . $basename),
            public_path($normalized),
        ];
    }

    /**
     * Le premier candidat qui est un fichier réellement lisible.
     *
     * `is_file` et pas seulement `file_exists` : les candidats « .../logos/ »
     * construits à partir d'un chemin sans nom de fichier désignent un dossier,
     * qui existe mais ne s'affiche pas.
     *
     * @param  list<string>  $candidats
     */
    private static function premierFichierLisible(array $candidats): ?string
    {
        foreach ($candidats as $candidat) {
            if ($candidat !== '' && is_file($candidat) && is_readable($candidat)) {
                return $candidat;
            }
        }

        return null;
    }

    /**
     * Le type MIME d'une image, déduit de son extension.
     *
     * Déduit et non sniffé : le fichier a été déposé par un formulaire de
     * réglages qui valide déjà le type, et `mime_content_type` n'est pas
     * garanti présent sur les hébergements mutualisés que KLASSCI vise.
     */
    public static function mimeImage(string $chemin): string
    {
        return match (strtolower(pathinfo($chemin, PATHINFO_EXTENSION) ?: 'png')) {
            'jpg', 'jpeg' => 'image/jpeg',
            'svg' => 'image/svg+xml',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            default => 'image/png',
        };
    }

    /**
     * Résout le logo de l'école en base64 pour inlining (DomPDF + previews web).
     *
     * Mémoïsé par requête : le base64 d'un logo (~50KB) appelé sur 8+ pages d'un même
     * export évite les file_get_contents répétés.
     *
     * @return array{mime: string, ext: string, b64: string, data_uri: string}|null
     */
    public static function resolveLogoBase64(): ?array
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache === false ? null : $cache;
        }

        // Repli generique : la marque KLASSCI, jamais le logo d'un
        // etablissement. esbtp_logo.png figurait ici en premier : toute ecole
        // sans logo configure affichait donc celui de l'ESBTP, sur ses
        // bulletins, ses attestations et son apercu de partage.
        $chemin = self::resolveLogoPath()
            ?? self::premierFichierLisible([public_path('images/LOGO-KLASSCI-PNG.png')]);

        if ($chemin === null) {
            $cache = false;

            return null;
        }

        $contents = @file_get_contents($chemin);

        if ($contents === false) {
            $cache = false;

            return null;
        }

        $mime = self::mimeImage($chemin);
        $b64 = base64_encode($contents);

        return $cache = [
            'mime' => $mime,
            'ext' => strtolower(pathinfo($chemin, PATHINFO_EXTENSION) ?: 'png'),
            'b64' => $b64,
            'data_uri' => 'data:' . $mime . ';base64,' . $b64,
        ];
    }

    /**
     * Récupère les paramètres PDF
     *
     * @return array
     */
    public static function getPdfSettings()
    {
        return [
            'header_text' => self::get('pdf_header_text', ''),
            'footer_text' => self::get('pdf_footer_text', ''),
            'footer_custom_text' => self::get('pdf_footer_custom_text', ''),
            'show_logo' => self::get('pdf_show_logo', '1') === '1',
            'logo_position' => self::get('pdf_logo_position', 'left'),
            'logo_size' => (int) self::get('pdf_logo_size', '60'),
            'signature_director' => self::get('pdf_signature_director', ''),
            'signature_secretary' => self::get('pdf_signature_secretary', ''),
            'signature_height' => (int) self::get('pdf_signature_height', '80'),
            'show_director_signature' => self::get('pdf_show_director_signature', '1') === '1',
            'show_generator_name' => self::get('pdf_show_generator_name', '1') === '1',
            'show_pagination' => self::get('pdf_show_pagination', '1') === '1',
            'watermark' => self::get('pdf_watermark', ''),
            'watermark_opacity' => (float) self::get('pdf_watermark_opacity', '0.05'),
            'watermark_rotation' => (int) self::get('pdf_watermark_rotation', '-30'),
            'font_size' => (int) self::get('pdf_font_size', '12'),
            'margin_top' => (int) self::get('pdf_margin_top', '20'),
            'margin_bottom' => (int) self::get('pdf_margin_bottom', '20'),
            'margin_left' => (int) self::get('pdf_margin_left', '15'),
            'margin_right' => (int) self::get('pdf_margin_right', '15'),
            'primary_color' => self::get('pdf_primary_color', '#0453cb'),
            'secondary_color' => self::get('pdf_secondary_color', '#64748b'),
            'accent_color' => $accent = self::get('pdf_accent_color', '#f59e0b'),
            'text_color' => self::get('pdf_text_color', '#1f2937'),
            'header_bg_color' => $headerBg = self::get('pdf_header_bg_color', '#0453cb'),
            'header_text_color_raw' => $headerText = self::get('pdf_header_text_color', '#ffffff'),
            'header_text_color' => $headerTextOnBg = self::contrastingText($headerBg, $headerText),
            'header_text_on_bg' => $headerTextOnBg,
            'header_text_on_primary' => self::contrastingText(
                self::get('pdf_primary_color', '#0453cb'),
                $headerText
            ),
            // Les pastilles de statut sont posees sur la couleur d'accent, qui est
            // souvent claire (ambre par defaut) : le blanc n'y tient pas.
            'text_on_accent' => self::contrastingText($accent, $headerText),
        ];
    }

    /**
     * Luminance relative du blanc — borne haute de l'echelle WCAG.
     */
    private const LUMINANCE_BLANC = 1.0;

    /**
     * Luminance relative du noir — borne basse de l'echelle WCAG.
     */
    private const LUMINANCE_NOIR = 0.0;

    /**
     * Contraste minimum exige par WCAG 2.1 (critere 1.4.3, niveau AA) pour du
     * texte de taille normale. Le seuil assoupli de 3:1 ne vaut que pour du
     * "grand texte" (>= 18.66px gras ou >= 24px) : les libelles de KPI d'un PDF
     * font 7 a 8px, ils relevent donc bien de l'exigence stricte.
     */
    public const CONTRASTE_MINIMUM = 4.5;

    /**
     * Couleur de texte lisible sur un fond donne.
     *
     * Le fond etant choisi par l'etablissement (parametres PDF), la couleur du
     * texte ne peut pas etre decretee : elle se deduit du fond.
     *
     * Methode — WCAG 2.1, "relative luminance" et "contrast ratio" :
     *   1. chaque canal RVB est normalise dans [0,1] puis linearise :
     *      c <= 0.03928  ->  c / 12.92
     *      sinon         ->  ((c + 0.055) / 1.055) ^ 2.4
     *   2. L = 0.2126*R + 0.7152*V + 0.0722*B
     *   3. contraste = (L_clair + 0.05) / (L_sombre + 0.05), borne entre 1:1 et 21:1
     *
     * Ordre de decision :
     *   1. la couleur souhaitee, si elle atteint deja le seuil ;
     *   2. sinon le sombre de la charte, s'il l'atteint ;
     *   3. sinon le blanc, s'il l'atteint ;
     *   4. sinon — fonds de demi-teinte, ou aucun des deux ne passe — celui du
     *      sombre, du blanc ou du noir pur qui se detache le mieux.
     *
     * L'etape 4 est un aveu d'impossibilite, pas un choix : entre L≈0.175 et
     * L≈0.183, aucune couleur n'atteint 4.5:1 sur ce fond. On rend alors le
     * maximum atteignable plutot qu'une valeur arbitraire. Le noir pur n'est
     * convoque qu'a ce stade, pour ne pas remplacer partout le sombre de la
     * charte par du #000000 sur simple avantage decimal.
     *
     * Fond illisible (null, vide, hexadecimal invalide) : on ne devine pas. DomPDF
     * ignore purement et simplement une `background-color` invalide, la zone reste
     * donc blanche comme le papier — on rend du texte sombre, jamais du blanc.
     *
     * @param  string|null  $background  Couleur de fond, hexadecimal court (#abc) ou long (#aabbcc)
     * @param  string  $preferred  Couleur souhaitee si elle est suffisamment lisible
     * @param  string  $dark  Repli sombre utilise quand le blanc ne passe pas
     * @param  float  $minRatio  Contraste minimum exige (defaut : WCAG AA texte normal)
     */
    public static function contrastingText(
        ?string $background,
        string $preferred = '#ffffff',
        string $dark = '#111827',
        float $minRatio = self::CONTRASTE_MINIMUM
    ): string {
        $bgLum = self::relativeLuminance($background);

        if ($bgLum === null) {
            return $dark;
        }

        $ratioAvec = static fn (?float $lum): float => $lum === null
            ? 0.0
            : self::contrastRatio($bgLum, $lum);

        $preferredRatio = $ratioAvec(self::relativeLuminance($preferred));
        if ($preferredRatio >= $minRatio) {
            return $preferred;
        }

        $darkRatio = $ratioAvec(self::relativeLuminance($dark));
        if ($darkRatio >= $minRatio) {
            return $dark;
        }

        $blancRatio = self::contrastRatio($bgLum, self::LUMINANCE_BLANC);
        if ($blancRatio >= $minRatio) {
            return '#ffffff';
        }

        // Aucun candidat n'atteint le seuil : on rend le moins mauvais.
        $noirRatio = self::contrastRatio($bgLum, self::LUMINANCE_NOIR);
        $meilleur = max($darkRatio, $blancRatio, $noirRatio);

        if ($meilleur === $darkRatio) {
            return $dark;
        }

        return $meilleur === $noirRatio ? '#000000' : '#ffffff';
    }

    /**
     * Rapport de contraste WCAG entre deux luminances relatives : de 1:1 (identiques)
     * a 21:1 (noir sur blanc).
     */
    public static function contrastRatio(float $lumA, float $lumB): float
    {
        $lighter = max($lumA, $lumB);
        $darker = min($lumA, $lumB);

        return ($lighter + 0.05) / ($darker + 0.05);
    }

    /**
     * Luminance relative WCAG d'une couleur hexadecimale, ou null si la valeur
     * n'est pas exploitable (null, vide, format inconnu). Le null est significatif :
     * il distingue "fond noir" (0.0) de "fond indeterminable".
     *
     * Limite assumee : seul l'hexadecimal est reconnu. Une couleur nommee ou une
     * notation rgb() renvoie null — l'ecran de parametres n'expose qu'un selecteur
     * hexadecimal, et rendre du texte sombre sur une couleur inconnue reste le
     * choix sur sur un papier blanc.
     */
    public static function relativeLuminance(?string $hex): ?float
    {
        if ($hex === null) {
            return null;
        }

        $hex = ltrim(trim($hex), '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }
        if (strlen($hex) !== 6 || ! ctype_xdigit($hex)) {
            return null;
        }
        $channel = static function (string $part): float {
            $value = hexdec($part) / 255;

            return $value <= 0.03928 ? $value / 12.92 : (($value + 0.055) / 1.055) ** 2.4;
        };

        return 0.2126 * $channel(substr($hex, 0, 2))
            + 0.7152 * $channel(substr($hex, 2, 2))
            + 0.0722 * $channel(substr($hex, 4, 2));
    }

    /**
     * Defaults stricts du PDF (sans aller chercher en base).
     * Utilisé pour le bouton "Réinitialiser défauts" et le formulaire d'aperçu.
     */
    public static function getPdfDefaults(): array
    {
        return [
            'pdf_header_text' => '',
            'pdf_footer_text' => '',
            'pdf_footer_custom_text' => '',
            'pdf_show_logo' => '1',
            'pdf_logo_position' => 'left',
            'pdf_logo_size' => '60',
            'pdf_show_director_signature' => '1',
            'pdf_show_generator_name' => '1',
            'pdf_signature_height' => '80',
            'pdf_show_pagination' => '1',
            'pdf_watermark' => '',
            'pdf_watermark_opacity' => '0.05',
            'pdf_watermark_rotation' => '-30',
            'pdf_font_size' => '12',
            'pdf_margin_top' => '20',
            'pdf_margin_bottom' => '20',
            'pdf_margin_left' => '15',
            'pdf_margin_right' => '15',
            'pdf_primary_color' => '#0453cb',
            'pdf_secondary_color' => '#64748b',
            'pdf_accent_color' => '#f59e0b',
            'pdf_text_color' => '#1f2937',
            'pdf_header_bg_color' => '#0453cb',
            'pdf_header_text_color' => '#ffffff',
        ];
    }

    /**
     * Récupère les couleurs du thème
     *
     * @return array
     */
    public static function getThemeColors()
    {
        return [
            'primary' => self::get('theme_primary_color', '#007bff'),
            'secondary' => self::get('theme_secondary_color', '#6c757d'),
            'success' => self::get('theme_success_color', '#28a745'),
            'danger' => self::get('theme_danger_color', '#dc3545'),
            'warning' => self::get('theme_warning_color', '#ffc107'),
            'info' => self::get('theme_info_color', '#17a2b8'),
            'sidebar' => self::get('sidebar_color', '#343a40'),
            'navbar' => self::get('navbar_color', '#ffffff'),
            'background' => self::get('background_color', '#f8f9fa'),
            'text' => self::get('text_color', '#212529'),
            'link' => self::get('link_color', '#007bff'),
        ];
    }

    /**
     * Récupère les paramètres académiques
     *
     * @return array
     */
    public static function getAcademicSettings()
    {
        return [
            'current_year' => self::get('current_academic_year', ''),
            'semester_system' => self::get('semester_system', 'trimester'),
            'grade_scale' => (int) self::get('grade_scale', '20'),
            'passing_grade' => (float) self::get('passing_grade', '10'),
            'attendance_required' => (int) self::get('attendance_required', '75'),
            'late_tolerance' => (int) self::get('late_arrival_tolerance', '15'),
        ];
    }

    /**
     * Récupère les paramètres de notifications
     *
     * @return array
     */
    public static function getNotificationSettings()
    {
        return [
            'email' => self::get('email_notifications', '1') === '1',
            'sms' => self::get('sms_notifications', '0') === '1',
            'parents' => self::get('parent_notifications', '1') === '1',
            'teachers' => self::get('teacher_notifications', '1') === '1',
            'admins' => self::get('admin_notifications', '1') === '1',
        ];
    }

    /**
     * Récupère les paramètres Analytics (poids, seuils, notifications).
     *
     * @return array
     */
    public static function getAnalyticsSettings()
    {
        return [
            'default_risk' => [
                'weight_solde'      => (float) self::get('analytics.default_risk.weight.solde', 3.0),
                'weight_retard'     => (float) self::get('analytics.default_risk.weight.retard', 2.5),
                'weight_engagement' => (float) self::get('analytics.default_risk.weight.engagement', 1.0),
                'weight_montant'    => (float) self::get('analytics.default_risk.weight.montant', 0.5),
                'bias'              => (float) self::get('analytics.default_risk.bias', -2.5),
                'threshold_high'    => (float) self::get('analytics.default_risk.threshold_high', 0.66),
                'threshold_medium'  => (float) self::get('analytics.default_risk.threshold_medium', 0.33),
                'top_n'             => (int)   self::get('analytics.default_risk.top_n', 50),
            ],
            'anomaly' => [
                'z_warning'                       => (float) self::get('analytics.anomaly.z_warning', 2.0),
                'z_critical'                      => (float) self::get('analytics.anomaly.z_critical', 3.0),
                'payment_outlier_multiplier'      => (float) self::get('analytics.anomaly.payment_outlier_multiplier', 3.0),
                'recouvrement_gap_warning_pct'    => (float) self::get('analytics.anomaly.recouvrement_gap_warning_pct', 30.0),
                'recouvrement_gap_critical_pct'   => (float) self::get('analytics.anomaly.recouvrement_gap_critical_pct', 50.0),
                'recouvrement_gap_min_expected'   => (float) self::get('analytics.anomaly.recouvrement_gap_min_expected', 100000.0),
                'notifications_enabled'           => (string) self::get('analytics.anomaly.notifications_enabled', '1') === '1',
            ],
            'recouvrement' => [
                'whatsapp_template' => (string) self::get(
                    'analytics.recouvrement.whatsapp_template',
                    "Bonjour {prenom}, votre solde de scolarité de {solde} FCFA est en retard de {retard} jours. Merci de régulariser dès que possible. — {ecole}",
                ),
            ],
        ];
    }

    /**
     * Initialise les paramètres par défaut
     *
     * @return void
     */
    public static function initializeDefaults()
    {
        $defaults = [
            // Établissement
            // ⚠ NE PAS pré-remplir school_name/acronym depuis APP_NAME : les .env de
            // tenants clonés depuis yakro héritent de "ESBTP-yAKRO" et le setting
            // se retrouve en DB de tous les nouveaux tenants. Laisser vide → le
            // fallback runtime ('KLASSCI') s'applique tant que l'admin n'a pas
            // configuré son école dans /esbtp/settings.
            'school_name' => ['value' => 'KLASSCI', 'group' => 'establishment'],
            'school_acronym' => ['value' => 'KLASSCI', 'group' => 'establishment'],
            'school_country' => ['value' => 'Côte d\'Ivoire', 'group' => 'establishment'],
            'director_title' => ['value' => 'Directeur Général', 'group' => 'establishment'],

            // PDF
            'pdf_show_logo' => ['value' => '1', 'group' => 'pdf'],
            'pdf_logo_position' => ['value' => 'left', 'group' => 'pdf'],
            'pdf_font_size' => ['value' => '12', 'group' => 'pdf'],
            'pdf_margin_top' => ['value' => '20', 'group' => 'pdf'],
            'pdf_margin_bottom' => ['value' => '20', 'group' => 'pdf'],
            'pdf_margin_left' => ['value' => '15', 'group' => 'pdf'],
            'pdf_margin_right' => ['value' => '15', 'group' => 'pdf'],

            // Bulletin
            'bulletin_style' => ['value' => 'yakro', 'group' => 'bulletin'],
            'bulletin_font_size' => ['value' => '13', 'group' => 'bulletin'],
            'bulletin_bts1_s1_council_title' => ['value' => 'Décision du conseil de classe', 'group' => 'bulletin'],
            'bulletin_semester1_weight' => ['value' => '1', 'group' => 'bulletin'],
            'bulletin_semester2_weight' => ['value' => '1', 'group' => 'bulletin'],

            // Interface
            'theme_primary_color' => ['value' => '#007bff', 'group' => 'interface'],
            'theme_secondary_color' => ['value' => '#6c757d', 'group' => 'interface'],
            'theme_success_color' => ['value' => '#28a745', 'group' => 'interface'],
            'theme_danger_color' => ['value' => '#dc3545', 'group' => 'interface'],
            'theme_warning_color' => ['value' => '#ffc107', 'group' => 'interface'],
            'theme_info_color' => ['value' => '#17a2b8', 'group' => 'interface'],
            'sidebar_color' => ['value' => '#343a40', 'group' => 'interface'],
            'navbar_color' => ['value' => '#ffffff', 'group' => 'interface'],
            'background_color' => ['value' => '#f8f9fa', 'group' => 'interface'],
            'text_color' => ['value' => '#212529', 'group' => 'interface'],
            'link_color' => ['value' => '#007bff', 'group' => 'interface'],

            // Académique
            'semester_system' => ['value' => 'trimester', 'group' => 'academic'],
            'grade_scale' => ['value' => '20', 'group' => 'academic'],
            'passing_grade' => ['value' => '10', 'group' => 'academic'],
            'attendance_required' => ['value' => '75', 'group' => 'academic'],
            'late_arrival_tolerance' => ['value' => '15', 'group' => 'academic'],

            // Notifications
            'email_notifications' => ['value' => '1', 'group' => 'notifications'],
            'sms_notifications' => ['value' => '0', 'group' => 'notifications'],
            'parent_notifications' => ['value' => '1', 'group' => 'notifications'],
            'teacher_notifications' => ['value' => '1', 'group' => 'notifications'],
            'admin_notifications' => ['value' => '1', 'group' => 'notifications'],
        ];

        foreach ($defaults as $key => $config) {
            // Ne créer que si le paramètre n'existe pas déjà
            if (!Setting::where('key', $key)->exists()) {
                Setting::set($key, $config['value'], $config['group']);
            }
        }
    }
}
