# Rendez-vous d'inscription — où la chaîne se coupe (22 septembre 2026)

Demande de Marcel : « je ne vois aucun rendez-vous, et je ne vois pas de mail parti,
ni sur MailPulse ni sur KLASSCI ».

Chaque affirmation ci-dessous porte la commande qui l'établit. Rien n'a été écrit sur
une instance : les mesures passent par l'appel en lecture que fait le navigateur d'une
famille qui ouvre la page de réservation.

## 1. L'état réel du canal, instance par instance

```bash
for t in esbtp-abidjan esbtp-yakro presentation ephrata hetec rostan usat ucao-benin; do
  curl -s -X POST "https://www.klassci.com/api/rendez-vous/$t/creneaux"; echo; done
```

Cette route est celle du site vitrine : elle signe la requête et la transmet à
`POST /api/public/rendez-vous/creneaux` de l'instance. Mesuré le 22/09/2026 :

| instance | réponse | lecture |
|---|---|---|
| esbtp-abidjan | 200, **301 créneaux, 44 complets** | canal ouvert, réservations présentes |
| presentation | 200, 98 créneaux, 0 complet | canal ouvert, aucune réservation |
| esbtp-yakro | 503 « pas ouverte » | `inscriptions.rdv.enabled` à 0 |
| ephrata, rostan, usat | 503 « pas ouverte » | idem |
| hetec, ucao-benin | 404 `etablissement_inconnu` | non déclarées côté vitrine |

**Sur esbtp-abidjan, des rendez-vous existent.** Les 28, 29 et 30 septembre sont
complets, le 1ᵉʳ octobre à moitié, plus rien après. Une famille qui choisit son
créneau ne remplit pas un calendrier dans l'ordre : c'est la signature du bouton
« Placer et convoquer » (`AffecteurDossiersRdv::prochainCreneau()` prend le premier
créneau libre, puis le suivant). Le placement en masse a donc tourné, et créé
plusieurs dizaines à plusieurs centaines de réservations (44 créneaux complets ×
la capacité réglée).

**Sur esbtp-yakro, rien ne peut entrer** : le canal est fermé. « Aucun rendez-vous »
y est le comportement attendu — mais l'écran ne le disait nulle part (voir §3).

## 2. Pourquoi les convocations ne partent pas — la vraie coupure

Lu dans le code, puis vérifié dans Laravel 9.52.22 installé :

1. `MessagerieRdv::confirmer()` faisait `EnvoyerConvocationRdvJob::dispatch(...)->afterResponse()`.
   `PendingDispatch::__destruct()` → `Dispatcher::dispatchAfterResponse()` →
   `$container->terminating(fn () => $this->dispatchSync($command))`.
   **La file d'attente n'est jamais utilisée** : chaque convocation s'exécute en
   synchrone, dans le même processus PHP, après l'envoi de la réponse. `$tries = 3`
   et `$backoff = 30` du job sont sans effet.
2. « Placer et convoquer » empile donc **une** convocation par dossier placé, toutes
   dans le même processus, chacune faisant deux appels HTTP à MailPulse (contact puis
   message, 20 s de délai chacun). Sur l'hébergement mutualisé, le processus est tué
   par sa limite d'exécution bien avant la fin d'un lot de plusieurs centaines.
3. `Application::terminate()` boucle sur les rappels **sans `try`**, et le job
   **relançait** toute erreur MailPulse. Un seul refus arrête donc toutes les
   convocations qui suivent dans le lot.
4. `MessagerieRdv::expedierConvocation()` rendait `false` **sans rien journaliser**
   quand MailPulse est désactivé (`MAILPULSE_ENABLED`). Aucune ligne, nulle part.
5. Aucune trace côté KLASSCI : la réservation ne portait ni état d'envoi ni
   identifiant MailPulse. Seul `rdv_invite_at` du porteur était posé, après succès.

## 3. Pourquoi Marcel « ne voit aucun rendez-vous »

L'écran `/esbtp/inscriptions/rendez-vous` **n'affichait aucune réservation**. Il
listait des créneaux, leur capacité et un badge « Ouvert » — jamais qui avait
réservé, ni combien. Sur esbtp-abidjan, les créneaux complets apparaissaient
exactement comme les créneaux vides.

Et il ne disait jamais que le canal était fermé : sur esbtp-yakro, chaque créneau
s'affichait « Ouvert » pendant que le portail répondait 503 aux familles.

