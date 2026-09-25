<?php

namespace App\Support\Recherche;

use App\Support\PorteDeRoute;
use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/**
 * Les écrans de l'application qu'on peut ouvrir depuis la palette Ctrl K / ⌘ K.
 *
 * La liste reprend le menu latéral (resources/views/layouts/app.blade.php et
 * layouts/partials/sidebar/), plus les réglages, la caisse et les espaces
 * personnels. Chaque entrée porte des mots-clés : on cherche « encaisser »,
 * « reçu » ou « caisse », pas le nom exact d'un écran.
 *
 * Une page n'est JAMAIS proposée si sa route la refuserait. La décision se lit
 * sur la route elle-même (PorteDeRoute), pas sur une copie de ses permissions :
 *
 *   - verdict `false`  → retirée, quoi que dise l'entrée ;
 *   - `can` déclaré    → exigé EN PLUS, comme le `@can` du menu (souvent la
 *                        permission de module, que la route ne porte pas) ;
 *   - verdict `true`   → proposée ;
 *   - verdict `null`   → la route n'a pas de garde lisible : proposée seulement
 *                        si l'entrée déclare son `can` (et qu'il est tenu) ou
 *                        si elle est ouverte à tout compte par construction
 *                        (`libre`, l'accueil par exemple).
 *
 * Aucun nom de rôle ici : `can` ne liste que des permissions.
 */
