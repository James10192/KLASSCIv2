# Annonces KLASSCI — e-mails facultatifs MailPulse

La case « Envoyer aussi par e-mail (MailPulse) » est désactivée par défaut ; elle est conservée sur le brouillon et prise en compte à la première publication uniquement. Les modifications d'une annonce déjà publiée n'envoient pas une nouvelle campagne.

**Ciblage** : générale = tous les étudiants de l'instance non supprimés ; classe = étudiants inscrits dans les classes sélectionnées durant l'année en cours ; nominative = uniquement les étudiants sélectionnés. Un étudiant sans compte peut recevoir un courriel si son adresse est renseignée. Le premier courriel valide est choisi dans ses adresses ou celle de son compte. Deux étudiants partageant une adresse ne génèrent qu'un mail.

**Architecture** : `DiffuserAnnonceParCourriel` crée des livraisons uniques dans `esbtp_annonce_email_deliveries`, puis planifie `EnvoyerAnnonceCourriel` par destinataire. Chaque mail utilise explicitement le transport `mailpulse`. Le corps reprend le titre, le contenu et le lien sécurisé vers le fil étudiant. Les pièces jointes ne sont pas attachées : l'API publique MailPulse ne les prend pas en charge.

**Exploitation** : exécuter la migration par établissement. Exiger `MAILPULSE_API_KEY`, MailPulse activé, une file asynchrone, un worker/cron fonctionnel et suffisamment de crédits e-mail. Le transport traite le débit et les reports temporaires. `submitted_at` signifie accepté ou reporté par le transport, **pas livré à l'inbox**. Suivre les échecs de queue, les logs du transport et les livraisons `pending` / `processing` anciennes ; un crash sur une livraison `processing` demande une reprise supervisée pour éviter les doublons. Ne pas envoyer d'essais réels à des étudiants en CI.
