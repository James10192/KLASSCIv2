<?php

namespace App\Services\Audit;

use App\Models\ESBTPAttendance;
use App\Models\ESBTPBulletin;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPExamenPlanifie;
use App\Models\ESBTPFacture;
use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPFraisOption;
use App\Models\ESBTPFraisSubscription;
use App\Models\ESBTPInscription;
use App\Models\ESBTPLMDJury;
use App\Models\ESBTPLMDJuryDecision;
use App\Models\ESBTPLMDJuryMembre;
use App\Models\ESBTPLMDSession;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPNote;
use App\Models\ESBTPPaiement;
use App\Models\ESBTPPlanificationAcademique;
use App\Models\ESBTPResultat;
use App\Models\ESBTPStudentAccessibilityProfile;
use App\Models\ESBTPTeacher;
use App\Models\ESBTPTpeDeclaration;
use App\Models\ESBTPUniteEnseignement;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Models\Audit;

/**
 * Résout les liens entités liées (étudiant, inscription, catégorie de frais, …)
 * pour un événement d'audit donné, afin qu'on puisse retracer le contexte
 * métier depuis la page /esbtp/audit/{id}.
 *
 * Sur les entités encore existantes : eager-load la relation et construit le
 * lien. Sur les entités supprimées : tente de reconstruire depuis les FK
 * stockées dans `old_values` / `new_values` (la table audits conserve l'état).
 */
class AuditEntityResolver
{
    /**
     * @return array<int, array{key:string,label:string,value:string,sublabel:?string,route:?string,icon:string,emphasis:string}>
     */
    public function resolve(Audit $audit): array
    {
        $type = $audit->auditable_type;
        if (! $type || ! class_exists($type)) {
            return [];
        }

        $id = $audit->auditable_id;
        $payload = $this->fingerprint($audit);

        return match ($type) {
            ESBTPFraisSubscription::class => $this->resolveFraisSubscription($id, $payload),
            ESBTPPaiement::class => $this->resolvePaiement($id, $payload),
            ESBTPInscription::class => $this->resolveInscription($id, $payload),
            ESBTPEtudiant::class => $this->resolveEtudiant($id, $payload),
            ESBTPNote::class => $this->resolveNote($id, $payload),
            ESBTPEvaluation::class => $this->resolveEvaluation($id, $payload),
            ESBTPBulletin::class => $this->resolveBulletin($id, $payload),
            ESBTPAttendance::class => $this->resolveAttendance($id, $payload),
            ESBTPFacture::class => $this->resolveFacture($id, $payload),
            ESBTPFraisCategory::class => $this->resolveFraisCategory($id, $payload),
            ESBTPFraisOption::class => $this->resolveFraisOption($id, $payload),
            ESBTPClasse::class => $this->resolveClasse($id, $payload),
            ESBTPMatiere::class => $this->resolveMatiere($id, $payload),
            ESBTPLMDJury::class => $this->resolveLMDJury($id, $payload),
            ESBTPLMDJuryDecision::class => $this->resolveLMDJuryDecision($id, $payload),
            ESBTPLMDJuryMembre::class => $this->resolveLMDJuryMembre($id, $payload),
            ESBTPLMDSession::class => $this->resolveLMDSession($id, $payload),
            ESBTPResultat::class => $this->resolveResultat($id, $payload),
            ESBTPExamenPlanifie::class => $this->resolveExamenPlanifie($id, $payload),
            ESBTPTeacher::class => $this->resolveTeacher($id, $payload),
            ESBTPTpeDeclaration::class => $this->resolveTpeDeclaration($id, $payload),
            ESBTPUniteEnseignement::class => $this->resolveUniteEnseignement($id, $payload),
            ESBTPPlanificationAcademique::class => $this->resolvePlanificationAcademique($id, $payload),
            ESBTPStudentAccessibilityProfile::class => $this->resolveAccessibilityProfile($id, $payload),
            User::class => $this->resolveUser($id, $payload),
            default => [],
        };
    }

    /* ────────────────────────────  RESOLVERS  ──────────────────────────── */