final class IndexDesPages
{
    /**
     * `can` : liste de permissions toutes exigées ; `a|b` = l'une des deux.
     * `rang` : ordre des suggestions affichées palette vide (plus petit = d'abord).
     *
     * @var list<array{route: string, titre: string, groupe: string, icone: string, mots?: list<string>, can?: list<string>, libre?: bool, rang?: int}>
     */
    private const PAGES = [
        // Accueil
        ['route' => 'dashboard', 'titre' => 'Accueil', 'groupe' => 'Accueil', 'icone' => 'fa-house', 'mots' => ['tableau de bord', 'dashboard', 'accueil'], 'libre' => true, 'rang' => 1],
        ['route' => 'esbtp.pilotage-academique.index', 'titre' => 'Pilotage académique', 'groupe' => 'Accueil', 'icone' => 'fa-compass', 'mots' => ['pilotage', 'indicateurs', 'direction'], 'can' => ['module.academic_pilotage.access', 'academic_pilotage.view']],

        // Scolarité
        ['route' => 'esbtp.etudiants.index', 'titre' => 'Étudiants', 'groupe' => 'Scolarité', 'icone' => 'fa-user-graduate', 'mots' => ['élèves', 'liste des étudiants', 'apprenants', 'fiche étudiant'], 'can' => ['module.etudiants.access', 'students.view'], 'rang' => 3],
        ['route' => 'esbtp.accessibility.index', 'titre' => 'Accessibilité et besoins particuliers', 'groupe' => 'Scolarité', 'icone' => 'fa-universal-access', 'mots' => ['handicap', 'aménagements', 'besoins spécifiques'], 'can' => ['module.etudiants.access', 'students.accessibility.view']],
        ['route' => 'esbtp.trash.index', 'titre' => 'Corbeille', 'groupe' => 'Scolarité', 'icone' => 'fa-trash-can', 'mots' => ['supprimés', 'restaurer', 'archives'], 'can' => ['trash.view']],
        ['route' => 'esbtp.candidatures.index', 'titre' => 'Candidatures', 'groupe' => 'Inscriptions', 'icone' => 'fa-envelope-open-text', 'mots' => ['admission', 'portail', 'dossiers de candidature'], 'can' => ['module.etudiants.access', 'inscriptions.candidatures.view']],
        ['route' => 'esbtp.reinscription-demandes.index', 'titre' => 'Demandes de réinscription', 'groupe' => 'Inscriptions', 'icone' => 'fa-inbox', 'mots' => ['réinscription en ligne', 'demandes'], 'can' => ['module.etudiants.access', 'reinscriptions.demandes.view']],
        ['route' => 'esbtp.rendez-vous.index', 'titre' => "Rendez-vous d'inscription", 'groupe' => 'Inscriptions', 'icone' => 'fa-calendar-check', 'mots' => ['rdv', 'créneaux', 'planning des rendez-vous'], 'can' => ['module.etudiants.access', 'inscriptions.rdv.view']],
        ['route' => 'esbtp.rendez-vous.accueil.index', 'titre' => 'Accueil du jour', 'groupe' => 'Inscriptions', 'icone' => 'fa-door-open', 'mots' => ['rendez-vous du jour', 'rdv', 'arrivées', 'guichet'], 'can' => ['module.etudiants.access', 'inscriptions.rdv.accueil']],
        ['route' => 'esbtp.inscriptions.create', 'titre' => 'Nouvelle inscription', 'groupe' => 'Inscriptions', 'icone' => 'fa-user-plus', 'mots' => ['inscrire', 'ajouter un étudiant', 'nouvel étudiant', 'créer inscription'], 'can' => ['module.etudiants.access', 'inscriptions.create'], 'rang' => 4],
        ['route' => 'esbtp.inscriptions.index', 'titre' => 'Inscriptions', 'groupe' => 'Inscriptions', 'icone' => 'fa-clipboard-list', 'mots' => ['liste des inscriptions', 'valider inscription', 'workflow'], 'can' => ['module.etudiants.access', 'inscriptions.view']],
        ['route' => 'esbtp.reinscription.index', 'titre' => 'Réinscriptions', 'groupe' => 'Inscriptions', 'icone' => 'fa-rotate', 'mots' => ['réinscrire', 'passage', 'redoublement', 'année suivante'], 'can' => ['module.etudiants.access']],
        ['route' => 'esbtp.inscriptions.sous-reserve', 'titre' => 'Inscriptions sous réserve', 'groupe' => 'Inscriptions', 'icone' => 'fa-hourglass-half', 'mots' => ['réserve', 'conditionnelle', 'en attente'], 'can' => ['module.etudiants.access']],
        ['route' => 'esbtp.inscriptions.pre-inscription', 'titre' => 'Pré-inscription', 'groupe' => 'Inscriptions', 'icone' => 'fa-id-card', 'mots' => ['préinscription', 'caisse', 'guichet'], 'can' => ['module.caisse.access', 'inscriptions.create']],
        ['route' => 'esbtp.pieces-dossier.index', 'titre' => 'Pièces du dossier', 'groupe' => 'Inscriptions', 'icone' => 'fa-folder-open', 'mots' => ['documents', 'pièces justificatives', 'extrait de naissance'], 'can' => ['module.etudiants.access', 'pieces_dossier.view']],
        ['route' => 'esbtp.pieces-dossier.suivi', 'titre' => 'Suivi des pièces manquantes', 'groupe' => 'Inscriptions', 'icone' => 'fa-list-check', 'mots' => ['dossiers incomplets', 'pièces manquantes'], 'can' => ['module.etudiants.access', 'pieces_dossier.view']],

        // Académique
        ['route' => 'esbtp.filieres.index', 'titre' => 'Filières', 'groupe' => 'Académique', 'icone' => 'fa-sitemap', 'mots' => ['spécialités', 'parcours'], 'can' => ['module.academique.access', 'filieres.view']],
        ['route' => 'esbtp.classes.index', 'titre' => 'Classes', 'groupe' => 'Académique', 'icone' => 'fa-chalkboard', 'mots' => ['groupes', 'effectifs', 'mes classes', 'liste d\'appel'], 'can' => ['classes.view']],
        ['route' => 'esbtp.classes.create', 'titre' => 'Nouvelle classe', 'groupe' => 'Académique', 'icone' => 'fa-plus', 'mots' => ['créer une classe', 'ajouter classe'], 'can' => ['module.academique.access', 'classes.create']],
        ['route' => 'esbtp.niveaux-etudes.index', 'titre' => "Niveaux d'études", 'groupe' => 'Académique', 'icone' => 'fa-layer-group', 'mots' => ['niveaux', 'licence', 'bts', 'année'], 'can' => ['module.academique.access', 'niveaux.view']],
        ['route' => 'esbtp.annees-universitaires.index', 'titre' => 'Années universitaires', 'groupe' => 'Académique', 'icone' => 'fa-calendar', 'mots' => ['année scolaire', 'année en cours', 'rentrée'], 'can' => ['module.academique.access', 'annees.view']],
        ['route' => 'esbtp.admin.orientation-targets.index', 'titre' => 'Orientation BTS tronc commun', 'groupe' => 'Académique', 'icone' => 'fa-diagram-project', 'mots' => ['tronc commun', 'orientation', 'spécialisation'], 'can' => ['module.academique.access', 'bts_tronc_commun.manage_targets']],
        ['route' => 'esbtp.cycles.index', 'titre' => 'Cycles de formation', 'groupe' => 'Académique', 'icone' => 'fa-arrows-spin', 'mots' => ['cycles', 'formation'], 'can' => ['module.academique.access']],
        ['route' => 'esbtp.specialties.index', 'titre' => 'Spécialités', 'groupe' => 'Académique', 'icone' => 'fa-shapes', 'mots' => ['options', 'spécialisations'], 'can' => ['module.academique.access']],
        ['route' => 'esbtp.continuing-education.index', 'titre' => 'Formation continue', 'groupe' => 'Académique', 'icone' => 'fa-briefcase', 'mots' => ['formation professionnelle', 'adultes'], 'can' => ['module.academique.access']],
        ['route' => 'esbtp.matieres.index', 'titre' => 'Matières', 'groupe' => 'Académique', 'icone' => 'fa-book', 'mots' => ['cours', 'coefficients', 'programme', 'maquette'], 'can' => ['module.emploi_temps.access', 'matieres.view']],

        ['route' => 'esbtp.filieres.create', 'titre' => 'Nouvelle filière', 'groupe' => 'Académique', 'icone' => 'fa-plus', 'mots' => ['créer une filière', 'ajouter filière'], 'can' => ['module.academique.access']],
        ['route' => 'esbtp.matieres.create', 'titre' => 'Nouvelle matière', 'groupe' => 'Académique', 'icone' => 'fa-plus', 'mots' => ['créer une matière', 'ajouter matière'], 'can' => ['module.emploi_temps.access']],

        // Emploi du temps
        ['route' => 'esbtp.emploi-temps.index', 'titre' => 'Emplois du temps', 'groupe' => 'Emploi du temps', 'icone' => 'fa-calendar-week', 'mots' => ['horaires', 'planning', 'edt', 'agenda'], 'can' => ['module.emploi_temps.access', 'timetables.view'], 'rang' => 6],
        ['route' => 'esbtp.seances-cours.index', 'titre' => 'Séances de cours', 'groupe' => 'Emploi du temps', 'icone' => 'fa-clock', 'mots' => ['séances', 'créneaux', 'cours programmés'], 'can' => ['module.emploi_temps.access']],
        ['route' => 'esbtp.planning-general.index', 'titre' => 'Planning général', 'groupe' => 'Emploi du temps', 'icone' => 'fa-calendar-days', 'mots' => ['volume horaire', 'cm td tp', 'heures'], 'can' => ['module.emploi_temps.access']],

        // Notes & examens
        ['route' => 'esbtp.notes.index', 'titre' => 'Notes', 'groupe' => 'Notes & examens', 'icone' => 'fa-pen-to-square', 'mots' => ['saisie des notes', 'moyennes', 'relevé'], 'can' => ['module.notes_evaluations.access', 'notes.view'], 'rang' => 7],
        ['route' => 'teacher.grades', 'titre' => 'Saisie des notes', 'groupe' => 'Notes & examens', 'icone' => 'fa-pen', 'mots' => ['noter', 'mes notes à saisir'], 'can' => ['notes.view']],
        ['route' => 'esbtp.resultats.index', 'titre' => 'Résultats', 'groupe' => 'Notes & examens', 'icone' => 'fa-square-poll-vertical', 'mots' => ['classement', 'rang', 'moyennes générales'], 'can' => ['module.notes_evaluations.access', 'notes.view']],
        ['route' => 'esbtp.bulletins.index', 'titre' => 'Bulletins', 'groupe' => 'Notes & examens', 'icone' => 'fa-file-lines', 'mots' => ['bulletin de notes', 'imprimer bulletin', 'pdf'], 'can' => ['module.notes_evaluations.access', 'bulletins.view']],
        ['route' => 'esbtp.evaluations.index', 'titre' => 'Évaluations', 'groupe' => 'Notes & examens', 'icone' => 'fa-file-signature', 'mots' => ['devoirs', 'examens', 'interrogations', 'contrôles'], 'can' => ['module.notes_evaluations.access', 'notes.view']],
        ['route' => 'esbtp.evaluations.create', 'titre' => 'Programmer une évaluation', 'groupe' => 'Notes & examens', 'icone' => 'fa-calendar-plus', 'mots' => ['nouvelle évaluation', 'devoir', 'examen', 'interrogation'], 'can' => ['module.notes_evaluations.access']],
        ['route' => 'esbtp.rapports.rentree', 'titre' => 'Rapport de rentrée', 'groupe' => 'Notes & examens', 'icone' => 'fa-file-contract', 'mots' => ['rapport', 'statistiques rentrée'], 'can' => ['reports.academic.rentree']],
        ['route' => 'esbtp.rapports.trimestre', 'titre' => 'Rapport de fin de semestre', 'groupe' => 'Notes & examens', 'icone' => 'fa-file-contract', 'mots' => ['rapport', 'semestre', 'trimestre'], 'can' => ['reports.academic.trimestre']],
        ['route' => 'esbtp.rapports.annuel', 'titre' => 'Rapport annuel', 'groupe' => 'Notes & examens', 'icone' => 'fa-file-contract', 'mots' => ['rapport', 'bilan annuel'], 'can' => ['reports.academic.annuel']],

        // LMD
        ['route' => 'esbtp.lmd.parcours-domain.index', 'titre' => 'Domaines, mentions et parcours', 'groupe' => 'LMD', 'icone' => 'fa-folder-tree', 'mots' => ['lmd', 'mention', 'domaine', 'parcours'], 'can' => ['module.lmd.access', 'lmd.structure.view']],
        ['route' => 'esbtp.lmd.ue.index', 'titre' => 'UE et ECUE', 'groupe' => 'LMD', 'icone' => 'fa-cubes', 'mots' => ['unités d\'enseignement', 'ecue', 'crédits', 'maquette lmd'], 'can' => ['module.lmd.access', 'lmd.structure.view']],
        ['route' => 'esbtp.lmd.notes.index', 'titre' => 'Notes LMD', 'groupe' => 'LMD', 'icone' => 'fa-pen-to-square', 'mots' => ['lmd', 'saisie notes ecue'], 'can' => ['module.lmd.access', 'lmd.notes.view']],
        ['route' => 'esbtp.lmd.resultats.index', 'titre' => 'Résultats LMD', 'groupe' => 'LMD', 'icone' => 'fa-square-poll-vertical', 'mots' => ['lmd', 'crédits validés', 'compensation'], 'can' => ['module.lmd.access', 'lmd.resultats.view']],
        ['route' => 'esbtp.lmd.bulletins.index', 'titre' => 'Bulletins LMD', 'groupe' => 'LMD', 'icone' => 'fa-file-lines', 'mots' => ['lmd', 'relevé de notes'], 'can' => ['module.lmd.access', 'lmd.bulletins.view']],
        ['route' => 'esbtp.lmd.planning.index', 'titre' => 'Planning LMD', 'groupe' => 'LMD', 'icone' => 'fa-calendar-days', 'mots' => ['lmd', 'volume horaire'], 'can' => ['module.lmd.access', 'lmd.planning.view']],
        ['route' => 'esbtp.examens.index', 'titre' => 'Examens', 'groupe' => 'LMD', 'icone' => 'fa-file-circle-check', 'mots' => ['session', 'examen terminal', 'calendrier des examens'], 'can' => ['module.lmd.access', 'lmd.examens.view']],
        ['route' => 'esbtp.lmd.rattrapage.index', 'titre' => 'Rattrapage', 'groupe' => 'LMD', 'icone' => 'fa-rotate-left', 'mots' => ['seconde session', 'rattrapage lmd'], 'can' => ['module.lmd.access', 'lmd.rattrapage.view']],
        ['route' => 'esbtp.lmd.jurys.index', 'titre' => 'Jurys de délibération', 'groupe' => 'LMD', 'icone' => 'fa-gavel', 'mots' => ['jury', 'délibération', 'procès-verbal', 'pv'], 'can' => ['module.lmd.access', 'lmd.jury.view']],
        ['route' => 'esbtp.lmd.ajournes.index', 'titre' => 'Ajournés', 'groupe' => 'LMD', 'icone' => 'fa-user-clock', 'mots' => ['ajourné', 'redoublants lmd'], 'can' => ['module.lmd.access', 'lmd.ajournes.view']],
        ['route' => 'esbtp.lmd.reconciliation.index', 'titre' => 'Réconciliation des UE', 'groupe' => 'LMD', 'icone' => 'fa-code-merge', 'mots' => ['doublons', 'fusion ue'], 'can' => ['module.lmd.access', 'lmd.reconciliation.manage']],
        ['route' => 'esbtp.tpe-journal.index', 'titre' => 'Mon journal TPE', 'groupe' => 'LMD', 'icone' => 'fa-book-open', 'mots' => ['tpe', 'travail personnel'], 'can' => ['module.tpe.access', 'tpe.declare']],
        ['route' => 'esbtp.tpe-validation.index', 'titre' => 'Valider les heures TPE', 'groupe' => 'LMD', 'icone' => 'fa-circle-check', 'mots' => ['tpe', 'validation heures'], 'can' => ['module.tpe.access', 'tpe.validate']],

        // Présences
        ['route' => 'esbtp.attendances.index', 'titre' => 'Présences', 'groupe' => 'Présences', 'icone' => 'fa-user-check', 'mots' => ['absences', 'appel', 'assiduité', 'émargement'], 'can' => ['module.presences.access', 'attendances.view']],
        ['route' => 'esbtp.attendances.rapport-form', 'titre' => 'Rapport de présences', 'groupe' => 'Présences', 'icone' => 'fa-chart-column', 'mots' => ['absences', 'statistiques de présence'], 'can' => ['module.presences.access', 'attendances.view']],
        ['route' => 'esbtp.teacher-attendance.history', 'titre' => 'Présences des enseignants', 'groupe' => 'Présences', 'icone' => 'fa-chalkboard-user', 'mots' => ['émargement enseignant', 'heures faites'], 'can' => ['module.presences.access', 'attendances.view']],
        ['route' => 'esbtp.attendance-codes.index', 'titre' => 'Codes de présence', 'groupe' => 'Présences', 'icone' => 'fa-qrcode', 'mots' => ['qr code', 'code de séance'], 'can' => ['module.presences.access', 'attendances.generate_codes']],
        ['route' => 'coordinateur.attendance-dashboard', 'titre' => 'Tableau des présences', 'groupe' => 'Présences', 'icone' => 'fa-chart-line', 'mots' => ['suivi assiduité', 'taux de présence'], 'can' => ['module.presences.access', 'attendances.view']],
        ['route' => 'esbtp.attendances.justifications.admin', 'titre' => "Justificatifs d'absence", 'groupe' => 'Présences', 'icone' => 'fa-file-medical', 'mots' => ['justification', 'certificat médical', 'excuses'], 'can' => ['module.presences.access', 'attendances.justify_process']],
        ['route' => 'esbtp.rapports-cours.index', 'titre' => 'Rapports de séance', 'groupe' => 'Présences', 'icone' => 'fa-clipboard', 'mots' => ['cahier de texte', 'compte rendu de cours'], 'can' => ['session_reports.view']],
        ['route' => 'esbtp.attendance.mark.index', 'titre' => 'Faire les émargements', 'groupe' => 'Présences', 'icone' => 'fa-signature', 'mots' => ['appel', 'marquer présence', 'émarger'], 'can' => ['attendances.view']],

        // Finances
        ['route' => 'esbtp.paiements.create', 'titre' => 'Encaisser', 'groupe' => 'Finances', 'icone' => 'fa-cash-register', 'mots' => ['encaissement', 'nouveau paiement', 'reçu', 'caisse', 'payer', 'versement'], 'rang' => 2],
        ['route' => 'esbtp.caisse.ma-caisse', 'titre' => 'Ma caisse', 'groupe' => 'Finances', 'icone' => 'fa-vault', 'mots' => ['session de caisse', 'ouvrir la caisse', 'clôturer la caisse', 'fond de caisse'], 'can' => ['cash_session.manage|module.caisse.access'], 'rang' => 5],
        ['route' => 'esbtp.paiements.index', 'titre' => 'Paiements', 'groupe' => 'Finances', 'icone' => 'fa-receipt', 'mots' => ['liste des paiements', 'reçus', 'encaissements', 'valider paiement', 'versements'], 'can' => ['paiements.view|paiements.view_own'], 'rang' => 5],
        ['route' => 'esbtp.paiements.suivi-categories', 'titre' => 'Suivi par catégorie de frais', 'groupe' => 'Finances', 'icone' => 'fa-chart-pie', 'mots' => ['impayés', 'frais', 'étudiants en règle'], 'can' => ['module.comptabilite.access', 'paiements.view']],
        ['route' => 'esbtp.paiements.export-detaille.index', 'titre' => 'Export détaillé des paiements', 'groupe' => 'Finances', 'icone' => 'fa-file-export', 'mots' => ['excel', 'export', 'extraction'], 'can' => ['module.comptabilite.access', 'paiements.export']],
        ['route' => 'esbtp.comptabilite.dashboard', 'titre' => 'Analyse financière', 'groupe' => 'Finances', 'icone' => 'fa-chart-line', 'mots' => ['comptabilité', 'tableau de bord financier', 'recettes'], 'can' => ['module.comptabilite.access', 'comptabilite.dashboard.view']],
        ['route' => 'esbtp.frais.index', 'titre' => 'Frais de scolarité', 'groupe' => 'Finances', 'icone' => 'fa-money-bill-wave', 'mots' => ['tarifs', 'scolarité', 'catégories de frais'], 'can' => ['module.comptabilite.access', 'comptabilite.access']],
        ['route' => 'esbtp.frais.configure', 'titre' => 'Configurer les frais', 'groupe' => 'Finances', 'icone' => 'fa-sliders', 'mots' => ['tarifs', 'montants', 'frais par classe'], 'can' => ['module.comptabilite.access', 'comptabilite.access']],
        ['route' => 'esbtp.comptabilite.echeanciers.index', 'titre' => 'Échéanciers de paiement', 'groupe' => 'Finances', 'icone' => 'fa-calendar-plus', 'mots' => ['tranches', 'échéances', 'versements'], 'can' => ['module.comptabilite.access', 'comptabilite.access']],
        ['route' => 'esbtp.comptabilite.relances.index', 'titre' => 'Relances', 'groupe' => 'Finances', 'icone' => 'fa-bell', 'mots' => ['relancer', 'impayés', 'rappel de paiement'], 'can' => ['module.comptabilite.access', 'comptabilite.relances.send']],
        ['route' => 'esbtp.comptabilite.recouvrement.index', 'titre' => 'Recouvrement', 'groupe' => 'Finances', 'icone' => 'fa-hand-holding-dollar', 'mots' => ['impayés', 'retards', 'à relancer'], 'can' => ['module.comptabilite.access', 'comptabilite.recouvrement.access']],
        ['route' => 'esbtp.comptabilite.analytics.index', 'titre' => 'Analytique prédictive', 'groupe' => 'Finances', 'icone' => 'fa-chart-area', 'mots' => ['prévisions', 'risque', 'trésorerie'], 'can' => ['module.comptabilite.access', 'comptabilite.analytics.view']],
        ['route' => 'esbtp.comptabilite.journal-caisse.index', 'titre' => 'Journal de caisse', 'groupe' => 'Finances', 'icone' => 'fa-book-journal-whills', 'mots' => ['journal', 'mouvements de caisse'], 'can' => ['module.comptabilite.access', 'comptabilite.journal.view']],
        ['route' => 'esbtp.comptabilite.reconciliation.index', 'titre' => 'Réconciliation de caisse', 'groupe' => 'Finances', 'icone' => 'fa-scale-balanced', 'mots' => ['rapprochement', 'écarts de caisse', 'comptage'], 'can' => ['module.comptabilite.access', 'comptabilite.reconciliation.view']],
        ['route' => 'esbtp.comptabilite.salaires.index', 'titre' => 'Salaires', 'groupe' => 'Finances', 'icone' => 'fa-money-check-dollar', 'mots' => ['paie', 'bulletin de paie', 'vacataires'], 'can' => ['module.comptabilite.access', 'comptabilite.salaires.view']],

        // Personnel
        ['route' => 'esbtp.personnel.unified.index', 'titre' => 'Gestion du personnel', 'groupe' => 'Personnel', 'icone' => 'fa-people-group', 'mots' => ['personnel', 'secrétaires', 'comptables', 'caissiers', 'utilisateurs', 'comptes'], 'can' => ['personnel.manage']],
        ['route' => 'esbtp.enseignants.index', 'titre' => 'Enseignants', 'groupe' => 'Personnel', 'icone' => 'fa-chalkboard-user', 'mots' => ['professeurs', 'vacataires', 'formateurs'], 'can' => ['personnel.manage']],
        ['route' => 'esbtp.enseignants.create', 'titre' => 'Nouvel enseignant', 'groupe' => 'Personnel', 'icone' => 'fa-user-plus', 'mots' => ['ajouter un professeur', 'recruter', 'vacataire'], 'can' => ['personnel.manage']],
        ['route' => 'esbtp.acces-temporaires.index', 'titre' => 'Accès temporaires', 'groupe' => 'Personnel', 'icone' => 'fa-user-lock', 'mots' => ['délégation', 'permissions temporaires', 'intérim'], 'can' => ['personnel.manage', 'permissions.temporaires.manage']],
        ['route' => 'esbtp.partnerships.index', 'titre' => 'Partenariats', 'groupe' => 'Personnel', 'icone' => 'fa-handshake', 'mots' => ['entreprises', 'stages', 'conventions'], 'can' => ['personnel.manage']],

        // Communication
        ['route' => 'esbtp.annonces.index', 'titre' => 'Annonces', 'groupe' => 'Communication', 'icone' => 'fa-bullhorn', 'mots' => ['messages', 'communiqués', 'informations'], 'can' => ['module.communication.access', 'annonces.view']],
        ['route' => 'esbtp.annonces.create', 'titre' => 'Créer une annonce', 'groupe' => 'Communication', 'icone' => 'fa-pen-nib', 'mots' => ['nouvelle annonce', 'publier', 'envoyer un message'], 'can' => ['module.communication.access', 'annonces.create']],
        ['route' => 'esbtp.communication.mailpulse', 'titre' => 'MailPulse', 'groupe' => 'Communication', 'icone' => 'fa-paper-plane', 'mots' => ['emailing', 'campagnes', 'whatsapp'], 'can' => ['module.communication.access', 'mailpulse.view']],
        ['route' => 'chat.index', 'titre' => 'Messages', 'groupe' => 'Communication', 'icone' => 'fa-comments', 'mots' => ['discussion', 'messagerie', 'chat'], 'can' => ['messages.send']],
        ['route' => 'notifications.index', 'titre' => 'Notifications', 'groupe' => 'Communication', 'icone' => 'fa-bell', 'mots' => ['alertes'], 'libre' => true],

        // Paramètres
        ['route' => 'esbtp.settings.index', 'titre' => 'Paramètres', 'groupe' => 'Paramètres', 'icone' => 'fa-gear', 'mots' => ['réglages', 'configuration', 'établissement', 'logo', 'téléphone', 'pdf'], 'can' => ['system.manage']],
        ['route' => 'esbtp.roles-permissions.index', 'titre' => 'Rôles et permissions', 'groupe' => 'Paramètres', 'icone' => 'fa-user-shield', 'mots' => ['droits', 'accès', 'rôles personnalisés'], 'can' => ['system.manage']],
        ['route' => 'esbtp.custom-roles.index', 'titre' => 'Rôles personnalisés', 'groupe' => 'Paramètres', 'icone' => 'fa-user-gear', 'mots' => ['rôles', 'profils', 'droits'], 'can' => ['system.manage']],
        ['route' => 'esbtp.matricule-config.index', 'titre' => 'Format des matricules', 'groupe' => 'Paramètres', 'icone' => 'fa-hashtag', 'mots' => ['matricule', 'numérotation'], 'can' => ['system.manage']],
        ['route' => 'esbtp.bulletin-style.index', 'titre' => 'Style des bulletins', 'groupe' => 'Paramètres', 'icone' => 'fa-palette', 'mots' => ['mise en page bulletin', 'modèle'], 'can' => ['system.manage']],
        ['route' => 'esbtp.parent-chatbot-onboarding.index', 'titre' => 'Assistant WhatsApp des parents', 'groupe' => 'Paramètres', 'icone' => 'fa-robot', 'mots' => ['chatbot', 'whatsapp', 'parents'], 'can' => ['parent_chatbot.manage']],
        ['route' => 'esbtp.audit.index', 'titre' => "Journal d'audit", 'groupe' => 'Paramètres', 'icone' => 'fa-clock-rotate-left', 'mots' => ['historique', 'traçabilité', 'qui a modifié'], 'can' => ['security.audit.view']],
        ['route' => 'esbtp.audit.comptabilite', 'titre' => 'Audit comptable', 'groupe' => 'Paramètres', 'icone' => 'fa-magnifying-glass-dollar', 'mots' => ['historique des paiements', 'modifications comptables'], 'can' => ['comptabilite.audit.view']],
        ['route' => 'esbtp.audit.user-activity', 'titre' => 'Activité des utilisateurs', 'groupe' => 'Paramètres', 'icone' => 'fa-user-clock', 'mots' => ['connexions', 'surveillance', 'activité'], 'can' => ['security.users.monitor']],

        // Mon espace
        ['route' => 'esbtp.mon-emploi-temps.index', 'titre' => 'Mon emploi du temps', 'groupe' => 'Mon espace', 'icone' => 'fa-calendar-week', 'mots' => ['mes cours', 'horaires'], 'can' => ['identity.student']],
        ['route' => 'teacher.timetable', 'titre' => 'Mon emploi du temps', 'groupe' => 'Mon espace', 'icone' => 'fa-calendar-week', 'mots' => ['mes cours', 'horaires'], 'can' => ['identity.teach']],
        ['route' => 'teacher.attendance', 'titre' => "Faire l'appel", 'groupe' => 'Mon espace', 'icone' => 'fa-clipboard-user', 'mots' => ['présences', 'émargement', 'absences'], 'can' => ['identity.teach']],
        ['route' => 'esbtp.mes-evaluations.index', 'titre' => 'Mes évaluations', 'groupe' => 'Mon espace', 'icone' => 'fa-file-signature', 'mots' => ['devoirs', 'examens à venir'], 'can' => ['identity.student']],
        ['route' => 'esbtp.mes-notes.index', 'titre' => 'Mes notes', 'groupe' => 'Mon espace', 'icone' => 'fa-pen-to-square', 'mots' => ['moyennes', 'résultats'], 'can' => ['identity.student']],
        ['route' => 'esbtp.mes-absences.index', 'titre' => 'Mes absences', 'groupe' => 'Mon espace', 'icone' => 'fa-user-xmark', 'mots' => ['présences', 'justifier une absence'], 'can' => ['identity.student']],
        ['route' => 'esbtp.mon-bulletin.index', 'titre' => 'Mes résultats et bulletin', 'groupe' => 'Mon espace', 'icone' => 'fa-file-lines', 'mots' => ['bulletin', 'rang'], 'can' => ['identity.student']],
        ['route' => 'esbtp.mes-paiements.index', 'titre' => 'Mes paiements', 'groupe' => 'Mon espace', 'icone' => 'fa-receipt', 'mots' => ['reçus', 'scolarité payée', 'reste à payer'], 'can' => ['identity.student']],
        ['route' => 'esbtp.mes-annonces.index', 'titre' => 'Mes annonces', 'groupe' => 'Mon espace', 'icone' => 'fa-bullhorn', 'mots' => ['messages', 'informations'], 'can' => ['identity.student']],
        ['route' => 'esbtp.mon-profil.index', 'titre' => 'Mon profil', 'groupe' => 'Mon espace', 'icone' => 'fa-id-badge', 'mots' => ['profil', 'mot de passe', 'mes informations'], 'can' => ['identity.student']],
        ['route' => 'teacher.profile', 'titre' => 'Mon profil', 'groupe' => 'Mon espace', 'icone' => 'fa-id-badge', 'mots' => ['profil', 'mot de passe'], 'can' => ['identity.teach']],
        ['route' => 'coordinateur.profile', 'titre' => 'Mon profil', 'groupe' => 'Mon espace', 'icone' => 'fa-id-badge', 'mots' => ['profil', 'mot de passe'], 'can' => ['identity.coordinate']],
        ['route' => 'admin.profile', 'titre' => 'Mon profil', 'groupe' => 'Mon espace', 'icone' => 'fa-id-badge', 'mots' => ['profil', 'mot de passe', 'mes informations']],
    ];

