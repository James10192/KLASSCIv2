<?php

namespace App\Services\ParentChatbot;

use App\Enums\ParentChatbotIntent;
use App\Models\ESBTPBulletin;
use App\Models\ESBTPNote;
use App\Models\ESBTPEtudiant;
use App\Models\ParentChatbotLink;
use App\Models\ParentChatbotInboundEvent;

class ParentChatbotResponder
{
    public function __construct(
        private ParentChatbotLinkService $links,
        private ParentChatbotPhoneNormalizer $phones,
        private ParentChatbotDispatcher $dispatcher,
        private ParentChatbotPublicationPolicy $publicationPolicy,
    ) {
    }

    public function isLierCommand(string $message): bool
    {
        return preg_match('/^LIER\s+([A-F0-9]{16})$/', $this->command($message)) === 1;
    }

    /** @return array{phone: string, intent: string, outcome: string, reply: string, should_dispatch: bool, disclosure: ?array, authorization_claim: ?array} */
    public function prepareInboundResponse(
        ParentChatbotInboundEvent $event,
        string $token,
        string $phone,
        string $message,
    ): array {
        if ($event->hasRecordedResponse()) {
            return $event->recordedResponse() + ['should_dispatch' => true];
        }

        if (! $this->isLierCommand($message)) {
            return $this->prepareResponse($phone, $message);
        }

        preg_match('/^LIER\s+([A-F0-9]{16})$/', $this->command($message), $matches);
        $response = $this->lierResponse($this->links->link($phone, $matches[1]));
        $normalizedPhone = $this->phones->normalize($phone);

        return [
            'phone' => $normalizedPhone ?? $phone,
            'intent' => $response['intent'],
            'outcome' => $response['outcome'],
            'reply' => $response['reply'],
            'should_dispatch' => $normalizedPhone !== null,
            'disclosure' => null,
            'authorization_claim' => $response['outcome'] === 'linked'
                ? $this->links->dispatchAuthorizationClaim($response['link'])
                : null,
        ];
    }

    public function dispatchRecordedResponse(ParentChatbotInboundEvent $event, string $token): string
    {
        $response = $event->fresh()->recordedResponse();
        if (($response['should_dispatch'] ?? true) === false) {
            return $response['outcome'];
        }

        $intent = ParentChatbotIntent::from($response['intent']);
        $authorizationGate = $this->links->submitIfStillDispatchAuthorized(
            is_array($response['authorization_claim']) ? $response['authorization_claim'] : null,
            $response['phone'],
            $intent === ParentChatbotIntent::Stop,
            fn (): array => $this->publicationPolicy->submitIfStillPublishable(
                is_array($response['disclosure']) ? $response['disclosure'] : null,
                function () use ($response, $event, $intent): void {
                    $this->dispatch(
                        $response['phone'],
                        $response['reply'],
                        $intent,
                        $event->source_event_id,
                        $response['idempotency_key'] ?? null,
                    );
                },
            ),
        );

        if (! $authorizationGate['authorization_eligible']) {
            if (! $event->discardRecordedResponse($token)) {
                throw new \RuntimeException('The unauthorized parent chatbot response could not be discarded.');
            }

            return 'link_not_authorized';
        }

        /** @var array{publication_eligible: bool, result: mixed} $gate */
        $gate = $authorizationGate['result'];

        if (! $gate['publication_eligible']) {
            if (! $event->discardRecordedResponse($token)) {
                throw new \RuntimeException('The unpublished parent chatbot response could not be discarded.');
            }

            return 'academic_content_unpublished';
        }

        return $response['outcome'];
    }

