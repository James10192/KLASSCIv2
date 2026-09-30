@php
    $_workflow = app(\App\Services\Admissions\InscriptionWorkflowSettings::class);
    $_workflow->ensureDefaults();
    $_workflowEnabled = $_workflow->enabled();
    $_workflowMode = $_workflow->mode();
    $_workflowRequireRdv = $_workflow->requiresAppointment();
    $_workflowActivation = $_workflow->accountActivationStep();
    $_workflowClassActor = $_workflow->classChoiceActor();
    $_workflowClassOnce = $_workflow->classChoiceOnce();
    $_workflowNotifyEmail = $_workflow->notifyEmail();
    $_workflowNotifyWhatsApp = $_workflow->notifyWhatsApp();
@endphp

<div class="bc-card" id="rendez-vous-reglages">
    <div class="bc-icon"><i class="fas fa-calendar-check"></i></div>
    <div class="bc-body">
        <div class="bc-label">Rendez-vous au guichet</div>
        <div class="bc-desc">
            Créneaux, jours et horaires se règlent sur la page Rendez-vous, pas ici.
            @can('inscriptions.rdv.configure')
                <a href="{{ route('esbtp.rendez-vous.index') }}#reglages">Ouvrir les réglages</a>
            @elsecan('inscriptions.rdv.view')
                <a href="{{ route('esbtp.rendez-vous.index') }}">Voir le planning</a>
            @endcan
        </div>
    </div>
</div>

<div class="bc-card" id="workflow-inscription-reglages">
    <div class="bc-icon"><i class="fas fa-route"></i></div>
    <div class="bc-body">
        <div class="bc-label">Workflow d'inscription</div>
        <div class="bc-desc">
            Configure l'ordre du parcours propre à cet établissement. Le comportement historique reste le défaut :
            une évolution déployée depuis <strong>presentation</strong> ne change donc aucun autre tenant tant que cette option n'est pas activée.
        </div>

        <div class="row g-2" style="margin-top:.75rem;max-width:760px;">
            <div class="col-12" style="display:flex;align-items:center;gap:.65rem;">
                <input type="hidden" name="setting_{{ \App\Services\Admissions\InscriptionWorkflowSettings::ENABLED }}" value="0">
                <label class="form-switch-modern" style="flex:0 0 auto;">
                    <input type="checkbox"
                           name="setting_{{ \App\Services\Admissions\InscriptionWorkflowSettings::ENABLED }}"
                           value="1" {{ $_workflowEnabled ? 'checked' : '' }}>
                    <span class="slider"></span>
                </label>
                <div class="bc-desc" style="margin:0;">
                    <strong>Activer le workflow configurable pour cet établissement.</strong>
                    Décoché, KLASSCI conserve exactement le parcours historique.
                </div>
            </div>

            <div class="col-12" style="margin-top:.55rem;">
                <label class="bc-desc" for="inscription-workflow-mode" style="display:block;margin-bottom:.25rem;">Ordre des étapes physiques</label>
                <select class="form-control form-control-sm" id="inscription-workflow-mode"
                        name="setting_{{ \App\Services\Admissions\InscriptionWorkflowSettings::MODE }}">
                    @foreach(\App\Services\Admissions\InscriptionWorkflowSettings::modeOptions() as $_value => $_label)
                        <option value="{{ $_value }}" {{ $_workflowMode === $_value ? 'selected' : '' }}>{{ $_label }}</option>
                    @endforeach
                </select>
                <div class="bc-desc" style="margin-top:.25rem;">
                    Pour ESBTP Yamoussoukro : candidature en ligne → rendez-vous → caisse → contrôle physique des pièces → finalisation en ligne.
                </div>
            </div>

            <div class="col-md-6" style="margin-top:.55rem;">
                <label class="bc-desc" for="inscription-account-activation" style="display:block;margin-bottom:.25rem;">Activation du compte étudiant</label>
                <select class="form-control form-control-sm" id="inscription-account-activation"
                        name="setting_{{ \App\Services\Admissions\InscriptionWorkflowSettings::ACCOUNT_ACTIVATION_STEP }}">
                    @foreach(\App\Services\Admissions\InscriptionWorkflowSettings::activationOptions() as $_value => $_label)
                        <option value="{{ $_value }}" {{ $_workflowActivation === $_value ? 'selected' : '' }}>{{ $_label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-6" style="margin-top:.55rem;">
                <label class="bc-desc" for="inscription-class-actor" style="display:block;margin-bottom:.25rem;">Choix de la classe</label>
                <select class="form-control form-control-sm" id="inscription-class-actor"
                        name="setting_{{ \App\Services\Admissions\InscriptionWorkflowSettings::CLASS_CHOICE_ACTOR }}">
                    @foreach(\App\Services\Admissions\InscriptionWorkflowSettings::classActorOptions() as $_value => $_label)
                        <option value="{{ $_value }}" {{ $_workflowClassActor === $_value ? 'selected' : '' }}>{{ $_label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-12" style="margin-top:.65rem;display:grid;gap:.55rem;">
                @foreach([
                    [\App\Services\Admissions\InscriptionWorkflowSettings::REQUIRE_RDV, $_workflowRequireRdv, 'Rendez-vous obligatoire avant le passage au guichet'],
                    [\App\Services\Admissions\InscriptionWorkflowSettings::CLASS_CHOICE_ONCE, $_workflowClassOnce, "Si l'étudiant choisit sa classe, le choix est verrouillé après sa première confirmation"],
                    [\App\Services\Admissions\InscriptionWorkflowSettings::NOTIFY_EMAIL, $_workflowNotifyEmail, "Envoyer les accès et les étapes par e-mail"],
                    [\App\Services\Admissions\InscriptionWorkflowSettings::NOTIFY_WHATSAPP, $_workflowNotifyWhatsApp, "Envoyer les accès et les étapes par WhatsApp quand le canal est disponible"],
                ] as [$_key, $_checked, $_label])
                    <div style="display:flex;align-items:center;gap:.65rem;">
                        <input type="hidden" name="setting_{{ $_key }}" value="0">
                        <label class="form-switch-modern" style="flex:0 0 auto;">
                            <input type="checkbox" name="setting_{{ $_key }}" value="1" {{ $_checked ? 'checked' : '' }}>
                            <span class="slider"></span>
                        </label>
                        <div class="bc-desc" style="margin:0;">{{ $_label }}</div>
                    </div>
                @endforeach
            </div>

            <div class="col-12" style="margin-top:.65rem;">
                <div class="bc-desc" style="padding:.65rem .8rem;border:1px solid #dbe4f0;border-radius:10px;background:#f8fafc;">
                    <strong>Principe multi-tenant :</strong> ce bloc configure le tenant courant uniquement. Le code commun peut être propagé à toutes les instances sans imposer le workflow de Yamoussoukro à Abidjan, USAT, ISTLG ou à un futur établissement.
                </div>
            </div>
        </div>
    </div>
</div>