    /**
     * Les pages que cet utilisateur peut ouvrir, dans l'ordre du menu. Deux
     * « Mon profil » peuvent passer le filtre (profil d'administration ET
     * profil enseignant) : seul le premier est gardé.
     *
     * @return list<array{route: string, titre: string, groupe: string, icone: string, mots: list<string>, rang: int, url: string}>
     */
    public function pourUtilisateur(?Authorizable $utilisateur): array
    {
        if ($utilisateur === null) {
            return [];
        }

        // Les droits s'évaluent à CHAQUE requête : ils ne tiennent pas qu'aux
        // rôles et permissions Spatie. Gate::after accorde aussi des accès
        // temporaires (qui expirent à une date) et des capacités de scolarité
        // (qui suivent un réglage d'instance) : aucune empreinte des rôles ne
        // les voit changer. Seules les exigences des routes sont mises en
        // cache — voir exigencesDesRoutes(). Rien n'est retenu par
        // utilisateur, pas même le temps d'une instance : le contrôleur qui
        // porte ce service peut survivre à la requête.
        $portes = self::exigencesDesRoutes();
        $pages = [];
        $titresVus = [];

        foreach (self::PAGES as $page) {
            if (! array_key_exists($page['route'], $portes) || ! $this->ouvrable($page, $portes[$page['route']], $utilisateur)) {
                continue;
            }

            $doublon = $page['groupe'].'|'.$page['titre'];
            if (isset($titresVus[$doublon])) {
                continue;
            }
            $titresVus[$doublon] = true;

            $pages[] = [
                'route' => $page['route'],
                'titre' => $page['titre'],
                'groupe' => $page['groupe'],
                'icone' => $page['icone'],
                'mots' => $page['mots'] ?? [],
                'rang' => $page['rang'] ?? 100,
                'url' => route($page['route']),
            ];
        }

        return $pages;
    }