    private function resolveFraisSubscription(int $id, array $payload): array
    {
        $sub = ESBTPFraisSubscription::with([
            'inscription.etudiant',
            'inscription.classe.filiere',
            'inscription.anneeUniversitaire',
            'fraisCategory',
            'selectedOption.fraisCategory',
            'createdBy',
        ])->find($id);

        $links = [];

        // Étudiant
        $etudiant = $sub?->inscription?->etudiant
            ?? $this->etudiantFromInscriptionId($payload['inscription_id'] ?? $sub?->inscription_id ?? null);
        $this->push($links, $this->linkEtudiant($etudiant));

        // Inscription
        $inscriptionId = $sub?->inscription_id ?? ($payload['inscription_id'] ?? null);
        if ($inscriptionId) {
            $this->push($links, $this->linkInscription($sub?->inscription, $inscriptionId));
        }

        // Classe
        if ($sub?->inscription?->classe) {
            $this->push($links, $this->linkClasse($sub->inscription->classe));
        }

        // Catégorie de frais
        $categoryId = $sub?->frais_category_id ?? ($payload['frais_category_id'] ?? null);
        if ($categoryId) {
            $category = $sub?->fraisCategory ?? ESBTPFraisCategory::find($categoryId);
            $this->push($links, $this->linkFraisCategory($category, $categoryId));
        }

        // Option sélectionnée
        $optionId = $sub?->selected_option_id ?? ($payload['selected_option_id'] ?? null);
        if ($optionId) {
            $option = $sub?->selectedOption ?? ESBTPFraisOption::find($optionId);
            if ($option) {
                $links[] = [
                    'key' => 'frais_option',
                    'label' => 'Option choisie',
                    'value' => $option->name ?? ('Option #'.$option->id),
                    'sublabel' => isset($option->amount) ? $this->money($option->amount) : null,
                    'route' => null,
                    'icon' => 'fa-list-check',
                    'emphasis' => 'normal',
                ];
            }
        }

        // Créé par
        if ($sub?->createdBy) {
            $this->push($links, $this->linkUser($sub->createdBy, 'Créé par'));
        }

        return $links;
    }

    private function resolvePaiement(int $id, array $payload): array
    {
        $paiement = ESBTPPaiement::with([
            'etudiant',
            'inscription.classe',
            'fraisCategory',
            'anneeUniversitaire',
            'relance',
            'createdBy',
        ])->find($id);

        $links = [];

        $etudiant = $paiement?->etudiant
            ?? $this->etudiantFromId($payload['etudiant_id'] ?? null);
        $this->push($links, $this->linkEtudiant($etudiant));

        $inscriptionId = $paiement?->inscription_id ?? ($payload['inscription_id'] ?? null);
        if ($inscriptionId) {
            $this->push($links, $this->linkInscription($paiement?->inscription, $inscriptionId));
        }

        if ($paiement?->inscription?->classe) {
            $this->push($links, $this->linkClasse($paiement->inscription->classe));
        }

        $categoryId = $paiement?->frais_category_id ?? ($payload['frais_category_id'] ?? null);
        if ($categoryId) {
            $category = $paiement?->fraisCategory ?? ESBTPFraisCategory::find($categoryId);
            $this->push($links, $this->linkFraisCategory($category, $categoryId));
        }

        if ($paiement?->anneeUniversitaire) {
            $links[] = [
                'key' => 'annee_universitaire',
                'label' => 'Année universitaire',
                'value' => $paiement->anneeUniversitaire->name ?? ('#'.$paiement->annee_universitaire_id),
                'sublabel' => null,
                'route' => null,
                'icon' => 'fa-calendar-alt',
                'emphasis' => 'normal',
            ];
        }

        if ($paiement?->createdBy) {
            $this->push($links, $this->linkUser($paiement->createdBy, 'Créé par'));
        }

        return $links;
    }

