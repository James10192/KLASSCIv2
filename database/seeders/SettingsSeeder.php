<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $settings = [
            // Établissement
            [
                'key' => 'school_name',
                'value' => 'ESBTP-yAKRO',
                'type' => 'string',
                'group' => 'establishment',
                'category' => 'establishment',
                'description' => 'Nom de l\'établissement',
                'is_required' => true,
                'default_value' => 'ESBTP-yAKRO',
                'validation_rules' => ['required', 'string', 'max:255'],
                'sort_order' => 1
            ],
            [
                'key' => 'school_acronym',
                'value' => 'ESBTP',
                'type' => 'string',
                'group' => 'establishment',
                'category' => 'establishment',
                'description' => 'Acronyme de l\'établissement',
                'is_required' => false,
                'default_value' => 'ESBTP',
                'validation_rules' => ['nullable', 'string', 'max:10'],
                'sort_order' => 2
            ],
            [
                'key' => 'school_address',
                'value' => '',
                'type' => 'string',
                'group' => 'establishment',
                'category' => 'establishment',
                'description' => 'Adresse de l\'établissement',
                'is_required' => false,
                'default_value' => '',
                'validation_rules' => ['nullable', 'string', 'max:500'],
                'sort_order' => 3
            ],
            [
                'key' => 'school_city',
                'value' => 'Yamoussoukro',
                'type' => 'string',
                'group' => 'establishment',
                'category' => 'establishment',
                'description' => 'Ville de l\'établissement',
                'is_required' => false,
                'default_value' => 'Yamoussoukro',
                'validation_rules' => ['nullable', 'string', 'max:100'],
                'sort_order' => 4
            ],
            [
                'key' => 'school_country',
                'value' => 'Côte d\'Ivoire',
                'type' => 'string',
                'group' => 'establishment',
                'category' => 'establishment',
                'description' => 'Pays de l\'établissement',
                'is_required' => false,
                'default_value' => 'Côte d\'Ivoire',
                'validation_rules' => ['nullable', 'string', 'max:100'],
                'sort_order' => 5
            ],
            [
                'key' => 'school_phone',
                'value' => '',
                'type' => 'string',
                'group' => 'establishment',
                'category' => 'establishment',
                'description' => 'Téléphone de l\'établissement',
                'is_required' => false,
                'default_value' => '',
                'validation_rules' => ['nullable', 'string', 'max:20'],
                'sort_order' => 6
            ],
            [
                'key' => 'school_email',
                'value' => '',
                'type' => 'string',
                'group' => 'establishment',
                'category' => 'establishment',
                'description' => 'Email de l\'établissement',
                'is_required' => false,
                'default_value' => '',
                'validation_rules' => ['nullable', 'email', 'max:255'],
                'sort_order' => 7
            ],
            [
                'key' => 'director_name',
                'value' => '',
                'type' => 'string',
                'group' => 'establishment',
                'category' => 'establishment',
                'description' => 'Nom du directeur',
                'is_required' => false,
                'default_value' => '',
                'validation_rules' => ['nullable', 'string', 'max:255'],
                'sort_order' => 8
            ],
            [
                'key' => 'director_title',
                'value' => 'Directeur Général',
                'type' => 'string',
                'group' => 'establishment',
                'category' => 'establishment',
                'description' => 'Titre du directeur',
                'is_required' => false,
                'default_value' => 'Directeur Général',
                'validation_rules' => ['nullable', 'string', 'max:100'],
                'sort_order' => 9
            ],
            [
                'key' => 'school_logo',
                'value' => '',
                'type' => 'file',
                'group' => 'establishment',
                'category' => 'establishment',
                'description' => 'Logo de l\'établissement',
                'is_required' => false,
                'default_value' => '',
                'validation_rules' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
                'sort_order' => 10
            ],

            // Paramètres PDF
            [
                'key' => 'pdf_show_logo',
                'value' => true,
                'type' => 'boolean',
                'group' => 'pdf',
                'category' => 'pdf',
                'description' => 'Afficher le logo sur les PDF',
                'is_required' => false,
                'default_value' => true,
                'validation_rules' => ['boolean'],
                'sort_order' => 1
            ],
            [
                'key' => 'pdf_logo_position',
                'value' => 'left',
                'type' => 'string',
                'group' => 'pdf',
                'category' => 'pdf',
                'description' => 'Position du logo sur les PDF',
                'is_required' => false,
                'default_value' => 'left',
                'validation_rules' => ['in:left,center,right'],
                'sort_order' => 2
            ],
            [
                'key' => 'pdf_font_size',
                'value' => 12,
                'type' => 'integer',
                'group' => 'pdf',
                'category' => 'pdf',
                'description' => 'Taille de police pour les PDF',
                'is_required' => false,
                'default_value' => 12,
                'validation_rules' => ['integer', 'min:8', 'max:20'],
                'sort_order' => 3
            ],
            [
                'key' => 'pdf_margin_top',
                'value' => 20,
                'type' => 'integer',
                'group' => 'pdf',
                'category' => 'pdf',
                'description' => 'Marge supérieure des PDF (mm)',
                'is_required' => false,
                'default_value' => 20,
                'validation_rules' => ['integer', 'min:10', 'max:50'],
                'sort_order' => 4
            ],
            [
                'key' => 'pdf_margin_bottom',
                'value' => 20,
                'type' => 'integer',
                'group' => 'pdf',
                'category' => 'pdf',
                'description' => 'Marge inférieure des PDF (mm)',
                'is_required' => false,
                'default_value' => 20,
                'validation_rules' => ['integer', 'min:10', 'max:50'],
                'sort_order' => 5
            ],

            // Paramètres PDF supplémentaires
            [
                'key' => 'pdf_margin_left',
                'value' => 15,
                'type' => 'integer',
                'group' => 'pdf',
                'category' => 'pdf',
                'description' => 'Marge gauche des PDF (mm)',
                'is_required' => false,
                'default_value' => 15,
                'validation_rules' => ['integer', 'min:10', 'max:50'],
                'sort_order' => 6
            ],
            [
                'key' => 'pdf_margin_right',
                'value' => 15,
                'type' => 'integer',
                'group' => 'pdf',
                'category' => 'pdf',
                'description' => 'Marge droite des PDF (mm)',
                'is_required' => false,
                'default_value' => 15,
                'validation_rules' => ['integer', 'min:10', 'max:50'],
                'sort_order' => 7
            ],
            [
                'key' => 'pdf_primary_color',
                'value' => '#0453cb',
                'type' => 'string',
                'group' => 'pdf',
                'category' => 'pdf',
                'description' => 'Couleur principale des PDF',
                'is_required' => false,
                'default_value' => '#0453cb',
                'validation_rules' => ['nullable', 'string', 'max:20'],
                'sort_order' => 8
            ],
            [
                'key' => 'pdf_secondary_color',
                'value' => '#64748b',
                'type' => 'string',
                'group' => 'pdf',
                'category' => 'pdf',
                'description' => 'Couleur secondaire des PDF',
                'is_required' => false,
                'default_value' => '#64748b',
                'validation_rules' => ['nullable', 'string', 'max:20'],
                'sort_order' => 9
            ],
            [
                'key' => 'pdf_accent_color',
                'value' => '#f59e0b',
                'type' => 'string',
                'group' => 'pdf',
                'category' => 'pdf',
                'description' => 'Couleur d\'accent des PDF',
                'is_required' => false,
                'default_value' => '#f59e0b',
                'validation_rules' => ['nullable', 'string', 'max:20'],
                'sort_order' => 10
            ],
            [
                'key' => 'pdf_text_color',
                'value' => '#1f2937',
                'type' => 'string',
                'group' => 'pdf',
                'category' => 'pdf',
                'description' => 'Couleur du texte des PDF',
                'is_required' => false,
                'default_value' => '#1f2937',
                'validation_rules' => ['nullable', 'string', 'max:20'],
                'sort_order' => 11
            ],
            [
                'key' => 'pdf_header_bg_color',
                'value' => '#0453cb',
                'type' => 'string',
                'group' => 'pdf',
                'category' => 'pdf',
                'description' => 'Couleur de fond des en-têtes PDF',
                'is_required' => false,
                'default_value' => '#0453cb',
                'validation_rules' => ['nullable', 'string', 'max:20'],
                'sort_order' => 12
            ],
            [
                'key' => 'pdf_header_text_color',
                'value' => '#ffffff',
                'type' => 'string',
                'group' => 'pdf',
                'category' => 'pdf',
                'description' => 'Couleur du texte des en-têtes PDF',
                'is_required' => false,
                'default_value' => '#ffffff',
                'validation_rules' => ['nullable', 'string', 'max:20'],
                'sort_order' => 13
            ],
            [
                'key' => 'pdf_header_height',
                'value' => 30,
                'type' => 'integer',
                'group' => 'pdf',
                'category' => 'pdf',
                'description' => 'Hauteur de l\'en-tête PDF (mm)',
                'is_required' => false,
                'default_value' => 30,
                'validation_rules' => ['integer', 'min:10', 'max:100'],
                'sort_order' => 8
            ],
            [
                'key' => 'pdf_footer_height',
                'value' => 20,
                'type' => 'integer',
                'group' => 'pdf',
                'category' => 'pdf',
                'description' => 'Hauteur du pied de page PDF (mm)',
                'is_required' => false,
                'default_value' => 20,
                'validation_rules' => ['integer', 'min:10', 'max:50'],
                'sort_order' => 9
            ],

            // Paramètres académiques
            [
                'key' => 'current_academic_year',
                'value' => '',
                'type' => 'string',
                'group' => 'academic',
                'category' => 'academic',
                'description' => 'Année universitaire actuelle',
                'is_required' => true,
                'default_value' => '',
                'validation_rules' => ['required', 'string'],
                'sort_order' => 1
            ],
            [
                'key' => 'semester_system',
                'value' => 'trimester',
                'type' => 'string',
                'group' => 'academic',
                'category' => 'academic',
                'description' => 'Système de semestres',
                'is_required' => false,
                'default_value' => 'trimester',
                'validation_rules' => ['in:semester,trimester,quarter'],
                'sort_order' => 2
            ],
            [
                'key' => 'grade_scale',
                'value' => 20,
                'type' => 'integer',
                'group' => 'academic',
                'category' => 'academic',
                'description' => 'Échelle de notation',
                'is_required' => false,
                'default_value' => 20,
                'validation_rules' => ['in:20,100'],
                'sort_order' => 3
            ],
            [
                'key' => 'passing_grade',
                'value' => 10,
                'type' => 'integer',
                'group' => 'academic',
                'category' => 'academic',
                'description' => 'Note de passage',
                'is_required' => false,
                'default_value' => 10,
                'validation_rules' => ['numeric', 'min:0'],
                'sort_order' => 4
            ],
            [
                'key' => 'attendance_required',
                'value' => 75,
                'type' => 'integer',
                'group' => 'academic',
                'category' => 'academic',
                'description' => 'Pourcentage d\'assiduité requis',
                'is_required' => false,
                'default_value' => 75,
                'validation_rules' => ['integer', 'min:0', 'max:100'],
                'sort_order' => 5
            ],

            // Paramètres d'interface
            [
                'key' => 'theme_primary_color',
                'value' => '#007bff',
                'type' => 'string',
                'group' => 'interface',
                'category' => 'interface',
                'description' => 'Couleur primaire du thème',
                'is_required' => false,
                'default_value' => '#007bff',
                'validation_rules' => ['regex:/^#[0-9A-Fa-f]{6}$/'],
                'sort_order' => 1
            ],
            [
                'key' => 'theme_secondary_color',
                'value' => '#6c757d',
                'type' => 'string',
                'group' => 'interface',
                'category' => 'interface',
                'description' => 'Couleur secondaire du thème',
                'is_required' => false,
                'default_value' => '#6c757d',
                'validation_rules' => ['regex:/^#[0-9A-Fa-f]{6}$/'],
                'sort_order' => 2
            ],

            // Paramètres d'interface supplémentaires
            [
                'key' => 'theme_success_color',
                'value' => '#28a745',
                'type' => 'string',
                'group' => 'interface',
                'category' => 'interface',
                'description' => 'Couleur de succès du thème',
                'is_required' => false,
                'default_value' => '#28a745',
                'validation_rules' => ['regex:/^#[0-9A-Fa-f]{6}$/'],
                'sort_order' => 3
            ],
            [
                'key' => 'theme_danger_color',
                'value' => '#dc3545',
                'type' => 'string',
                'group' => 'interface',
                'category' => 'interface',
                'description' => 'Couleur de danger du thème',
                'is_required' => false,
                'default_value' => '#dc3545',
                'validation_rules' => ['regex:/^#[0-9A-Fa-f]{6}$/'],
                'sort_order' => 4
            ],
            [
                'key' => 'theme_warning_color',
                'value' => '#ffc107',
                'type' => 'string',
                'group' => 'interface',
                'category' => 'interface',
                'description' => 'Couleur d\'avertissement du thème',
                'is_required' => false,
                'default_value' => '#ffc107',
                'validation_rules' => ['regex:/^#[0-9A-Fa-f]{6}$/'],
                'sort_order' => 5
            ],
            [
                'key' => 'theme_info_color',
                'value' => '#17a2b8',
                'type' => 'string',
                'group' => 'interface',
                'category' => 'interface',
                'description' => 'Couleur d\'information du thème',
                'is_required' => false,
                'default_value' => '#17a2b8',
                'validation_rules' => ['regex:/^#[0-9A-Fa-f]{6}$/'],
                'sort_order' => 6
            ],

            // Paramètres de notifications
            [
                'key' => 'email_notifications',
                'value' => true,
                'type' => 'boolean',
                'group' => 'notifications',
                'category' => 'notifications',
                'description' => 'Activer les notifications par email',
                'is_required' => false,
                'default_value' => true,
                'validation_rules' => ['boolean'],
                'sort_order' => 1
            ],
            [
                'key' => 'parent_notifications',
                'value' => true,
                'type' => 'boolean',
                'group' => 'notifications',
                'category' => 'notifications',
                'description' => 'Activer les notifications aux parents',
                'is_required' => false,
                'default_value' => true,
                'validation_rules' => ['boolean'],
                'sort_order' => 2
            ],

            // Paramètres de notifications supplémentaires
            [
                'key' => 'sms_notifications',
                'value' => false,
                'type' => 'boolean',
                'group' => 'notifications',
                'category' => 'notifications',
                'description' => 'Activer les notifications SMS',
                'is_required' => false,
                'default_value' => false,
                'validation_rules' => ['boolean'],
                'sort_order' => 3
            ],
            [
                'key' => 'teacher_notifications',
                'value' => true,
                'type' => 'boolean',
                'group' => 'notifications',
                'category' => 'notifications',
                'description' => 'Activer les notifications aux enseignants',
                'is_required' => false,
                'default_value' => true,
                'validation_rules' => ['boolean'],
                'sort_order' => 4
            ],
            [
                'key' => 'admin_notifications',
                'value' => true,
                'type' => 'boolean',
                'group' => 'notifications',
                'category' => 'notifications',
                'description' => 'Activer les notifications aux administrateurs',
                'is_required' => false,
                'default_value' => true,
                'validation_rules' => ['boolean'],
                'sort_order' => 5
            ],

            // Paramètres d'assiduité
            [
                'key' => 'attendance_tracking_enabled',
                'value' => true,
                'type' => 'boolean',
                'group' => 'attendance',
                'category' => 'attendance',
                'description' => 'Activer le suivi d\'assiduité',
                'is_required' => false,
                'default_value' => true,
                'validation_rules' => ['boolean'],
                'sort_order' => 1
            ],
            [
                'key' => 'late_arrival_tolerance',
                'value' => 15,
                'type' => 'integer',
                'group' => 'attendance',
                'category' => 'attendance',
                'description' => 'Tolérance de retard (minutes)',
                'is_required' => false,
                'default_value' => 15,
                'validation_rules' => ['integer', 'min:0', 'max:60'],
                'sort_order' => 2
            ],
            [
                'key' => 'absence_justification_required',
                'value' => true,
                'type' => 'boolean',
                'group' => 'attendance',
                'category' => 'attendance',
                'description' => 'Justification d\'absence requise',
                'is_required' => false,
                'default_value' => true,
                'validation_rules' => ['boolean'],
                'sort_order' => 3
            ],

            // Paramètres de bulletin (nouveaux paramètres basés sur les fichiers de référence)
            [
                'key' => 'bulletin_show_header',
                'value' => '1',
                'type' => 'string',
                'group' => 'bulletin',
                'category' => 'bulletin',
                'description' => 'Afficher l\'en-tête du bulletin',
                'is_required' => false,
                'default_value' => '1',
                'validation_rules' => ['string'],
                'sort_order' => 1
            ],
            [
                'key' => 'bulletin_show_logo',
                'value' => '1',
                'type' => 'string',
                'group' => 'bulletin',
                'category' => 'bulletin',
                'description' => 'Afficher le logo de l\'école',
                'is_required' => false,
                'default_value' => '1',
                'validation_rules' => ['string'],
                'sort_order' => 2
            ],
            [
                'key' => 'bulletin_show_school_info',
                'value' => '1',
                'type' => 'string',
                'group' => 'bulletin',
                'category' => 'bulletin',
                'description' => 'Afficher les informations de l\'école',
                'is_required' => false,
                'default_value' => '1',
                'validation_rules' => ['string'],
                'sort_order' => 3
            ],
            [
                'key' => 'bulletin_show_republic_info',
                'value' => '1',
                'type' => 'string',
                'group' => 'bulletin',
                'category' => 'bulletin',
                'description' => 'Afficher les informations de la République',
                'is_required' => false,
                'default_value' => '1',
                'validation_rules' => ['string'],
                'sort_order' => 4
            ],
            [
                'key' => 'bulletin_show_ministry_info',
                'value' => '1',
                'type' => 'string',
                'group' => 'bulletin',
                'category' => 'bulletin',
                'description' => 'Afficher les informations du ministère',
                'is_required' => false,
                'default_value' => '1',
                'validation_rules' => ['string'],
                'sort_order' => 5
            ],
            [
                'key' => 'bulletin_show_edition_date',
                'value' => '1',
                'type' => 'string',
                'group' => 'bulletin',
                'category' => 'bulletin',
                'description' => 'Afficher la date d\'édition',
                'is_required' => false,
                'default_value' => '1',
                'validation_rules' => ['string'],
                'sort_order' => 6
            ],
            [
                'key' => 'bulletin_show_cycle_info',
                'value' => '1',
                'type' => 'string',
                'group' => 'bulletin',
                'category' => 'bulletin',
                'description' => 'Afficher les informations du cycle (BTS)',
                'is_required' => false,
                'default_value' => '1',
                'validation_rules' => ['string'],
                'sort_order' => 7
            ],

            // Informations étudiant
            [
                'key' => 'bulletin_show_student_info',
                'value' => '1',
                'type' => 'string',
                'group' => 'bulletin',
                'category' => 'bulletin',
                'description' => 'Afficher les informations de l\'étudiant',
                'is_required' => false,
                'default_value' => '1',
                'validation_rules' => ['string'],
                'sort_order' => 8
            ],
            [
                'key' => 'bulletin_show_matricule',
                'value' => '1',
                'type' => 'string',
                'group' => 'bulletin',
                'category' => 'bulletin',
                'description' => 'Afficher le matricule',
                'is_required' => false,
                'default_value' => '1',
                'validation_rules' => ['string'],
                'sort_order' => 9
            ],
            [
                'key' => 'bulletin_show_birth_date',
                'value' => '1',
                'type' => 'string',
                'group' => 'bulletin',
                'category' => 'bulletin',
                'description' => 'Afficher la date de naissance',
                'is_required' => false,
                'default_value' => '1',
                'validation_rules' => ['string'],
                'sort_order' => 10
            ],
            [
                'key' => 'bulletin_show_redoublant',
                'value' => '1',
                'type' => 'string',
                'group' => 'bulletin',
                'category' => 'bulletin',
                'description' => 'Afficher le statut redoublant',
                'is_required' => false,
                'default_value' => '1',
                'validation_rules' => ['string'],
                'sort_order' => 11
            ],
            [
                'key' => 'bulletin_show_class_info',
                'value' => '1',
                'type' => 'string',
                'group' => 'bulletin',
                'category' => 'bulletin',
                'description' => 'Afficher les informations de classe',
                'is_required' => false,
                'default_value' => '1',
                'validation_rules' => ['string'],
                'sort_order' => 12
            ],
            [
                'key' => 'bulletin_show_effectif',
                'value' => '1',
                'type' => 'string',
                'group' => 'bulletin',
                'category' => 'bulletin',
                'description' => 'Afficher l\'effectif de la classe',
                'is_required' => false,
                'default_value' => '1',
                'validation_rules' => ['string'],
                'sort_order' => 13
            ],

            // Tableau des matières
            [
                'key' => 'bulletin_show_subjects_table',
                'value' => '1',
                'type' => 'string',
                'group' => 'bulletin',
                'category' => 'bulletin',
                'description' => 'Afficher le tableau des matières',
                'is_required' => false,
                'default_value' => '1',
                'validation_rules' => ['string'],
                'sort_order' => 14
            ],
            [
                'key' => 'bulletin_show_general_subjects',
                'value' => '1',
                'type' => 'string',
                'group' => 'bulletin',
                'category' => 'bulletin',
                'description' => 'Afficher les matières d\'enseignement général',
                'is_required' => false,
                'default_value' => '1',
                'validation_rules' => ['string'],
                'sort_order' => 15
            ],
            [
                'key' => 'bulletin_show_technical_subjects',
                'value' => '1',
                'type' => 'string',
                'group' => 'bulletin',
                'category' => 'bulletin',
                'description' => 'Afficher les matières d\'enseignement technique',
                'is_required' => false,
                'default_value' => '1',
                'validation_rules' => ['string'],
                'sort_order' => 16
            ],
            [
                'key' => 'bulletin_show_subject_average',
                'value' => '1',
                'type' => 'string',
                'group' => 'bulletin',
                'category' => 'bulletin',
                'description' => 'Afficher la moyenne par matière',
                'is_required' => false,
                'default_value' => '1',
                'validation_rules' => ['string'],
                'sort_order' => 17
            ],
            [
                'key' => 'bulletin_show_coefficient',
                'value' => '1',
                'type' => 'string',
                'group' => 'bulletin',
                'category' => 'bulletin',
                'description' => 'Afficher les coefficients',
                'is_required' => false,
                'default_value' => '1',
                'validation_rules' => ['string'],
                'sort_order' => 18
            ],
            [
                'key' => 'bulletin_show_weighted_average',
                'value' => '1',
                'type' => 'string',
                'group' => 'bulletin',
                'category' => 'bulletin',
                'description' => 'Afficher la moyenne pondérée',
                'is_required' => false,
                'default_value' => '1',
                'validation_rules' => ['string'],
                'sort_order' => 19
            ],
            [
                'key' => 'bulletin_show_rank_per_subject',
                'value' => '1',
                'type' => 'string',
                'group' => 'bulletin',
                'category' => 'bulletin',
                'description' => 'Afficher le rang par matière',
                'is_required' => false,
                'default_value' => '1',
                'validation_rules' => ['string'],
                'sort_order' => 20
            ],
            [
                'key' => 'bulletin_show_teachers',
                'value' => '1',
                'type' => 'string',
                'group' => 'bulletin',
                'category' => 'bulletin',
                'description' => 'Afficher les professeurs',
                'is_required' => false,
                'default_value' => '1',
                'validation_rules' => ['string'],
                'sort_order' => 21
            ],
            [
                'key' => 'bulletin_show_appreciations',
                'value' => '1',
                'type' => 'string',
                'group' => 'bulletin',
                'category' => 'bulletin',
                'description' => 'Afficher les appréciations',
                'is_required' => false,
                'default_value' => '1',
                'validation_rules' => ['string'],
                'sort_order' => 22
            ],
            [
                'key' => 'bulletin_show_section_averages',
                'value' => '1',
                'type' => 'string',
                'group' => 'bulletin',
                'category' => 'bulletin',
                'description' => 'Afficher les moyennes par section',
                'is_required' => false,
                'default_value' => '1',
                'validation_rules' => ['string'],
                'sort_order' => 23
            ],

            // Absences
            [
                'key' => 'bulletin_show_absences',
                'value' => '1',
                'type' => 'string',
                'group' => 'bulletin',
                'category' => 'bulletin',
                'description' => 'Afficher le tableau des absences',
                'is_required' => false,
                'default_value' => '1',
                'validation_rules' => ['string'],
                'sort_order' => 24
            ],
            [
                'key' => 'bulletin_show_justified_absences',
                'value' => '1',
                'type' => 'string',
                'group' => 'bulletin',
                'category' => 'bulletin',
                'description' => 'Afficher les absences justifiées',
                'is_required' => false,
                'default_value' => '1',
                'validation_rules' => ['string'],
                'sort_order' => 25
            ],
            [
                'key' => 'bulletin_show_unjustified_absences',
                'value' => '1',
                'type' => 'string',
                'group' => 'bulletin',
                'category' => 'bulletin',
                'description' => 'Afficher les absences non justifiées',
                'is_required' => false,
                'default_value' => '1',
                'validation_rules' => ['string'],
                'sort_order' => 26
            ],

            // Résultats
            [
                'key' => 'bulletin_show_results_section',
                'value' => '1',
                'type' => 'string',
                'group' => 'bulletin',
                'category' => 'bulletin',
                'description' => 'Afficher la section résultats',
                'is_required' => false,
                'default_value' => '1',
                'validation_rules' => ['string'],
                'sort_order' => 27
            ],
            [
                'key' => 'bulletin_show_raw_average',
                'value' => '1',
                'type' => 'string',
                'group' => 'bulletin',
                'category' => 'bulletin',
                'description' => 'Afficher la moyenne brute',
                'is_required' => false,
                'default_value' => '1',
                'validation_rules' => ['string'],
                'sort_order' => 28
            ],
            [
                'key' => 'bulletin_show_attendance_note',
                'value' => '1',
                'type' => 'string',
                'group' => 'bulletin',
                'category' => 'bulletin',
                'description' => 'Afficher la note d\'assiduité',
                'is_required' => false,
                'default_value' => '1',
                'validation_rules' => ['string'],
                'sort_order' => 29
            ],
            [
                'key' => 'bulletin_show_semester_average',
                'value' => '1',
                'type' => 'string',
                'group' => 'bulletin',
                'category' => 'bulletin',
                'description' => 'Afficher la moyenne semestrielle',
                'is_required' => false,
                'default_value' => '1',
                'validation_rules' => ['string'],
                'sort_order' => 30
            ],
            [
                'key' => 'bulletin_show_student_rank',
                'value' => '1',
                'type' => 'string',
                'group' => 'bulletin',
                'category' => 'bulletin',
                'description' => 'Afficher le rang de l\'étudiant',
                'is_required' => false,
                'default_value' => '1',
                'validation_rules' => ['string'],
                'sort_order' => 31
            ],

            // Mentions et récompenses
            [
                'key' => 'bulletin_show_mentions',
                'value' => '1',
                'type' => 'string',
                'group' => 'bulletin',
                'category' => 'bulletin',
                'description' => 'Afficher les mentions',
                'is_required' => false,
                'default_value' => '1',
                'validation_rules' => ['string'],
                'sort_order' => 32
            ],
            [
                'key' => 'bulletin_show_felicitation',
                'value' => '1',
                'type' => 'string',
                'group' => 'bulletin',
                'category' => 'bulletin',
                'description' => 'Afficher félicitation',
                'is_required' => false,
                'default_value' => '1',
                'validation_rules' => ['string'],
                'sort_order' => 33
            ],
            [
                'key' => 'bulletin_show_encouragement',
                'value' => '1',
                'type' => 'string',
                'group' => 'bulletin',
                'category' => 'bulletin',
                'description' => 'Afficher encouragement',
                'is_required' => false,
                'default_value' => '1',
                'validation_rules' => ['string'],
                'sort_order' => 34
            ],
            [
                'key' => 'bulletin_show_honor_roll',
                'value' => '1',
                'type' => 'string',
                'group' => 'bulletin',
                'category' => 'bulletin',
                'description' => 'Afficher tableau d\'honneur',
                'is_required' => false,
                'default_value' => '1',
                'validation_rules' => ['string'],
                'sort_order' => 35
            ],
            [
                'key' => 'bulletin_show_work_warning',
                'value' => '1',
                'type' => 'string',
                'group' => 'bulletin',
                'category' => 'bulletin',
                'description' => 'Afficher avertissement travail',
                'is_required' => false,
                'default_value' => '1',
                'validation_rules' => ['string'],
                'sort_order' => 36
            ],
            [
                'key' => 'bulletin_show_conduct_blame',
                'value' => '1',
                'type' => 'string',
                'group' => 'bulletin',
                'category' => 'bulletin',
                'description' => 'Afficher blâme conduite',
                'is_required' => false,
                'default_value' => '1',
                'validation_rules' => ['string'],
                'sort_order' => 37
            ],

            // Note de conduite
            [
                'key' => 'bulletin_conduite_enabled',
                'value' => '0',
                'type' => 'boolean',
                'group' => 'bulletin',
                'category' => 'bulletin',
                'description' => 'Activer la note de conduite sur le bulletin',
                'is_required' => false,
                'default_value' => '0',
                'validation_rules' => ['string'],
                'sort_order' => 38
            ],
            [
                'key' => 'conduite_note_defaut',
                'value' => '16',
                'type' => 'float',
                'group' => 'bulletin',
                'category' => 'bulletin',
                'description' => 'Note de conduite par défaut (/20)',
                'is_required' => false,
                'default_value' => '16',
                'validation_rules' => ['nullable', 'numeric', 'min:0', 'max:20'],
                'sort_order' => 39
            ],
            [
                'key' => 'conduite_heures_par_point',
                'value' => '4',
                'type' => 'float',
                'group' => 'bulletin',
                'category' => 'bulletin',
                'description' => 'Nombre d\'heures d\'absence pour retrancher 1 point de conduite',
                'is_required' => false,
                'default_value' => '4',
                'validation_rules' => ['nullable', 'numeric', 'min:1'],
                'sort_order' => 40
            ],
            [
                'key' => 'bulletin_show_absences_par_matiere',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'bulletin',
                'category' => 'bulletin',
                'description' => 'Afficher les absences par matière sur le bulletin',
                'is_required' => false,
                'default_value' => '1',
                'validation_rules' => ['string'],
                'sort_order' => 41
            ],

            // Statistiques
            [
                'key' => 'bulletin_show_statistics',
                'value' => '1',
                'type' => 'string',
                'group' => 'bulletin',
                'category' => 'bulletin',
                'description' => 'Afficher les statistiques',
                'is_required' => false,
                'default_value' => '1',
                'validation_rules' => ['string'],
                'sort_order' => 38
            ],
            [
                'key' => 'bulletin_show_highest_average',
                'value' => '1',
                'type' => 'string',
                'group' => 'bulletin',
                'category' => 'bulletin',
                'description' => 'Afficher la plus forte moyenne',
                'is_required' => false,
                'default_value' => '1',
                'validation_rules' => ['string'],
                'sort_order' => 39
            ],
            [
                'key' => 'bulletin_show_lowest_average',
                'value' => '1',
                'type' => 'string',
                'group' => 'bulletin',
                'category' => 'bulletin',
                'description' => 'Afficher la plus faible moyenne',
                'is_required' => false,
                'default_value' => '1',
                'validation_rules' => ['string'],
                'sort_order' => 40
            ],
            [
                'key' => 'bulletin_show_class_average',
                'value' => '1',
                'type' => 'string',
                'group' => 'bulletin',
                'category' => 'bulletin',
                'description' => 'Afficher la moyenne de classe',
                'is_required' => false,
                'default_value' => '1',
                'validation_rules' => ['string'],
                'sort_order' => 41
            ],

            // Décision et signatures
            [
                'key' => 'bulletin_show_council_decision',
                'value' => '1',
                'type' => 'string',
                'group' => 'bulletin',
                'category' => 'bulletin',
                'description' => 'Afficher la décision du conseil de classe',
                'is_required' => false,
                'default_value' => '1',
                'validation_rules' => ['string'],
                'sort_order' => 42
            ],
            [
                'key' => 'bulletin_show_signature',
                'value' => '1',
                'type' => 'string',
                'group' => 'bulletin',
                'category' => 'bulletin',
                'description' => 'Afficher la signature',
                'is_required' => false,
                'default_value' => '1',
                'validation_rules' => ['string'],
                'sort_order' => 43
            ],
            [
                'key' => 'bulletin_show_director_signature',
                'value' => '1',
                'type' => 'string',
                'group' => 'bulletin',
                'category' => 'bulletin',
                'description' => 'Afficher la signature de la directrice',
                'is_required' => false,
                'default_value' => '1',
                'validation_rules' => ['string'],
                'sort_order' => 44
            ],

            // Fonctionnalités avancées
            [
                'key' => 'bulletin_auto_calculate_rank',
                'value' => '1',
                'type' => 'string',
                'group' => 'bulletin',
                'category' => 'bulletin',
                'description' => 'Calculer automatiquement le rang',
                'is_required' => false,
                'default_value' => '1',
                'validation_rules' => ['string'],
                'sort_order' => 45
            ],
            [
                'key' => 'bulletin_auto_calculate_mention',
                'value' => '1',
                'type' => 'string',
                'group' => 'bulletin',
                'category' => 'bulletin',
                'description' => 'Calculer automatiquement la mention',
                'is_required' => false,
                'default_value' => '1',
                'validation_rules' => ['string'],
                'sort_order' => 46
            ],
            [
                'key' => 'bulletin_auto_calculate_attendance',
                'value' => '1',
                'type' => 'string',
                'group' => 'bulletin',
                'category' => 'bulletin',
                'description' => 'Calculer automatiquement l\'assiduité',
                'is_required' => false,
                'default_value' => '1',
                'validation_rules' => ['string'],
                'sort_order' => 47
            ],
            [
                'key' => 'bulletin_require_teacher_assignment',
                'value' => '1',
                'type' => 'string',
                'group' => 'bulletin',
                'category' => 'bulletin',
                'description' => 'Exiger l\'assignation des professeurs',
                'is_required' => false,
                'default_value' => '1',
                'validation_rules' => ['string'],
                'sort_order' => 48
            ],
            [
                'key' => 'bulletin_require_subject_config',
                'value' => '1',
                'type' => 'string',
                'group' => 'bulletin',
                'category' => 'bulletin',
                'description' => 'Exiger la configuration des matières',
                'is_required' => false,
                'default_value' => '1',
                'validation_rules' => ['string'],
                'sort_order' => 49
            ],
            [
                'key' => 'bulletin_validate_averages',
                'value' => '1',
                'type' => 'string',
                'group' => 'bulletin',
                'category' => 'bulletin',
                'description' => 'Valider les moyennes avant génération',
                'is_required' => false,
                'default_value' => '1',
                'validation_rules' => ['string'],
                'sort_order' => 50
            ],

            // Seuils de mentions
            [
                'key' => 'bulletin_felicitation_threshold',
                'value' => '16',
                'type' => 'string',
                'group' => 'bulletin',
                'category' => 'bulletin',
                'description' => 'Seuil pour félicitation',
                'is_required' => false,
                'default_value' => '16',
                'validation_rules' => ['string'],
                'sort_order' => 51
            ],
            [
                'key' => 'bulletin_encouragement_threshold',
                'value' => '14',
                'type' => 'string',
                'group' => 'bulletin',
                'category' => 'bulletin',
                'description' => 'Seuil pour encouragement',
                'is_required' => false,
                'default_value' => '14',
                'validation_rules' => ['string'],
                'sort_order' => 52
            ],
            [
                'key' => 'bulletin_honor_roll_threshold',
                'value' => '12',
                'type' => 'string',
                'group' => 'bulletin',
                'category' => 'bulletin',
                'description' => 'Seuil pour tableau d\'honneur',
                'is_required' => false,
                'default_value' => '12',
                'validation_rules' => ['string'],
                'sort_order' => 53
            ],
            [
                'key' => 'bulletin_work_warning_threshold',
                'value' => '8',
                'type' => 'string',
                'group' => 'bulletin',
                'category' => 'bulletin',
                'description' => 'Seuil pour avertissement travail',
                'is_required' => false,
                'default_value' => '8',
                'validation_rules' => ['string'],
                'sort_order' => 54
            ],

            // Personnalisation du texte
            [
                'key' => 'bulletin_school_name_custom',
                'value' => '',
                'type' => 'string',
                'group' => 'bulletin',
                'category' => 'bulletin',
                'description' => 'Nom personnalisé de l\'école (vide = utiliser config)',
                'is_required' => false,
                'default_value' => '',
                'validation_rules' => ['string'],
                'sort_order' => 55
            ],
            [
                'key' => 'bulletin_republic_text',
                'value' => 'République de Côte d\'Ivoire',
                'type' => 'string',
                'group' => 'bulletin',
                'category' => 'bulletin',
                'description' => 'Texte de la République',
                'is_required' => false,
                'default_value' => 'République de Côte d\'Ivoire',
                'validation_rules' => ['string'],
                'sort_order' => 56
            ],
            [
                'key' => 'bulletin_union_text',
                'value' => 'Union-Discipline-Travail',
                'type' => 'string',
                'group' => 'bulletin',
                'category' => 'bulletin',
                'description' => 'Devise nationale',
                'is_required' => false,
                'default_value' => 'Union-Discipline-Travail',
                'validation_rules' => ['string'],
                'sort_order' => 57
            ],
            [
                'key' => 'bulletin_ministry_text',
                'value' => 'Ministère de l\'Enseignement Supérieur et de la Recherche Scientifique',
                'type' => 'string',
                'group' => 'bulletin',
                'category' => 'bulletin',
                'description' => 'Nom du ministère',
                'is_required' => false,
                'default_value' => 'Ministère de l\'Enseignement Supérieur et de la Recherche Scientifique',
                'validation_rules' => ['string'],
                'sort_order' => 58
            ],
            [
                'key' => 'bulletin_cycle_text',
                'value' => 'Brevet de Technicien Supérieur',
                'type' => 'string',
                'group' => 'bulletin',
                'category' => 'bulletin',
                'description' => 'Nom du cycle',
                'is_required' => false,
                'default_value' => 'Brevet de Technicien Supérieur',
                'validation_rules' => ['string'],
                'sort_order' => 59
            ],
            [
                'key' => 'bulletin_cycle_abbreviation',
                'value' => 'BTS',
                'type' => 'string',
                'group' => 'bulletin',
                'category' => 'bulletin',
                'description' => 'Abréviation du cycle',
                'is_required' => false,
                'default_value' => 'BTS',
                'validation_rules' => ['string'],
                'sort_order' => 60
            ],

            // Options d'affichage
            [
                'key' => 'bulletin_show_print_button',
                'value' => '1',
                'type' => 'string',
                'group' => 'bulletin',
                'category' => 'bulletin',
                'description' => 'Afficher le bouton d\'impression',
                'is_required' => false,
                'default_value' => '1',
                'validation_rules' => ['string'],
                'sort_order' => 61
            ],
            [
                'key' => 'bulletin_paper_format',
                'value' => 'A4',
                'type' => 'string',
                'group' => 'bulletin',
                'category' => 'bulletin',
                'description' => 'Format du papier (A4, A3, Letter)',
                'is_required' => false,
                'default_value' => 'A4',
                'validation_rules' => ['string'],
                'sort_order' => 62
            ],
            [
                'key' => 'bulletin_orientation',
                'value' => 'portrait',
                'type' => 'string',
                'group' => 'bulletin',
                'category' => 'bulletin',
                'description' => 'Orientation (portrait, landscape)',
                'is_required' => false,
                'default_value' => 'portrait',
                'validation_rules' => ['string'],
                'sort_order' => 63
            ],
            [
                'key' => 'bulletin_font_size',
                'value' => '11',
                'type' => 'string',
                'group' => 'bulletin',
                'category' => 'bulletin',
                'description' => 'Taille de police (px)',
                'is_required' => false,
                'default_value' => '11',
                'validation_rules' => ['string'],
                'sort_order' => 64
            ],
            [
                'key' => 'bulletin_dpi',
                'value' => '150',
                'type' => 'string',
                'group' => 'bulletin',
                'category' => 'bulletin',
                'description' => 'Résolution DPI pour PDF',
                'is_required' => false,
                'default_value' => '150',
                'validation_rules' => ['string'],
                'sort_order' => 65
            ],

            // Paramètres généraux
            [
                'key' => 'sidebar_color',
                'value' => '#343a40',
                'type' => 'string',
                'group' => 'general',
                'category' => 'general',
                'description' => 'Couleur de la barre latérale',
                'is_required' => false,
                'default_value' => '#343a40',
                'validation_rules' => ['regex:/^#[0-9A-Fa-f]{6}$/'],
                'sort_order' => 1
            ],
            [
                'key' => 'navbar_color',
                'value' => '#ffffff',
                'type' => 'string',
                'group' => 'general',
                'category' => 'general',
                'description' => 'Couleur de la barre de navigation',
                'is_required' => false,
                'default_value' => '#ffffff',
                'validation_rules' => ['regex:/^#[0-9A-Fa-f]{6}$/'],
                'sort_order' => 2
            ],
            [
                'key' => 'app_timezone',
                'value' => 'Africa/Abidjan',
                'type' => 'string',
                'group' => 'general',
                'category' => 'general',
                'description' => 'Fuseau horaire de l\'application',
                'is_required' => false,
                'default_value' => 'Africa/Abidjan',
                'validation_rules' => ['string'],
                'sort_order' => 3
            ],
            [
                'key' => 'app_locale',
                'value' => 'fr',
                'type' => 'string',
                'group' => 'general',
                'category' => 'general',
                'description' => 'Langue de l\'application',
                'is_required' => false,
                'default_value' => 'fr',
                'validation_rules' => ['in:fr,en'],
                'sort_order' => 4
            ],
            [
                'key' => 'maintenance_mode',
                'value' => false,
                'type' => 'boolean',
                'group' => 'general',
                'category' => 'general',
                'description' => 'Mode maintenance activé',
                'is_required' => false,
                'default_value' => false,
                'validation_rules' => ['boolean'],
                'sort_order' => 5
            ],
            // ─── SAARI export comptable (Sage Ligne 100) ──────────────────
            [
                'key' => 'saari_code_journal',
                'value' => 'JV',
                'type' => 'string',
                'group' => 'comptabilite',
                'category' => 'comptabilite',
                'description' => 'Code journal SAARI pour exports compta (ex: JV, BK, CA)',
                'is_required' => false,
                'default_value' => 'JV',
                'validation_rules' => ['nullable', 'string', 'max:10'],
                'sort_order' => 1
            ],
            [
                'key' => 'saari_default_account',
                'value' => '',
                'type' => 'string',
                'group' => 'comptabilite',
                'category' => 'comptabilite',
                'description' => 'Numéro de compte SAARI par défaut',
                'is_required' => false,
                'default_value' => '',
                'validation_rules' => ['nullable', 'string', 'max:20'],
                'sort_order' => 2
            ],
            [
                'key' => 'saari_account_mapping',
                'value' => '{}',
                'type' => 'string',
                'group' => 'comptabilite',
                'category' => 'comptabilite',
                'description' => 'Mapping JSON catégorie → compte SAARI',
                'is_required' => false,
                'default_value' => '{}',
                'validation_rules' => ['nullable', 'string'],
                'sort_order' => 3
            ],
            // PR1 réconciliation
            [
                'key' => 'comptabilite.reconciliation.frequency',
                'value' => 'daily',
                'type' => 'string',
                'group' => 'comptabilite',
                'category' => 'comptabilite',
                'description' => 'Fréquence par défaut des sessions de réconciliation caisse (daily / weekly / monthly).',
                'is_required' => true,
                'default_value' => 'daily',
                'validation_rules' => ['required', 'in:daily,weekly,monthly'],
                'sort_order' => 10
            ],
            [
                'key' => 'comptabilite.reconciliation.require_separation_of_duties',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'comptabilite',
                'category' => 'comptabilite',
                'description' => 'Si activé, l\'approbateur d\'une session doit être différent de l\'ouvreur (séparation des devoirs OHADA). Recommandé en prod.',
                'is_required' => true,
                'default_value' => '1',
                'validation_rules' => ['required', 'boolean'],
                'sort_order' => 11
            ],
            // PR5 réconciliation : seuil tolérance + URLs portails
            [
                'key' => 'comptabilite.reconciliation.ecart_tolerance',
                'value' => '100',
                'type' => 'integer',
                'group' => 'comptabilite',
                'category' => 'comptabilite',
                'description' => 'Seuil de tolérance (FCFA) sous lequel un écart n\'est pas considéré comme discrepancy.',
                'is_required' => true,
                'default_value' => '100',
                'validation_rules' => ['required', 'integer', 'min:0'],
                'sort_order' => 12
            ],
            [
                'key' => 'comptabilite.reconciliation.portal_url_orange_money',
                'value' => '',
                'type' => 'string',
                'group' => 'comptabilite',
                'category' => 'comptabilite',
                'description' => 'URL portail merchant Orange Money (hint UI).',
                'is_required' => false,
                'default_value' => '',
                'validation_rules' => ['nullable', 'url', 'max:255'],
                'sort_order' => 13
            ],
            [
                'key' => 'comptabilite.reconciliation.portal_url_mtn_money',
                'value' => '',
                'type' => 'string',
                'group' => 'comptabilite',
                'category' => 'comptabilite',
                'description' => 'URL portail merchant MTN MoMo (hint UI).',
                'is_required' => false,
                'default_value' => '',
                'validation_rules' => ['nullable', 'url', 'max:255'],
                'sort_order' => 14
            ],
            [
                'key' => 'comptabilite.reconciliation.portal_url_moov_money',
                'value' => '',
                'type' => 'string',
                'group' => 'comptabilite',
                'category' => 'comptabilite',
                'description' => 'URL portail merchant Moov Money (hint UI).',
                'is_required' => false,
                'default_value' => '',
                'validation_rules' => ['nullable', 'url', 'max:255'],
                'sort_order' => 15
            ],
            [
                'key' => 'comptabilite.reconciliation.portal_url_wave',
                'value' => '',
                'type' => 'string',
                'group' => 'comptabilite',
                'category' => 'comptabilite',
                'description' => 'URL portail Wave Business (hint UI).',
                'is_required' => false,
                'default_value' => '',
                'validation_rules' => ['nullable', 'url', 'max:255'],
                'sort_order' => 16
            ],
            // PR6 réconciliation : seuil overdue
            [
                'key' => 'comptabilite.reconciliation.overdue_days',
                'value' => '2',
                'type' => 'integer',
                'group' => 'comptabilite',
                'category' => 'comptabilite',
                'description' => 'Nombre de jours après ouverture d\'une session avant qu\'elle soit considérée overdue.',
                'is_required' => true,
                'default_value' => '2',
                'validation_rules' => ['required', 'integer', 'min:1', 'max:60'],
                'sort_order' => 17
            ]
        ];

        foreach ($settings as $settingData) {
            Setting::updateOrCreate(
                ['key' => $settingData['key']],
                array_merge($settingData, [
                    'is_active' => true,
                    'created_by' => 1,
                    'updated_by' => 1
                ])
            );
        }

        $this->command->info('Configurations de base créées avec succès.');
    }
}
