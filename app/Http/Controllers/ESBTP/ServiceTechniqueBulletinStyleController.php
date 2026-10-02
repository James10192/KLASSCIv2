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

    /** Les deux gabarits, dans l'ordre d'affichage. */
    public const STYLES = ['yakro', 'abidjan'];

    public function index()
    {
        $setting = $this->ensureBulletinStyleSetting();

        return view('esbtp.service-technique.bulletin-style', [
            'currentStyle' => Setting::get('bulletin_style', 'yakro'),
            'modifieLe' => $setting->updated_at,
            'modifiePar' => optional($setting->updater)->name,
        ]);
    }

    public function update(Request $request): JsonResponse|RedirectResponse
    {
        $this->ensureBulletinStyleSetting();

        $validated = $request->validate([
            'bulletin_style' => 'required|in:'.implode(',', self::STYLES),
        ]);

        // Setting::set() rend false sans lever quand l'ecriture echoue :
        // annoncer un succes dans ce cas laisserait l'ancien gabarit en place.
        if (! Setting::set('bulletin_style', $validated['bulletin_style'], auth()->id())) {
            $message = 'Le style n\'a pas pu être enregistré. Réessayez.';

            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $message], 500)
                : back()->with('error', $message);
        }

        $message = $validated['bulletin_style'] === 'abidjan'
            ? 'Gabarit Abidjan / Plateau appliqué aux bulletins et aux aperçus.'
            : 'Gabarit Yakro appliqué aux bulletins et aux aperçus.';

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
                'validation_rules' => ['required', 'in:yakro,abidjan'],
                'sort_order' => 20,
                'is_active' => true,
            ]
        );
    }
}
