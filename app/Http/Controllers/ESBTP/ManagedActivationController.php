<?php

namespace App\Http\Controllers\ESBTP;

use App\Http\Controllers\Controller;
use App\Models\ESBTPCandidatureWorkflow;
use App\Services\Admissions\AdmissionAccountActivator;
use App\Services\Admissions\AdmissionWhatsappActivationLink;
use App\Services\Admissions\InscriptionWorkflowSettings;
use App\Services\Admissions\ManagedInscriptionWorkflow;
use App\Services\Verification\ConfirmationContactEcole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;

final class ManagedActivationController extends Controller
{
    public function __construct(
        private readonly ManagedInscriptionWorkflow $managed,
        private readonly InscriptionWorkflowSettings $settings,
        private readonly AdmissionWhatsappActivationLink $whatsapp,
        private readonly AdmissionAccountActivator $activator,
    ) {
    }

    public function signedForm(Request $request, ESBTPCandidatureWorkflow $workflow)
    {
        $this->guardManagedWorkflow();
        $workflow->loadMissing(['candidature', 'etudiant.user']);

        // Même raison que pour le lien par e-mail : pas de page précédente.
        try {
            $this->assertActivatable($workflow, $request->query('v'));
        } catch (ValidationException $e) {
            return response()->view('esbtp.admissions.workflow.activation-indisponible', [
                'motif' => collect($e->errors())->flatten()->first(),
            ], 410);
        }

        $submitUrl = URL::temporarySignedRoute(
            'esbtp.admissions.workflow.activation.signed.submit',
            now()->addMinutes(30),
            ['workflow' => $workflow->id, 'v' => $request->query('v')],
        );

        return view('esbtp.admissions.workflow.activation-signed', [
            'workflow' => $workflow,
            'submitUrl' => $submitUrl,
        ]);
    }

    public function signedActivate(Request $request, ESBTPCandidatureWorkflow $workflow)
    {
        $this->guardManagedWorkflow();
        $this->assertActivatable($workflow, $request->query('v'));

        $data = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $workflow = $this->activator->activateWorkflow($workflow, $data['password']);
        Auth::login($workflow->etudiant->user);
        $request->session()->regenerate();

        return redirect()
            ->route('esbtp.admissions.workflow.student')
            ->with('success', 'Votre espace étudiant est activé. Complétez maintenant vos informations avant le choix de la classe.');
    }

    public function resend(ESBTPCandidatureWorkflow $workflow)
    {
        $this->guardManagedWorkflow();
        $this->assertMilestoneReached($workflow);

        return back()->with('success', $this->envoyerLiens($workflow));
    }

    /**
     * L'étudiant est au guichet : l'agent relit avec lui l'e-mail et le numéro
     * affichés, les confirme, et le lien part dans la foulée. L'empreinte
     * garantit que l'agent confirme bien le contact qu'il avait sous les yeux.
     */
    public function confirmContact(Request $request, ESBTPCandidatureWorkflow $workflow, ConfirmationContactEcole $confirmation)
    {
        $this->guardManagedWorkflow();
        $this->assertMilestoneReached($workflow);
        if ($workflow->accessActivated()) {
            throw ValidationException::withMessages(['activation' => 'Cet espace étudiant est déjà activé.']);
        }

        $empreinte = (string) $request->validate(['empreinte' => ['required', 'string', 'max:128']])['empreinte'];
        [$resultat] = $confirmation->confirmerAuGuichet($workflow->candidature, $empreinte, (int) $request->user()->id);

        if ($resultat === ConfirmationContactEcole::MODIFIE_ENTRE_TEMPS) {
            return back()->with('warning', "Le contact de ce dossier a changé depuis l'affichage de la page. Rechargez-la et relisez-le avec l'étudiant.");
        }

        $prefixe = $resultat === ConfirmationContactEcole::CONFIRME ? 'Contact confirmé. ' : '';

        return back()->with('success', $prefixe.$this->envoyerLiens($workflow->fresh(['candidature', 'etudiant.user'])));
    }

    /** Émet un nouveau lien et dit, sans l'arrondir, par où il est parti. */
    private function envoyerLiens(ESBTPCandidatureWorkflow $workflow): string
    {
        if ($workflow->accessActivated()) {
            throw ValidationException::withMessages(['activation' => 'Cet espace étudiant est déjà activé.']);
        }

        // Le canal e-mail conserve le jeton aléatoire historique. WhatsApp
        // reçoit une URL Laravel signée portant la version de ce jeton : la
        // régénération ci-dessous rend donc caducs TOUS les anciens liens.
        $emailResult = $this->managed->issueActivation($workflow);
        $whatsappSent = $this->whatsapp->sendIfDue($workflow);

        // MailPulse accepte une demande de remise, mais n'atteste pas sa livraison.
        // Une transaction peut différer le dispatch jusqu'au commit : ne pas
        // interpréter cet état comme un échec ou comme un message déjà envoyé.
        $channels = [];
        if ($emailResult['email_sent'] ?? false) {
            $channels[] = 'e-mail accepté par le prestataire';
        }
        if ($whatsappSent) {
            $channels[] = 'WhatsApp accepté par le prestataire';
        }
        if ($emailResult['email_pending'] ?? false) {
            $channels[] = 'e-mail programmé après validation de la transaction';
        }

        return $channels
            ? 'Lien d’activation renouvelé : '.implode(', ', $channels).'. La remise au destinataire reste à confirmer.'
            : "Lien régénéré, mais aucun envoi n'a été confirmé. Vérifiez les contacts, les canaux activés et le suivi MailPulse avant de relancer.";
    }

    private function assertMilestoneReached(ESBTPCandidatureWorkflow $workflow): void
    {
        $workflow->refresh();

        if (! $this->managed->activationMilestoneReached($workflow)) {
            throw ValidationException::withMessages([
                'activation' => "Le dossier n'a pas encore atteint l'étape configurée pour ouvrir l'espace étudiant.",
            ]);
        }
    }

    private function assertActivatable(ESBTPCandidatureWorkflow $workflow, ?string $version): void
    {
        $workflow->refresh();

        if (! $this->whatsapp->isCurrent($workflow, $version) && ! $workflow->accessActivated()) {
            throw ValidationException::withMessages([
                'activation' => "Ce lien a été remplacé par un lien plus récent. Utilisez le dernier message reçu.",
            ]);
        }

        if ($workflow->accessActivated()) {
            throw ValidationException::withMessages([
                'activation' => 'Cet espace étudiant a déjà été activé.',
            ]);
        }

        $this->assertMilestoneReached($workflow);

        if (! $workflow->loadMissing('etudiant.user')->etudiant?->user) {
            throw ValidationException::withMessages([
                'compte' => "Le compte étudiant n'a pas encore été préparé.",
            ]);
        }
    }

    private function guardManagedWorkflow(): void
    {
        abort_unless($this->settings->usesManagedWorkflow(), 404);
    }
}