    private function resolveInscription(int $id, array $payload): array
    {
        $inscription = ESBTPInscription::with([
            'etudiant',
            'classe.filiere',
            'anneeUniversitaire',
            'filiere',
            'niveau',
            'createdBy',
        ])->find($id);

        $links = [];

        $etudiant = $inscription?->etudiant ?? $this->etudiantFromId($payload['etudiant_id'] ?? null);
        $this->push($links, $this->linkEtudiant($etudiant));

        if ($inscription?->classe) {
            $this->push($links, $this->linkClasse($inscription->classe));
        }

        if ($inscription?->anneeUniversitaire) {
            $links[] = [
                'key' => 'annee_universitaire',
                'label' => 'Année universitaire',
                'value' => $inscription->anneeUniversitaire->name,
                'sublabel' => null,
                'route' => null,
                'icon' => 'fa-calendar-alt',
                'emphasis' => 'normal',
            ];
        }

        if ($inscription?->createdBy) {
            $this->push($links, $this->linkUser($inscription->createdBy, 'Inscrit par'));
        }

        return $links;
    }

    private function resolveEtudiant(int $id, array $payload): array
    {
        $etudiant = ESBTPEtudiant::with(['user', 'inscription.classe', 'classe', 'createdBy'])->find($id);

        $links = [];

        if ($etudiant?->user) {
            $this->push($links, $this->linkUser($etudiant->user, 'Compte utilisateur'));
        }

        $inscription = $etudiant?->inscription;
        if ($inscription) {
            $this->push($links, $this->linkInscription($inscription, $inscription->id));
            if ($inscription->classe) {
                $this->push($links, $this->linkClasse($inscription->classe));
            }
        } elseif ($etudiant?->classe) {
            $this->push($links, $this->linkClasse($etudiant->classe));
        }

        if ($etudiant?->createdBy) {
            $this->push($links, $this->linkUser($etudiant->createdBy, 'Créé par'));
        }

        return $links;
    }

    private function resolveNote(int $id, array $payload): array
    {
        $note = ESBTPNote::with([
            'etudiant',
            'evaluation.matiere',
            'matiere',
            'classe',
            'createdBy',
        ])->find($id);

        $links = [];
        $this->push($links, $this->linkEtudiant(
            $note?->etudiant ?? $this->etudiantFromId($payload['etudiant_id'] ?? null)
        ));

        if ($note?->evaluation) {
            $links[] = [
                'key' => 'evaluation',
                'label' => 'Évaluation',
                'value' => $note->evaluation->title ?? $note->evaluation->name ?? ('Évaluation #'.$note->evaluation->id),
                'sublabel' => $note->evaluation->date?->format('d/m/Y'),
                'route' => $this->safeRoute('esbtp.evaluations.show', $note->evaluation->id),
                'icon' => 'fa-clipboard-list',
                'emphasis' => 'normal',
            ];
        }

        $matiere = $note?->matiere ?? $note?->evaluation?->matiere;
        if ($matiere) {
            $this->push($links, $this->linkMatiere($matiere));
        }

        if ($note?->classe) {
            $this->push($links, $this->linkClasse($note->classe));
        }

        if ($note?->createdBy) {
            $this->push($links, $this->linkUser($note->createdBy, 'Saisie par'));
        }

        return $links;
    }

    private function resolveEvaluation(int $id, array $payload): array
    {
        $eval = ESBTPEvaluation::with(['matiere', 'classe', 'anneeUniversitaire', 'enseignant', 'createdBy'])->find($id);

        $links = [];
        if ($eval?->matiere) {
            $this->push($links, $this->linkMatiere($eval->matiere));
        }
        if ($eval?->classe) {
            $this->push($links, $this->linkClasse($eval->classe));
        }
        if ($eval?->enseignant) {
            $this->push($links, $this->linkUser($eval->enseignant, 'Enseignant'));
        }
        if ($eval?->createdBy) {
            $this->push($links, $this->linkUser($eval->createdBy, 'Créée par'));
        }
        return $links;
    }