    /** @return array{phone: string, intent: string, outcome: string, reply: string, should_dispatch: bool, disclosure: ?array, authorization_claim: ?array} */
    private function prepareResponse(string $phone, string $message): array
    {
        $normalizedPhone = $this->phones->normalize($phone);
        if ($normalizedPhone === null) {
            return [
                'phone' => '',
                'intent' => ParentChatbotIntent::SchoolAdminFirst->value,
                'outcome' => 'invalid_phone',
                'reply' => '',
                'should_dispatch' => false,
                'disclosure' => null,
                'authorization_claim' => null,
            ];
        }

        $command = $this->command($message);
        $links = ParentChatbotLink::query()
            ->where('phone_hash', $this->phones->hash($normalizedPhone))
            ->get();
        if ($links->isEmpty()) {
            return $this->prepared($normalizedPhone, ParentChatbotIntent::Unlinked, 'unlinked', 'Pour lier ce numero, envoyez LIER suivi du code transmis par votre ecole.');
        }

        if ($links->count() !== 1) {
            return $this->prepared($normalizedPhone, ParentChatbotIntent::SchoolAdminFirst, 'ambiguous_link', 'Ce numero correspond a plusieurs dossiers. Contactez d\'abord l\'administration de votre ecole.');
        }

        $link = $links->sole();
        if (! $this->links->isAuthorized($link, $normalizedPhone)) {
            return $this->prepared($normalizedPhone, ParentChatbotIntent::Revoked, ParentChatbotIntent::Revoked->value, 'Ce lien n\'est plus autorise. Contactez d\'abord l\'administration de votre ecole.');
        }

        if ($command === 'STOP') {
            if (! $this->links->stop($link)) {
                return $this->prepared($normalizedPhone, ParentChatbotIntent::Revoked, ParentChatbotIntent::Revoked->value, 'Ce lien a ete revoque. Contactez d\'abord l\'administration de votre ecole.');
            }

            return $this->prepared($normalizedPhone, ParentChatbotIntent::Stop, ParentChatbotIntent::Stopped->value, 'Les messages KLASSCI sont arretes pour ce numero. Envoyez START pour les reprendre.');
        }

        if ($command === 'START') {
            $started = $this->links->start($link);

            return $this->prepared(
                $normalizedPhone,
                $started ? ParentChatbotIntent::Start : ParentChatbotIntent::Revoked,
                $started ? ParentChatbotIntent::Start->value : ParentChatbotIntent::Revoked->value,
                $started
                    ? 'Les messages KLASSCI sont reactives. Demandez NOTES, ABSENCES, ASSIDUITE ou BULLETIN.'
                    : 'Ce lien a ete revoque. Contactez d\'abord l\'administration de votre ecole.',
                null,
                $started ? $this->links->dispatchAuthorizationClaim($link->fresh() ?? $link) : null,
            );
        }

        if (! $link->isActive()) {
            return $this->prepared($normalizedPhone, ParentChatbotIntent::Stopped, ParentChatbotIntent::Stopped->value, 'Les messages sont arretes. Envoyez START pour les reprendre.');
        }

        $link->update(['last_inbound_at' => now()]);
        [$intent, $reply, $disclosure] = $this->replyFor($link, $command);

        return $this->prepared(
            $normalizedPhone,
            $intent,
            $intent->value,
            $reply,
            $disclosure,
            $this->links->dispatchAuthorizationClaim($link->fresh() ?? $link),
        );
    }

    /** @return array{phone: string, intent: string, outcome: string, reply: string, should_dispatch: bool, disclosure: ?array, authorization_claim: ?array} */
    private function prepared(
        string $phone,
        ParentChatbotIntent $intent,
        string $outcome,
        string $reply,
        ?array $disclosure = null,
        ?array $authorizationClaim = null,
    ): array
    {
        return [
            'phone' => $phone,
            'intent' => $intent->value,
            'outcome' => $outcome,
            'reply' => $reply,
            'should_dispatch' => true,
            'disclosure' => $disclosure,
            'authorization_claim' => $authorizationClaim,
        ];
    }

    /** @param array{link: ?ParentChatbotLink, reason: string} $result
     * @return array{intent: string, outcome: string, reply: string, link: ?ParentChatbotLink} */
    private function lierResponse(array $result): array
    {
        $reply = match ($result['reason']) {
            'linked' => $result['link']->selected_student_id
                ? 'Votre numero est lie. Vous pouvez demander NOTES, ABSENCES, ASSIDUITE ou BULLETIN.'
                : $this->childSelectionReply($result['link']),
            'revoked' => 'Ce lien a ete revoque. Contactez d\'abord l\'administration de votre ecole.',
            default => 'Le code est invalide ou ce numero n\'est pas le telephone tuteur enregistre. Contactez d\'abord l\'administration de votre ecole.',
        };

        return [
            'intent' => ParentChatbotIntent::Link->value,
            'outcome' => $result['reason'],
            'reply' => $reply,
            'link' => $result['link'],
        ];
    }