    /**
     * Les exigences de garde de chaque route de l'index, ou null si elle n'a
     * pas de garde lisible. Les routes absentes de l'instance n'y figurent pas.
     *
     * Les LIRE coûte (~200 ms pour l'index : les middlewares de constructeur
     * instancient chaque contrôleur) mais ne dépend d'aucun utilisateur ; elles
     * ne changent qu'avec le code déployé. La clé porte donc la version du code
     * (le commit en place, à défaut la date des fichiers de routes).
     *
     * @return array<string, list<array{0: string, 1: list<string>}>|null>
     */
    private static function exigencesDesRoutes(): array
    {
        return Cache::remember(
            'recherche.portes.'.md5(self::versionDuCode().'|'.implode(',', array_column(self::PAGES, 'route'))),
            now()->addDay(),
            function () {
                $portes = [];
                foreach (self::PAGES as $page) {
                    if (Route::has($page['route'])) {
                        $portes[$page['route']] = PorteDeRoute::exigencesDe($page['route']);
                    }
                }

                return $portes;
            }
        );
    }

    private static function versionDuCode(): string
    {
        $head = base_path('.git/HEAD');
        if (is_readable($head)) {
            $ref = trim((string) @file_get_contents($head));
            if (str_starts_with($ref, 'ref: ')) {
                $fichier = base_path('.git/'.substr($ref, 5));
                $ref = is_readable($fichier) ? trim((string) @file_get_contents($fichier)) : $ref;
            }
            if ($ref !== '') {
                return $ref;
            }
        }

        $dates = array_map(fn ($f) => (string) @filemtime($f), glob(base_path('routes/*.php')) ?: []);

        return implode('-', $dates);
    }