    private function resolveBulletin(int $id, array $payload): array
    {
        $bulletin = ESBTPBulletin::with(['etudiant', 'classe', 'anneeUniversitaire', 'user'])->find($id);

        $links = [];
        $this->push($links, $this->linkEtudiant(
            $bulletin?->etudiant ?? $this->etudiantFromId($payload['etudiant_id'] ?? null)
        ));
        if ($bulletin?->classe) {
            $this->push($links, $this->linkClasse($bulletin->classe));
        }
        if ($bulletin?->user) {
            $this->push($links, $this->linkUser($bulletin->user, 'Généré par'));
        }
        return $links;
    }

    private function resolveAttendance(int $id, array $payload): array
    {
        $att = ESBTPAttendance::with(['etudiant', 'classe', 'matiere', 'teacher.user', 'createdBy'])->find($id);

        $links = [];
        $this->push($links, $this->linkEtudiant(
            $att?->etudiant ?? $this->etudiantFromId($payload['etudiant_id'] ?? null)
        ));
        if ($att?->classe) {
            $this->push($links, $this->linkClasse($att->classe));
        }
        if ($att?->matiere) {
            $this->push($links, $this->linkMatiere($att->matiere));
        }
        if ($att?->teacher?->user) {
            $this->push($links, $this->linkUser($att->teacher->user, 'Enseignant'));
        }
        if ($att?->createdBy) {
            $this->push($links, $this->linkUser($att->createdBy, 'Saisie par'));
        }
        return $links;
    }

    private function resolveFacture(int $id, array $payload): array
    {
        $facture = ESBTPFacture::with(['etudiant', 'inscription', 'anneeUniversitaire'])->find($id);

        $links = [];
        $this->push($links, $this->linkEtudiant(
            $facture?->etudiant ?? $this->etudiantFromId($payload['etudiant_id'] ?? null)
        ));
        $inscriptionId = $facture?->inscription_id ?? ($payload['inscription_id'] ?? null);
        if ($inscriptionId) {
            $this->push($links, $this->linkInscription($facture?->inscription, $inscriptionId));
        }
        return $links;
    }

    private function resolveFraisCategory(int $id, array $payload): array
    {
        $cat = ESBTPFraisCategory::find($id);
        if (! $cat) {
            return [];
        }
        return [[
            'key' => 'frais_category_self',
            'label' => 'Catégorie de frais',
            'value' => $cat->name,
            'sublabel' => ($cat->is_optional ?? false) ? 'Optionnelle' : 'Obligatoire',
            'route' => null,
            'icon' => 'fa-tag',
            'emphasis' => 'primary',
        ]];
    }

    private function resolveFraisOption(int $id, array $payload): array
    {
        $opt = ESBTPFraisOption::with(['fraisCategory', 'createdBy'])->find($id);
        $links = [];
        $categoryId = $opt?->frais_category_id ?? ($payload['frais_category_id'] ?? null);
        if ($categoryId) {
            $category = $opt?->fraisCategory ?? ESBTPFraisCategory::find($categoryId);
            $this->push($links, $this->linkFraisCategory($category, $categoryId));
        }
        if ($opt?->createdBy) {
            $this->push($links, $this->linkUser($opt->createdBy, 'Créée par'));
        }
        return $links;
    }

    private function resolveClasse(int $id, array $payload): array
    {
        $classe = ESBTPClasse::with(['filiere', 'niveau', 'anneeUniversitaire', 'parcours', 'createdBy'])->find($id);
        $links = [];
        if ($classe?->filiere) {
            $links[] = [
                'key' => 'filiere',
                'label' => 'Filière',
                'value' => $classe->filiere->name,
                'sublabel' => $classe->filiere->code ?? null,
                'route' => $this->safeRoute('esbtp.filieres.show', $classe->filiere->id),
                'icon' => 'fa-stream',
                'emphasis' => 'normal',
            ];
        }
        if ($classe?->niveau) {
            $links[] = [
                'key' => 'niveau',
                'label' => 'Niveau',
                'value' => $classe->niveau->name,
                'sublabel' => null,
                'route' => $this->safeRoute('esbtp.niveaux-etudes.show', $classe->niveau->id),
                'icon' => 'fa-layer-group',
                'emphasis' => 'normal',
            ];
        }
        if ($classe?->createdBy) {
            $this->push($links, $this->linkUser($classe->createdBy, 'Créée par'));
        }
        return $links;
    }