    /** @return array{ParentChatbotIntent, string, ?array} */
    private function replyFor(ParentChatbotLink $link, string $command): array
    {
        if ($command === 'AIDE') {
            return [ParentChatbotIntent::Help, 'Commandes disponibles : NOTES, ABSENCES, ASSIDUITE, BULLETIN, ENFANT <numéro>, ENFANTS <page>, STOP et START.', null];
        }

        if (preg_match('/^ENFANT\s+(\d+)$/', $command, $matches)) {
            $student = $link->parent->pupilles()->where('esbtp_etudiants.id', (int) $matches[1])->first();
            if (! $student) {
                return [ParentChatbotIntent::ChildSelection, $this->childSelectionReply($link), null];
            }
            $link->update(['selected_student_id' => $student->id]);
            return [ParentChatbotIntent::ChildSelection, 'Enfant sélectionné : ' . $student->nom_complet . '.', null];
        }

        if (preg_match('/^ENFANTS\s+(\d+)$/', $command, $matches)) {
            return [ParentChatbotIntent::ChildSelection, $this->childSelectionReply($link, (int) $matches[1]), null];
        }

        $student = $this->selectedStudent($link);
        if (! $student) {
            return [ParentChatbotIntent::ChildSelection, $this->childSelectionReply($link), null];
        }

        return match ($command) {
            'NOTES' => [ParentChatbotIntent::PublishedGrades, ...$this->publishedGradesReply($student)],
            'ABSENCES' => [ParentChatbotIntent::Absences, ...$this->absencesReply($student)],
            'ASSIDUITE' => [ParentChatbotIntent::AttendanceRate, ...$this->attendanceReply($student)],
            'BULLETIN' => [ParentChatbotIntent::PublishedReportCard, ...$this->reportCardReply($student)],
            default => [ParentChatbotIntent::SchoolAdminFirst, 'Pour cette demande, contactez d\'abord l\'administration de votre école.', null],
        };
    }

    private function selectedStudent(ParentChatbotLink $link): ?ESBTPEtudiant
    {
        if ($link->selected_student_id) {
            return $link->parent->pupilles()->where('esbtp_etudiants.id', $link->selected_student_id)->first();
        }

        return null;
    }

    private function childSelectionReply(ParentChatbotLink $link, int $page = 1): string
    {
        $perPage = 10;
        $total = $link->parent->pupilles()->count();
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $lastPage);
        $children = $link->parent->pupilles()
            ->select(['esbtp_etudiants.id', 'esbtp_etudiants.nom', 'esbtp_etudiants.prenoms'])
            ->orderBy('esbtp_etudiants.id')
            ->forPage($page, $perPage)
            ->get();
        if ($children->isEmpty()) {
            return 'Aucun enfant tuteur n\'est disponible. Contactez d\'abord l\'administration de votre école.';
        }

