<?php

namespace App\Http\Controllers\ESBTP;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ServiceTechniqueBulletinStyleController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:serviceTechnique');
    }

    /**
     * Les gabarits, dans l'ordre d'affichage : seule source pour la validation,
     * les messages et l'écran.
     *
     * Les images sont des rendus figés de données de démonstration : les
     * régénérer quand un gabarit PDF change de mise en page.
     */
    public const STYLES = [
        'yakro' => [
            'nom' => 'Modèle Yakro',
            'image' => 'images/service-technique/gabarit-bulletin-yakro.webp',
            'points' => [
                'En-tête officiel à gauche, logo au centre, titre à droite.',
                'Décision du conseil et signature sous les statistiques, pour tenir sur une page.',
                'Gabarit des instances ESBTP Yamoussoukro.',
            ],
        ],
        'abidjan' => [
            'nom' => 'Modèle Abidjan / Plateau',
            'image' => 'images/service-technique/gabarit-bulletin-abidjan.webp',
            'points' => [
                'Ministère sur toute la largeur, logo encadré à gauche.',
                'Bloc « Conseil de classe » au-dessus de la signature du directeur.',
                'Le titre du conseil en 1re année BTS, semestre 1, se règle à part.',
            ],
        ],
    ];

    public function index()
    {
        $setting = $this->ensureBulletinStyleSetting();

        return view('esbtp.service-technique.bulletin-style', [
            'currentStyle' => Setting::get('bulletin_style', 'yakro'),
            'styles' => self::STYLES,
            'modifieLe' => $setting->updated_at,
            'modifiePar' => optional($setting->updater)->name,
        ]);
    }

    public function update(Request $request): JsonResponse|RedirectResponse
    {
        $this->ensureBulletinStyleSetting();

        $validated = $request->validate([
            'bulletin_style' => 'required|in:'.implode(',', array_keys(self::STYLES)),
        ]);

        // Setting::set() rend false sans lever quand l'ecriture echoue :
        // annoncer un succes dans ce cas laisserait l'ancien gabarit en place.
        if (! Setting::set('bulletin_style', $validated['bulletin_style'], auth()->id())) {
            $message = 'Le style n\'a pas pu être enregistré. Réessayez.';

            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $message], 500)
                : back()->with('error', $message);
        }

        $message = self::STYLES[$validated['bulletin_style']]['nom'].' appliqué aux bulletins et aux aperçus.';

        return $request->expectsJson()
            ? response()->json([
                'success' => true,
                'message' => $message,
                'style' => $validated['bulletin_style'],
                'modifie' => now()->format('d/m/Y à H:i').' · '.auth()->user()->name,
            ])
            : back()->with('success', $message);
    }

    private function ensureBulletinStyleSetting(): Setting
    {
        return Setting::firstOrCreate(
            ['key' => 'bulletin_style'],
            [
                'value' => 'yakro',
                'type' => 'string',
                'group' => 'bulletin',
                'category' => 'bulletin',
                'description' => 'Style de bulletin PDF',
                'is_required' => false,
                'default_value' => 'yakro',
                'validation_rules' => ['required', 'in:'.implode(',', array_keys(self::STYLES))],
                'sort_order' => 20,
                'is_active' => true,
            ]
        );
    }
}