    private function resolveMatiere(int $id, array $payload): array
    {
        $mat = ESBTPMatiere::with(['filiere', 'uniteEnseignement', 'createdBy'])->find($id);
        $links = [];
        if ($mat?->filiere) {
            $links[] = [
                'key' => 'filiere',
                'label' => 'Filière',
                'value' => $mat->filiere->name,
                'sublabel' => $mat->filiere->code ?? null,
                'route' => $this->safeRoute('esbtp.filieres.show', $mat->filiere->id),
                'icon' => 'fa-stream',
                'emphasis' => 'normal',
            ];
        }
        if ($mat?->uniteEnseignement) {
            $links[] = [
                'key' => 'ue',
                'label' => 'Unité d\'enseignement',
                'value' => $mat->uniteEnseignement->name,
                'sublabel' => $mat->uniteEnseignement->code ?? null,
                'route' => $this->safeRoute('esbtp.lmd.ue.show', $mat->uniteEnseignement->id),
                'icon' => 'fa-cubes',
                'emphasis' => 'normal',
            ];
        }
        if ($mat?->createdBy) {
            $this->push($links, $this->linkUser($mat->createdBy, 'Créée par'));
        }
        return $links;
    }

    private function resolveLMDJury(int $id, array $payload): array
    {
        $jury = ESBTPLMDJury::with(['classe', 'session', 'parcours', 'anneeUniversitaire'])->find($id);
        $links = [];
        if ($jury?->classe) {
            $this->push($links, $this->linkClasse($jury->classe));
        }
        if ($jury?->session) {
            $links[] = [
                'key' => 'lmd_session',
                'label' => 'Session LMD',
                'value' => $jury->session->name ?? ('Session #'.$jury->session->id),
                'sublabel' => null,
                'route' => $this->safeRoute('esbtp.lmd.sessions.show', $jury->session->id),
                'icon' => 'fa-calendar-check',
                'emphasis' => 'normal',
            ];
        }
        if ($jury?->parcours) {
            $links[] = [
                'key' => 'parcours',
                'label' => 'Parcours',
                'value' => $jury->parcours->name,
                'sublabel' => $jury->parcours->code ?? null,
                'route' => $this->safeRoute('esbtp.lmd.parcours.show', $jury->parcours->id),
                'icon' => 'fa-route',
                'emphasis' => 'normal',
            ];
        }
        return $links;
    }

    private function resolveLMDJuryDecision(int $id, array $payload): array
    {
        $dec = ESBTPLMDJuryDecision::with(['jury', 'etudiant', 'bulletin'])->find($id);
        $links = [];
        $this->push($links, $this->linkEtudiant(
            $dec?->etudiant ?? $this->etudiantFromId($payload['etudiant_id'] ?? null)
        ));
        if ($dec?->jury) {
            $links[] = [
                'key' => 'jury',
                'label' => 'Jury',
                'value' => $dec->jury->pv_numero ?? ('Jury #'.$dec->jury->id),
                'sublabel' => $dec->jury->status ?? null,
                'route' => $this->safeRoute('esbtp.lmd.jurys.show', $dec->jury->id),
                'icon' => 'fa-gavel',
                'emphasis' => 'primary',
            ];
        }
        return $links;
    }

    private function resolveLMDJuryMembre(int $id, array $payload): array
    {
        $mem = ESBTPLMDJuryMembre::with(['jury', 'user'])->find($id);
        $links = [];
        if ($mem?->jury) {
            $links[] = [
                'key' => 'jury',
                'label' => 'Jury',
                'value' => $mem->jury->pv_numero ?? ('Jury #'.$mem->jury->id),
                'sublabel' => null,
                'route' => $this->safeRoute('esbtp.lmd.jurys.show', $mem->jury->id),
                'icon' => 'fa-gavel',
                'emphasis' => 'normal',
            ];
        }
        if ($mem?->user) {
            $this->push($links, $this->linkUser($mem->user, 'Membre'));
        }
        return $links;
    }