        $choices = $children->map(fn (ESBTPEtudiant $student) => $student->id . ' : ' . $student->nom_complet)->implode("\n");
        $pagination = $lastPage > 1 ? "\nPage $page/$lastPage. Envoyez ENFANTS <page> pour afficher une autre page." : '';
        return "Choisissez un enfant en envoyant ENFANT <numéro> :\n" . $choices . $pagination;
    }

    /** @return array{string, ?array} */
    private function publishedGradesReply(ESBTPEtudiant $student): array
    {
        $notes = $this->publicationPolicy->publishedGradesForStudent($student->id)
            ->with(['evaluation:id,titre,bareme', 'matiere:id,name,nom'])
            ->latest('id')
            ->limit(5)
            ->get();

        if ($notes->isEmpty()) {
            return ['Aucune note publiée n\'est disponible pour ' . $student->nom_complet . '.', null];
        }

        $lines = $notes->map(function (ESBTPNote $note): string {
            $label = $note->matiere->nom ?? $note->matiere->name ?? $note->evaluation->titre ?? 'Evaluation';
            return $label . ' : ' . number_format((float) $note->note_vingt, 2, ',', ' ') . '/20';
        })->implode("\n");

        return [
            'Notes publiées de ' . $student->nom_complet . " :\n" . $lines,
            ['type' => 'grades', 'student_id' => $student->id, 'resource_ids' => $notes->modelKeys()],
        ];
    }

    /** @return array{string, ?array} */
    private function absencesReply(ESBTPEtudiant $student): array
    {
        $summary = $this->publicationPolicy->publishedReportCardsForStudent($student->id)
            ->latest('id')
            ->first();

        if (! $summary) {
            return ['Aucun récapitulatif d\'absences publié n\'est disponible pour ' . $student->nom_complet . '.', null];
        }

        return [sprintf(
            'Absences publiées pour %s : %s au total, dont %s justifiée(s) et %s non justifiée(s).',
            $student->nom_complet,
            number_format($this->bulletinNumber($summary, 'total_absences'), 2, ',', ' '),
            number_format($this->bulletinNumber($summary, 'absences_justifiees'), 2, ',', ' '),
            number_format($this->bulletinNumber($summary, 'absences_non_justifiees'), 2, ',', ' '),
        ), ['type' => 'report_card', 'student_id' => $student->id, 'resource_ids' => [$summary->id]]];
    }

    /** @return array{string, ?array} */
    private function attendanceReply(ESBTPEtudiant $student): array
    {
        $bulletin = $this->publicationPolicy->publishedReportCardsForStudent($student->id)
            ->whereNotNull('note_assiduite')
            ->latest('id')
            ->first();

        if (! $bulletin) {
            return ['Aucune assiduité publiée n\'est disponible pour ' . $student->nom_complet . '.', null];
        }

        return [
            'Assiduité publiée de ' . $student->nom_complet . ' : note d\'assiduité '
                . $this->formatNumber($this->bulletinNumber($bulletin, 'note_assiduite')) . '/20.',
            ['type' => 'report_card', 'student_id' => $student->id, 'resource_ids' => [$bulletin->id]],
        ];
    }

    /** @return array{string, ?array} */
    private function reportCardReply(ESBTPEtudiant $student): array
    {
        $bulletin = $this->publicationPolicy->publishedReportCardsForStudent($student->id)
            ->latest('id')
            ->first();

        if (! $bulletin) {
            return ['Aucun bulletin publié n\'est disponible pour ' . $student->nom_complet . '.', null];
        }

        $average = $bulletin->moyenne_generale !== null
            ? $this->formatNumber((float) $bulletin->moyenne_generale) . '/20'
            : 'non renseignee';
        $rank = $bulletin->rang && $bulletin->effectif_classe
            ? $bulletin->rang . '/' . $bulletin->effectif_classe
            : 'non renseigne';
        $decision = $bulletin->decision_conseil ?: 'non renseignee';

        return [sprintf(
            "Bulletin publie de %s : moyenne %s, rang %s, decision %s. Le detail officiel reste disponible depuis le compte KLASSCI de l'enfant.",
            $student->nom_complet,
            $average,
            $rank,
            $decision
        ), ['type' => 'report_card', 'student_id' => $student->id, 'resource_ids' => [$bulletin->id]]];
    }

    private function command(string $message): string
    {
        $message = preg_replace('/\s+/', ' ', trim($message));
        return strtoupper((string) $message);
    }

    private function formatNumber(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, ',', ' '), '0'), ',');
    }

    private function bulletinNumber(ESBTPBulletin $bulletin, string $field): float
    {
        $value = $bulletin->getRawOriginal($field);

        return $value === null ? 0.0 : (float) $value;
    }

    private function dispatch(
        string $phone,
        string $reply,
        ParentChatbotIntent $intent,
        string $eventId,
        ?string $idempotencyKey = null,
    ): void
    {
        $outcome = $this->dispatcher->dispatch(
            $phone,
            $reply,
            $intent,
            $eventId,
            $idempotencyKey ?? 'klassci-parent-inbound-'.$eventId,
        );
        if ($outcome->isPendingReconciliation()) {
            throw new \RuntimeException('Parent chatbot response requires MailPulse reconciliation.');
        }
        if (! $outcome->isAccepted()) {
            throw new \RuntimeException('Parent chatbot response was not accepted by MailPulse.');
        }
    }
}