    /**
     * Les pages dont le titre, le groupe ou un mot-clé correspond à la saisie,
     * de la plus pertinente à la moins pertinente.
     *
     * @return list<array>
     */
    public function chercher(string $saisie, ?Authorizable $utilisateur, int $limite): array
    {
        $jetons = self::jetons($saisie);

        if ($jetons === []) {
            return [];
        }

        $trouvees = [];

        foreach ($this->pourUtilisateur($utilisateur) as $ordre => $page) {
            $score = self::score($jetons, self::normaliser($page['titre']), array_map([self::class, 'normaliser'], $page['mots']), self::normaliser($page['groupe']));

            if ($score > 0) {
                $trouvees[] = ['score' => $score, 'ordre' => $ordre, 'page' => $page];
            }
        }

        usort($trouvees, fn ($a, $b) => [$b['score'], $a['ordre']] <=> [$a['score'], $b['ordre']]);

        return array_map(fn ($t) => $t['page'], array_slice($trouvees, 0, $limite));
    }

    /** @return list<array> Les pages proposées palette vide, par rang puis ordre du menu. */
    public function suggestions(?Authorizable $utilisateur, int $limite): array
    {
        $pages = $this->pourUtilisateur($utilisateur);
        $ordonnees = array_keys($pages);
        usort($ordonnees, fn ($a, $b) => [$pages[$a]['rang'], $a] <=> [$pages[$b]['rang'], $b]);

        return array_map(fn ($i) => $pages[$i], array_slice($ordonnees, 0, $limite));
    }

