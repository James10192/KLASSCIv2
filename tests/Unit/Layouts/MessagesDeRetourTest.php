<?php

namespace Tests\Unit\Layouts;

use Tests\TestCase;

/**
 * La mise en page affiche les messages de retour — sauf ceux que la page
 * affiche déjà elle-même. 126 vues les rendaient une seconde fois, et
 * l'utilisateur lisait chaque message deux fois.
 */
class MessagesDeRetourTest extends TestCase
{
    private function rendre(string $contenuDeLaPage): string
    {
        return view('partials._messages_de_retour', ['contenuDeLaPage' => $contenuDeLaPage])->render();
    }

    public function test_un_message_que_la_page_n_affiche_pas_est_affiche(): void
    {
        session()->flash('warning', 'Une moyenne est laissée.');

        $html = $this->rendre('<div>Grille</div>');

        $this->assertStringContainsString('alert-warning', $html);
        $this->assertStringContainsString('Une moyenne est laissée.', $html);
    }

    public function test_un_message_deja_affiche_par_la_page_n_est_pas_repete(): void
    {
        session()->flash('error', "Le devoir « Contrôle » a le statut « Terminée » : annulez-le d'abord.");
        // La page l'affiche échappée, comme le fait {{ }} dans sa propre alerte.
        $page = '<div class="alert">'.e(session('error')).'</div>';

        $this->assertStringNotContainsString('alert-danger', $this->rendre($page));
    }

    public function test_chaque_type_est_juge_a_part(): void
    {
        session()->flash('success', 'Séance supprimée avec succès.');
        session()->flash('warning', 'Une moyenne est laissée.');

        $html = $this->rendre('<p>'.e('Séance supprimée avec succès.').'</p>');

        $this->assertStringNotContainsString('alert-success', $html);
        $this->assertStringContainsString('alert-warning', $html);
    }

    public function test_le_message_reste_echappe(): void
    {
        session()->flash('info', '<script>alert(1)</script>');

        $html = $this->rendre('');

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }
}
