// Navigation LMD depuis les notes : ne jamais ouvrir l'aperçu sans avoir
// vérifié l'existence du document officiel. L'émission reste volontaire.
@php
    // Le compilateur Blade interprete mal les appels route() imbriques avec des virgules.
    // La garde Route::has évite les erreurs 500 lors des caches de routes anciens.
    $urlParams = ['etudiant' => '__ETUDIANT__', 'annee' => '__ANNEE__'];
    $lmdStatusTemplate = \Illuminate\Support\Facades\Route::has('esbtp.lmd.releves.status')
        ? route('esbtp.lmd.releves.status', $urlParams) : null;
    $lmdPreviewTemplate = \Illuminate\Support\Facades\Route::has('esbtp.lmd.releves.preview')
        ? route('esbtp.lmd.releves.preview', $urlParams) : null;
    $lmdIssueTemplate = \Illuminate\Support\Facades\Route::has('esbtp.lmd.releves.issue')
        ? route('esbtp.lmd.releves.issue', $urlParams) : null;
@endphp
const lmdReleveStatusTemplate = @json($lmdStatusTemplate);
const lmdRelevePreviewTemplate = @json($lmdPreviewTemplate);
const lmdReleveIssueTemplate = @json($lmdIssueTemplate);
const canIssueReleveLmdFromNotes = @json(auth()->user()->can('lmd.releve.issue') && \App\Support\PorteDeRoute::ouverte('esbtp.lmd.releves.issue', auth()->user()));
function lmdReleveUrl(template, studentId, yearId) {
    return template.replace('__ETUDIANT__', encodeURIComponent(studentId))
        .replace('__ANNEE__', encodeURIComponent(yearId));
}
document.addEventListener('click', async function(event) {
    const btn = event.target.closest('button[data-lmd-releve]');
    if (!btn || btn.disabled) return;
    const studentId = Number(btn.dataset.lmdReleve);
    const yearId = Number(lmdAcademicYearId);
    if (!studentId || !yearId || !lmdReleveStatusTemplate || !lmdRelevePreviewTemplate) return;
    // Réserver l'onglet pendant le clic utilisateur : un window.open() lancé
    // seulement après await fetch() est souvent bloqué par le navigateur.
    // Jamais de navigation de la grille : la saisie courante reste intacte.
    const previewTab = window.open('about:blank', '_blank');
    if (!previewTab) {
        alert('Autorisez l’ouverture de nouveaux onglets pour consulter le relevé LMD sans quitter la saisie.');
        return;
    }
    previewTab.opener = null;
    previewTab.document.title = 'Préparation du relevé LMD';
    btn.disabled = true;
    let opened = false;
    try {
        const response = await fetch(lmdReleveUrl(lmdReleveStatusTemplate, studentId, yearId), {
            credentials: 'same-origin',
            headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'}
        });
        const data = await response.json().catch(() => ({}));
        if (!response.ok) throw new Error(data.message || 'Impossible de vérifier le relevé LMD.');
        const previewUrl = lmdReleveUrl(lmdRelevePreviewTemplate, studentId, yearId);
        if (data.releve) {
            previewTab.location.replace(previewUrl);
            opened = true;
            return;
        }
        if (!data.readiness?.ok) {
            alert((data.readiness?.reasons || ['Le relevé n’est pas encore prêt.']).join('\n'));
            return;
        }
        if (!canIssueReleveLmdFromNotes || !lmdReleveIssueTemplate) {
            alert('Le relevé est prêt, mais vous n’avez pas la permission de l’émettre.');
            return;
        }
        if (!confirm('Émettre le relevé officiel LMD depuis les bulletins publiés ? Cette action est tracée et ne modifie pas les notes.')) return;
        const issue = await fetch(lmdReleveUrl(lmdReleveIssueTemplate, studentId, yearId), {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            }
        });
        const issued = await issue.json().catch(() => ({}));
        if (!issue.ok || !issued.success) {
            throw new Error(issued.message || 'Impossible d’émettre le relevé LMD.');
        }
        previewTab.location.replace(previewUrl);
        opened = true;
    } catch (error) {
        alert(error.message || 'Le relevé LMD n’est pas accessible.');
    } finally {
        if (!opened && !previewTab.closed) previewTab.close();
        if (btn.isConnected) btn.disabled = false;
    }
});

