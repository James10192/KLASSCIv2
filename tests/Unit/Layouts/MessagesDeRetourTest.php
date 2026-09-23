<?php

namespace Tests\Unit\Layouts;

use App\Support\MessagesDeRetour;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * La mise en page affiche les messages de retour — sauf ceux que la page
 * MONTRE déjà. Environ 126 vues les rendaient une seconde fois. Dans le doute,
 * le message reste doublé : le cacher à tort le ferait disparaître.
 */
class MessagesDeRetourTest extends TestCase
{
    private const MESSAGE = "Le devoir « Contrôle » a le statut « Terminée » : annulez-le d'abord.";

    private function rendre(string $contenuDeLaPage): string
    {
        return view('partials._messages_de_retour', ['contenuDeLaPage' => $contenuDeLaPage])->render();
    }

    private function alerte(string $message, string $attributs = 'class="alert alert-danger"'): string
    {
        return '<div '.$attributs.'><div class="d-flex"><i class="fas fa-circle"></i><div>'.e($message).'</div></div></div>';
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
        session()->flash('error', self::MESSAGE);

        $this->assertStringNotContainsString('alert-danger', $this->rendre($this->alerte(self::MESSAGE)));
    }

    public function test_les_alertes_maison_comptent_aussi(): void
    {
        $this->assertTrue(MessagesDeRetour::dejaAffichePar($this->alerte(self::MESSAGE, 'class="re-alert re-alert--success"'), self::MESSAGE));
    }

    public function test_chaque_type_est_juge_a_part(): void
    {
        session()->flash('success', 'Séance supprimée avec succès.');
        session()->flash('warning', 'Une moyenne est laissée.');

        $html = $this->rendre($this->alerte('Séance supprimée avec succès.', 'class="alert alert-success"'));

        $this->assertStringNotContainsString('alert-success', $html);
        $this->assertStringContainsString('alert-warning', $html);
    }

    public function test_un_ecran_a_version_bureau_masquee_sur_mobile_garde_le_message(): void
    {
        // La copie de la page n'existe que dans la version bureau, masquée sur téléphone.
        $page = '<div class="m-only-desktop">'.$this->alerte(self::MESSAGE).'</div><div class="m-only-mobile">…</div>';

        $this->assertFalse(MessagesDeRetour::dejaAffichePar($page, self::MESSAGE));
    }

    public function test_le_texte_d_un_script_ne_compte_pas(): void
    {
        // Gabarit JS d'un repli d'erreur : du HTML d'alerte, mais dans une chaîne.
        $page = "<script>const repli = '<div class=\"alert alert-danger\">".e('Une erreur est survenue.')."</div>';</script>";

        $this->assertFalse(MessagesDeRetour::dejaAffichePar($page, 'Une erreur est survenue.'));
    }

    public function test_une_alerte_cachee_ne_compte_pas(): void
    {
        foreach (['class="alert d-none"', 'class="alert" style="display: none"', 'class="alert" x-show="erreur"', 'class="alert" x-cloak', 'class="alert" hidden'] as $attributs) {
            $this->assertFalse(MessagesDeRetour::dejaAffichePar($this->alerte('Une erreur est survenue.', $attributs), 'Une erreur est survenue.'), $attributs);
        }
    }

    public function test_le_meme_texte_hors_d_une_alerte_ne_compte_pas(): void
    {
        $page = '<div class="modal"><span class="text-muted">'.e('Une erreur est survenue.').'</span></div>';

        $this->assertFalse(MessagesDeRetour::dejaAffichePar($page, 'Une erreur est survenue.'));
    }

    public function test_le_message_reste_echappe(): void
    {
        session()->flash('info', '<script>alert(1)</script>');

        $html = $this->rendre('');

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function test_la_vraie_mise_en_page_passe_le_contenu_de_la_page(): void
    {
        $miseEnPage = file_get_contents(resource_path('views/layouts/app.blade.php'));
        $include = "@include('partials._messages_de_retour', ['contenuDeLaPage' => \$__env->yieldContent('content')])";

        $this->assertStringContainsString($include, $miseEnPage);
        $this->assertLessThan(strpos($miseEnPage, "@yield('content')"), strpos($miseEnPage, $include));
    }

    public function test_la_mise_en_page_recoit_le_contenu_deja_rendu_de_la_page(): void
    {
        // Même geste que layouts/app.blade.php : @include avant @yield('content').
        $dossier = storage_path('framework/testing/vues-messages');
        File::ensureDirectoryExists($dossier);
        File::put($dossier.'/mise-en-page.blade.php', "<header>@include('partials._messages_de_retour', ['contenuDeLaPage' => \$__env->yieldContent('content')])</header><main>@yield('content')</main>");
        File::put($dossier.'/page-avec-alerte.blade.php', "@extends('vues-messages::mise-en-page')\n@section('content')<div class=\"alert alert-success\">{{ session('success') }}</div>@endsection");
        File::put($dossier.'/page-sans-alerte.blade.php', "@extends('vues-messages::mise-en-page')\n@section('content')<p>Grille</p>@endsection");
        app('view')->addNamespace('vues-messages', $dossier);

        try {
            session()->flash('success', 'Séance supprimée avec succès.');

            $avec = view('vues-messages::page-avec-alerte')->render();
            $this->assertSame(1, substr_count($avec, 'Séance supprimée avec succès.'), 'la page le montre : une seule fois');

            $sans = view('vues-messages::page-sans-alerte')->render();
            $this->assertSame(1, substr_count($sans, 'Séance supprimée avec succès.'), 'la page ne le montre pas : la mise en page le montre');
        } finally {
            File::deleteDirectory($dossier);
        }
    }
}