Enfin, « Placer et convoquer » comptait un canal fermé comme « sans créneau », et
annonçait « N dossiers placés **et convoqués par mail** » au moment où aucun mail
n'était encore parti.

## 4. WhatsApp

La convocation de rendez-vous **n'a jamais eu de canal WhatsApp** :
`MessagerieRdv` n'appelle que `sendEmailMessage()`. L'ajouter demande un modèle de
message approuvé côté WhatsApp Business et une règle de consentement : c'est une
décision, pas un défaut, et elle n'est pas prise ici.

## 5. Ce que la correction change

- L'état de chaque convocation vit **sur la réservation** : en attente, envoyée
  (avec l'identifiant MailPulse et l'heure), échec (avec la raison), sans e-mail,
  sans objet (créneau passé avant l'envoi).
- **Une seule porte d'envoi**, `FileConvocationsRdv`, derrière un verrou unique
  (`Cache::lock`, pris en charge par le cache fichier de l'hébergement). Le portail,
  l'écran, la tâche planifiée et l'API CLI passent tous par elle : deux envois
  simultanés ne peuvent plus doubler un courriel. `EnvoyerConvocationRdvJob` est
  supprimé — marqué `ShouldQueue`, il n'était jamais mis en file.
- Plus d'envoi en lot dans `terminating`. Le placement pose « en attente » ; l'envoi
  se fait par paquets bornés dans le temps, depuis l'écran (barre de progression) et
  par une tâche planifiée. Une erreur n'arrête plus les suivantes.
- « Déjà convoqué » se lit sur la réservation, plus sur `rdv_invite_at` : un second
  « Placer et convoquer » ne remet plus à zéro les tentatives ni le motif d'échec.
- MailPulse désactivé ou sans clé : l'envoi s'arrête et **le dit** à l'écran.
- L'écran montre l'état de la chaîne, les réservations de chaque créneau et leur
  convocation.
- `php artisan inscriptions:diagnostiquer-rdv` et `GET /api/cli/rendez-vous/diagnostic`
  rendent la même mesure pour une instance.

## 6. Hypothèses non vérifiées

**Le planificateur tourne-t-il sur les instances ?** La relance automatique des
convocations en attente (échec passager, envoi du portail qui a trouvé le verrou
pris) repose sur `schedule:run`. Le seul crontab documenté pour un serveur d'école
est un exemple générique (`docs/deployment/installation/README.md`). **Le crontab
réel des instances LWS n'est attesté nulle part** et n'a pas pu être vérifié d'ici.
S'il ne tourne pas, rien n'est perdu : les convocations restent « en attente », le
bandeau les compte et le bouton « Envoyer » les fait partir — mais personne ne les
relance sans ce geste. À vérifier dans le cPanel de chaque instance (Tâches cron).

**MailPulse est-il coupé par la base ?** L'écran des réglages crée
`mailpulse_enabled` à `0` s'il n'existe pas (`ESBTPSettingsController`), et la ligne
en base prime sur `MAILPULSE_ENABLED` du `.env`. Une école qui a ouvert ses réglages
sans cocher MailPulse a donc les envois coupés — constaté en local, pas mesuré sur
les instances. Le diagnostic le dit en une ligne : `GET /api/cli/rendez-vous/diagnostic`.

## 7. Ce qui reste à mesurer sur esbtp-abidjan

Les réservations créées avant ce suivi n'ont pas d'état de convocation. Une
convocation réussie posait `rdv_invite_at` sur la candidature, donc :

```sql
SELECT COUNT(*) AS reservations,
       SUM(COALESCE(c.rdv_invite_at, d.rdv_invite_at) IS NOT NULL) AS convoquees
  FROM esbtp_rdv_reservations r
  LEFT JOIN esbtp_candidatures c ON c.id = r.candidature_id
  LEFT JOIN esbtp_reinscription_demandes d ON d.id = r.reinscription_demande_id
 WHERE r.statut IN ('confirmee','honoree','manquee');
```

La migration reporte cet état : `rdv_invite_at` posé → « envoyée » ; sinon l'état
reste **inconnu**, et l'écran propose de leur envoyer la convocation. **L'envoi n'est
pas automatique** : plusieurs centaines de courriels partiraient d'un coup au
déploiement, et c'est à l'école de le décider.