    private function resolveLMDSession(int $id, array $payload): array
    {
        $session = ESBTPLMDSession::with(['anneeUniversitaire', 'parcours'])->find($id);
        $links = [];
        if ($session?->parcours) {
            $links[] = [
                'key' => 'parcours',
                'label' => 'Parcours',
                'value' => $session->parcours->name,
                'sublabel' => $session->parcours->code ?? null,
                'route' => $this->safeRoute('esbtp.lmd.parcours.show', $session->parcours->id),
                'icon' => 'fa-route',
                'emphasis' => 'normal',
            ];
        }
        return $links;
    }

    private function resolveResultat(int $id, array $payload): array
    {
        $res = ESBTPResultat::with(['etudiant', 'classe', 'matiere', 'enseignant'])->find($id);
        $links = [];
        $this->push($links, $this->linkEtudiant(
            $res?->etudiant ?? $this->etudiantFromId($payload['etudiant_id'] ?? null)
        ));
        if ($res?->classe) {
            $this->push($links, $this->linkClasse($res->classe));
        }
        if ($res?->matiere) {
            $this->push($links, $this->linkMatiere($res->matiere));
        }
        if ($res?->enseignant) {
            $this->push($links, $this->linkUser($res->enseignant, 'Enseignant'));
        }
        return $links;
    }

    private function resolveExamenPlanifie(int $id, array $payload): array
    {
        $exam = ESBTPExamenPlanifie::with(['classe', 'matiere', 'parcours', 'createdBy'])->find($id);
        $links = [];
        if ($exam?->classe) {
            $this->push($links, $this->linkClasse($exam->classe));
        }
        if ($exam?->matiere) {
            $this->push($links, $this->linkMatiere($exam->matiere));
        }
        if ($exam?->createdBy) {
            $this->push($links, $this->linkUser($exam->createdBy, 'Planifié par'));
        }
        return $links;
    }

    private function resolveTeacher(int $id, array $payload): array
    {
        $teacher = ESBTPTeacher::with(['user', 'createdBy'])->find($id);
        $links = [];
        if ($teacher?->user) {
            $this->push($links, $this->linkUser($teacher->user, 'Compte utilisateur'));
        }
        if ($teacher?->createdBy) {
            $this->push($links, $this->linkUser($teacher->createdBy, 'Créé par'));
        }
        return $links;
    }

    private function resolveTpeDeclaration(int $id, array $payload): array
    {
        $tpe = ESBTPTpeDeclaration::with(['etudiant', 'matiere', 'createdBy'])->find($id);
        $links = [];
        $this->push($links, $this->linkEtudiant(
            $tpe?->etudiant ?? $this->etudiantFromId($payload['etudiant_id'] ?? null)
        ));
        if ($tpe?->matiere) {
            $this->push($links, $this->linkMatiere($tpe->matiere));
        }
        if ($tpe?->createdBy) {
            $this->push($links, $this->linkUser($tpe->createdBy, 'Déclarée par'));
        }
        return $links;
    }

    private function resolveUniteEnseignement(int $id, array $payload): array
    {
        $ue = ESBTPUniteEnseignement::with(['filiere', 'niveau', 'parcours', 'createdBy'])->find($id);
        $links = [];
        if ($ue?->filiere) {
            $links[] = [
                'key' => 'filiere',
                'label' => 'Filière',
                'value' => $ue->filiere->name,
                'sublabel' => null,
                'route' => $this->safeRoute('esbtp.filieres.show', $ue->filiere->id),
                'icon' => 'fa-stream',
                'emphasis' => 'normal',
            ];
        }
        if ($ue?->parcours) {
            $links[] = [
                'key' => 'parcours',
                'label' => 'Parcours',
                'value' => $ue->parcours->name,
                'sublabel' => $ue->parcours->code ?? null,
                'route' => $this->safeRoute('esbtp.lmd.parcours.show', $ue->parcours->id),
                'icon' => 'fa-route',
                'emphasis' => 'normal',
            ];
        }
        if ($ue?->createdBy) {
            $this->push($links, $this->linkUser($ue->createdBy, 'Créée par'));
        }
        return $links;
    }

