<?php

namespace Tests\Feature\LMD;

use Tests\TestCase;

class LmdNotesOfflineRecoveryTest extends TestCase
{
    private function viewContent(): string
    {
        return file_get_contents(resource_path('views/esbtp/lmd/notes/index.blade.php'));
    }

    public function test_notes_view_persists_offline_queue_in_local_storage(): void
    {
        $content = $this->viewContent();

        $this->assertStringContainsString('klassci:lmd-notes:offline-queue:v1', $content);
        $this->assertStringContainsString('localStorage.setItem(lmdNotesQueueKey', $content);
        $this->assertStringContainsString('loadOfflineNoteQueue', $content);
        $this->assertStringContainsString('persistOfflineNoteQueue', $content);
    }

    public function test_notes_view_replays_queue_after_network_recovery(): void
    {
        $content = $this->viewContent();

        $this->assertStringContainsString("window.addEventListener('online', replayOfflineNoteQueue)", $content);
        $this->assertStringContainsString('async function replayOfflineNoteQueue()', $content);
        $this->assertStringContainsString('sendNoteMutation(item.payload)', $content);
        $this->assertStringContainsString('removeOfflineNote(item.key)', $content);
    }

    public function test_notes_view_keeps_draft_when_save_fails(): void
    {
        $content = $this->viewContent();

        $this->assertStringContainsString('if (!navigator.onLine)', $content);
        $this->assertStringContainsString('queueOfflineNote(payload, input', $content);
        $this->assertStringContainsString('queueOfflineNote(note, findNoteInput(note)', $content);
        $this->assertStringContainsString('Certaines notes restent', $content);
    }

    public function test_notes_view_marks_pending_inputs_after_grid_rebuild(): void
    {
        $content = $this->viewContent();

        $this->assertStringContainsString('markQueuedNoteInputs();', $content);
        $this->assertStringContainsString("input.classList.add(item.error ? 'ln-error' : 'ln-pending')", $content);
        $this->assertStringContainsString('offlineQueueCount', $content);
    }
}