    /** Nombre d'entrées déclarées (indépendamment de l'utilisateur). */
    public static function taille(): int
    {
        return count(self::PAGES);
    }

    /** @param list<array{0: string, 1: list<string>}>|null $exigences */
    private function ouvrable(array $page, ?array $exigences, Authorizable $utilisateur): bool
    {
        $verdict = PorteDeRoute::verdictSelon($exigences, $utilisateur);

        if ($verdict === false) {
            return false;
        }

        $exigees = $page['can'] ?? [];

        foreach ($exigees as $exigence) {
            $tenue = false;
            foreach (explode('|', $exigence) as $permission) {
                if ($utilisateur->can($permission)) {
                    $tenue = true;
                    break;
                }
            }
            if (! $tenue) {
                return false;
            }
        }

        if ($verdict === true) {
            return true;
        }

        // Route sans garde lisible : on ne la montre que si l'entrée a dit
        // elle-même ce qu'il faut tenir, ou qu'elle est ouverte à tout compte.
        return $exigees !== [] || ($page['libre'] ?? false);
    }

    /** @param list<string> $jetons @param list<string> $mots */
    private static function score(array $jetons, string $titre, array $mots, string $groupe): int
    {
        $total = 0;

        foreach ($jetons as $jeton) {
            $meilleur = 0;

            if (str_starts_with($titre, $jeton)) {
                $meilleur = 100;
            } elseif (preg_match('/(^|[\s\'-])'.preg_quote($jeton, '/').'/u', $titre)) {
                $meilleur = 80;
            } elseif (str_contains($titre, $jeton)) {
                $meilleur = 60;
            } else {
                foreach ($mots as $mot) {
                    if (str_starts_with($mot, $jeton) || preg_match('/(^|[\s\'-])'.preg_quote($jeton, '/').'/u', $mot)) {
                        $meilleur = max($meilleur, 50);
                    } elseif (str_contains($mot, $jeton)) {
                        $meilleur = max($meilleur, 35);
                    }
                }
                if ($meilleur === 0 && str_starts_with($groupe, $jeton)) {
                    $meilleur = 20;
                }
            }

            // Chaque mot saisi doit trouver sa place quelque part.
            if ($meilleur === 0) {
                return 0;
            }

            $total += $meilleur;
        }

        return $total;
    }

    /** @return list<string> */
    public static function jetons(string $saisie): array
    {
        return array_values(array_filter(
            preg_split('/[\s,]+/u', self::normaliser($saisie), -1, PREG_SPLIT_NO_EMPTY),
            fn ($jeton) => mb_strlen($jeton) >= 1
        ));
    }

    /** Minuscules sans accents : « Réinscription » et « reinscription » se valent. */
    public static function normaliser(string $texte): string
    {
        $texte = mb_strtolower(Str::ascii(trim($texte)), 'UTF-8');

        return trim(preg_replace('/[^a-z0-9\s\'-]+/', ' ', $texte) ?? $texte);
    }
}
