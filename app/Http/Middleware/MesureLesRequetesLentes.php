<?php

namespace App\Http\Middleware;

use App\Domain\Exploitation\TracesLentes\EnregistreurDeTraces;
use App\Domain\Exploitation\TracesLentes\Mesure;
use App\Domain\Exploitation\TracesLentes\MesuresEnCours;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mesure chaque requête ; n'écrit une trace qu'au-dessus du seuil de l'école,
 * et seulement après l'envoi de la réponse (terminate). Les traces des mesures
 * imbriquées (PDF, envois) sont retenues pendant la requête et écrites ici avec
 * la sienne : l'utilisateur n'attend jamais l'écriture.
 */
class MesureLesRequetesLentes
{
    private const ATTRIBUT = 'traces_lentes.mesure';

    /**
     * La lecture des traces par la console ne se trace pas elle-même : appelée
     * chaque heure, elle deviendrait « l'action lente habituelle » de l'école.
     */
    private const ROUTES_IGNOREES = ['api.cli.traces.lentes'];

    public function __construct(
        private readonly MesuresEnCours $mesures,
        private readonly EnregistreurDeTraces $enregistreur,
    ) {}

    public function handle(Request $request, Closure $next)
    {
        if (EnregistreurDeTraces::actif()) {
            $request->attributes->set(self::ATTRIBUT, $this->mesures->ouvrir());
            $this->enregistreur->retenir();
        }

        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        $mesure = $request->attributes->get(self::ATTRIBUT);
        if (! $mesure instanceof Mesure) {
            return;
        }

        $this->mesures->fermer($mesure);
        $request->attributes->remove(self::ATTRIBUT);

        $route = $request->route();
        if (in_array($route?->getName(), self::ROUTES_IGNOREES, true)) {
            $this->enregistreur->vider();

            return;
        }
        $nom = $route?->getName() ?: ($route ? $request->method().' '.$route->uri() : $request->method().' (sans route)');
        $statut = $response->getStatusCode();

        $this->enregistreur->consigner(
            EnregistreurDeTraces::REQUETE,
            $nom,
            $mesure,
            $statut,
            fn () => [
                'methode' => $request->method(),
                'route' => $route?->uri(),
                'role' => $request->user()?->roles?->pluck('name')->first(),
                'request_id' => $request->attributes->get('request_id'),
            ],
            $statut >= 500,
        );
        $this->enregistreur->vider();
    }
}