    private function resolvePlanificationAcademique(int $id, array $payload): array
    {
        $plan = ESBTPPlanificationAcademique::with(['filiere', 'matiere', 'anneeUniversitaire', 'createdBy'])->find($id);
        $links = [];
        if ($plan?->matiere) {
            $this->push($links, $this->linkMatiere($plan->matiere));
        }
        if ($plan?->filiere) {
            $links[] = [
                'key' => 'filiere',
                'label' => 'Filière',
                'value' => $plan->filiere->name,
                'sublabel' => null,
                'route' => $this->safeRoute('esbtp.filieres.show', $plan->filiere->id),
                'icon' => 'fa-stream',
                'emphasis' => 'normal',
            ];
        }
        if ($plan?->createdBy) {
            $this->push($links, $this->linkUser($plan->createdBy, 'Planifié par'));
        }
        return $links;
    }

    private function resolveAccessibilityProfile(int $id, array $payload): array
    {
        $profile = ESBTPStudentAccessibilityProfile::with(['etudiant', 'createdBy'])->find($id);
        $links = [];
        $this->push($links, $this->linkEtudiant(
            $profile?->etudiant ?? $this->etudiantFromId($payload['etudiant_id'] ?? null)
        ));
        if ($profile?->createdBy) {
            $this->push($links, $this->linkUser($profile->createdBy, 'Configuré par'));
        }
        return $links;
    }

    private function resolveUser(int $id, array $payload): array
    {
        $user = User::with('roles:id,name')->find($id);
        if (! $user) {
            return [];
        }
        $roleNames = $user->roles->pluck('name')->implode(', ') ?: '—';
        return [[
            'key' => 'user_roles',
            'label' => 'Rôles',
            'value' => $roleNames,
            'sublabel' => $user->email,
            'route' => null,
            'icon' => 'fa-user-shield',
            'emphasis' => 'normal',
        ]];
    }

    /* ────────────────────────────  HELPERS  ──────────────────────────── */

    private function linkEtudiant(?ESBTPEtudiant $etudiant): ?array
    {
        if (! $etudiant) {
            return null;
        }
        // ESBTPEtudiant utilise `prenoms` (avec s) — pas `prenom`. L'accesseur
        // `nom_complet` renvoie déjà "prenoms nom" mais on garde la composition
        // manuelle pour conserver le contrôle de l'ordre (nom en majuscules,
        // prénoms en titre — convention ivoirienne pour l'identité officielle).
        $nom = trim((string) ($etudiant->nom ?? ''));
        $prenoms = trim((string) ($etudiant->prenoms ?? ''));
        if ($nom !== '' && $prenoms !== '') {
            $fullName = $nom.' '.$prenoms;
        } else {
            $fullName = ($nom !== '' ? $nom : $prenoms) ?: ('Étudiant #'.$etudiant->id);
        }
        return [
            'key' => 'etudiant',
            'label' => 'Étudiant',
            'value' => $fullName,
            'sublabel' => $etudiant->matricule ?? null,
            'route' => $this->safeRoute('esbtp.etudiants.show', $etudiant->id),
            'icon' => 'fa-user-graduate',
            'emphasis' => 'primary',
        ];
    }

    private function linkInscription(?ESBTPInscription $inscription, int $inscriptionId): ?array
    {
        $sublabel = null;
        if ($inscription) {
            $parts = [];
            if ($inscription->anneeUniversitaire?->name) {
                $parts[] = $inscription->anneeUniversitaire->name;
            }
            if ($inscription->status) {
                $parts[] = ucfirst($inscription->status);
            }
            if ($parts) {
                $sublabel = implode(' · ', $parts);
            }
        }
        return [
            'key' => 'inscription',
            'label' => 'Inscription',
            'value' => '#'.$inscriptionId,
            'sublabel' => $sublabel,
            'route' => $this->safeRoute('esbtp.inscriptions.show', $inscriptionId),
            'icon' => 'fa-file-signature',
            'emphasis' => 'primary',
        ];
    }

