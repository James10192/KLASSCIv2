<?php
namespace Tests\Unit\Services\Care;

use App\Services\Care\TelemetrieIncidents;
use App\Support\Incidents\MessageErreurAnnonce;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use PDOException;
use Tests\TestCase;

class TelemetrieIncidentsTest extends TestCase
{
    public function test_erreur_sql_reduite_a_une_reference_sans_contenu_ni_requete(): void
    {
        $request = Request::create('/esbtp/annonces', 'POST', [
            'titre' => 'Communique confidentiel',
            'contenu' => 'Texte personnel etudiant',
        ]);
        $route = new Route('POST', 'esbtp/annonces', fn () => null);
        $route->name('esbtp.annonces.store');
        $request->setRouteResolver(fn () => $route);
        $request->attributes->set('request_id', '01M63RF829V0XZBPSEV2ZQZ2QZ');

        $pdo = new PDOException('SQLSTATE[42S22]: INSERT INTO secrets...');
        $pdo->errorInfo = ['42S22', 1054, 'Unknown column email_notification_enabled'];
        $exception = new QueryException('mysql', 'INSERT INTO esbtp_annonces (contenu)', ['Texte personnel etudiant'], $pdo);
        $result = (new TelemetrieIncidents())->preparer($exception, $request);
        $this->assertSame('01M63RF829V0XZBPSEV2ZQZ2QZ', $result['request_id']);
        $this->assertSame('esbtp.annonces.store', $result['route_name']);
        $this->assertSame('42S22', $result['sqlstate']);
        $this->assertSame(1054, $result['db_errno']);
        $json = json_encode($result);
        $this->assertStringNotContainsString('INSERT INTO', $json);
        $this->assertStringNotContainsString('Texte personnel', $json);
        $this->assertStringNotContainsString('Communique confidentiel', $json);
    }

    public function test_le_message_affiche_ne_revele_jamais_le_sql(): void
    {
        $message = MessageErreurAnnonce::pour('create', '01M63RF829V0XZBPSEV2ZQZ2QZ');
        $this->assertStringContainsString('Code de suivi', $message);
        $this->assertStringNotContainsString('SQLSTATE', $message);
        $this->assertStringNotContainsString('INSERT', $message);
    }

    public function test_les_erreurs_fonctionnelles_ne_remontent_pas(): void
    {
        $request = Request::create('/esbtp/annonces', 'POST');
        $exception = new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException('Non trouvé');
        $this->assertNull((new TelemetrieIncidents())->preparer($exception, $request));
    }
}