    private function linkClasse(?ESBTPClasse $classe): ?array
    {
        if (! $classe) {
            return null;
        }
        $sublabel = null;
        if (isset($classe->filiere?->code)) {
            $sublabel = $classe->filiere->code;
        }
        return [
            'key' => 'classe',
            'label' => 'Classe',
            'value' => $classe->name ?? ('Classe #'.$classe->id),
            'sublabel' => $sublabel,
            'route' => $this->safeRoute('esbtp.classes.show', $classe->id),
            'icon' => 'fa-chalkboard',
            'emphasis' => 'normal',
        ];
    }

    private function linkMatiere(?ESBTPMatiere $matiere): ?array
    {
        if (! $matiere) {
            return null;
        }
        return [
            'key' => 'matiere',
            'label' => 'Matière',
            'value' => $matiere->name ?? ('Matière #'.$matiere->id),
            'sublabel' => $matiere->code ?? null,
            'route' => $this->safeRoute('esbtp.matieres.show', $matiere->id),
            'icon' => 'fa-book',
            'emphasis' => 'normal',
        ];
    }

    private function linkFraisCategory(?ESBTPFraisCategory $category, int $id): ?array
    {
        if (! $category) {
            return [
                'key' => 'frais_category',
                'label' => 'Catégorie de frais',
                'value' => 'Catégorie #'.$id.' (supprimée)',
                'sublabel' => null,
                'route' => null,
                'icon' => 'fa-tag',
                'emphasis' => 'muted',
            ];
        }
        return [
            'key' => 'frais_category',
            'label' => 'Catégorie de frais',
            'value' => $category->name,
            'sublabel' => ($category->is_optional ?? false) ? 'Optionnelle' : 'Obligatoire',
            'route' => null,
            'icon' => 'fa-tag',
            'emphasis' => 'primary',
        ];
    }

    private function linkUser(?User $user, string $label): ?array
    {
        if (! $user) {
            return null;
        }
        return [
            'key' => 'user_'.\Illuminate\Support\Str::slug($label, '_'),
            'label' => $label,
            'value' => $user->name ?? ('Utilisateur #'.$user->id),
            'sublabel' => $user->email ?? null,
            'route' => $this->safeRoute('esbtp.users.show', $user->id),
            'icon' => 'fa-user',
            'emphasis' => 'normal',
        ];
    }

    private function etudiantFromInscriptionId(?int $inscriptionId): ?ESBTPEtudiant
    {
        if (! $inscriptionId) {
            return null;
        }
        return ESBTPInscription::with('etudiant')->find($inscriptionId)?->etudiant;
    }

    private function etudiantFromId(?int $etudiantId): ?ESBTPEtudiant
    {
        if (! $etudiantId) {
            return null;
        }
        return ESBTPEtudiant::find($etudiantId);
    }

    private function safeRoute(string $name, ...$params): ?string
    {
        try {
            return route($name, $params);
        } catch (\Throwable) {
            return null;
        }
    }

    private function money($amount): string
    {
        return number_format((float) $amount, 0, ',', ' ').' FCFA';
    }

    private function push(array &$links, ?array $link): void
    {
        if ($link !== null) {
            $links[] = $link;
        }
    }

    /**
     * Construit le payload combiné (old + new) pour reconstruire le contexte
     * d'une entité supprimée (FK conservées dans old_values).
     */
    private function fingerprint(Audit $audit): array
    {
        $old = $this->normalize($audit->old_values);
        $new = $this->normalize($audit->new_values);
        return array_merge($old, $new);
    }

    private function normalize($value): array
    {
        if (empty($value)) {
            return [];
        }
        if (is_array($value)) {
            return $value;
        }
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }
        return [];
    }
}
