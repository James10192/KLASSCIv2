<?php

namespace App\Http\Controllers\ESBTP;

use App\Exceptions\AvoirForbiddenException;
use App\Helpers\SettingsHelper;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\RespondsWithInlinePdf;
use App\Http\Requests\Paiement\StoreAvoirRequest;
use App\Models\ESBTPPaiement;
use App\Services\AvoirService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class AvoirPaiementController extends Controller
{
    use RespondsWithInlinePdf;

    public function store(StoreAvoirRequest $request, ESBTPPaiement $paiement, AvoirService $avoires)
    {
        try {
            $avoir = $avoires->issue(
                $paiement,
                (float) $request->input('montant'),
                (string) $request->input('avoir_kind'),
                (string) $request->input('motif'),
                (int) $request->user()->id,
            );
        } catch (AvoirForbiddenException $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 403);
            }

            return redirect()->back()->with('error', $e->getMessage());
        }

        $message = 'Avoir '.$avoir->numero_avoir.' enregistré.';

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $message, 'avoir_id' => $avoir->id]);
        }

        return redirect()->route('esbtp.paiements.index')->with('success', $message);
    }

    public function pdf(Request $request, ESBTPPaiement $paiement)
    {
        abort_unless($paiement->isAvoir(), 404);

        $paiement->load([
            'etudiant.user',
            'inscription.anneeUniversitaire',
            'inscription.filiere',
            'inscription.niveauEtude',
            'fraisCategory',
            'parentPaiement',
            'creator:id,name',
            'validatedBy',
        ]);

        $settings = $this->receiptSettings();
        $pdf = Pdf::loadView('esbtp.paiements.avoir', compact('paiement', 'settings'))
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'dpi' => 150,
                'defaultFont' => 'DejaVu Sans',
                'isRemoteEnabled' => false,
                'isHtml5ParserEnabled' => true,
                'isPhpEnabled' => false,
                'isFontSubsettingEnabled' => true,
            ]);

        return $this->respondWithPdf($pdf, 'Avoir_'.$paiement->numero_avoir.'.pdf', $request);
    }

    private function receiptSettings(): array
    {
        $settings = [
            'school_name' => SettingsHelper::get('school_name', config('app.name', 'KLASSCI')),
            'school_address' => SettingsHelper::get('school_address', ''),
            'school_phone' => SettingsHelper::get('school_phone', ''),
            'school_email' => SettingsHelper::get('school_email', ''),
            'show_logo' => SettingsHelper::get('receipt_show_logo', '1') === '1',
        ];

        if ($settings['show_logo']) {
            $logoPath = SettingsHelper::get('school_logo');
            if ($logoPath) {
                foreach ([
                    storage_path('app/public/'.$logoPath),
                    public_path($logoPath),
                ] as $path) {
                    if (file_exists($path)) {
                        $ext = pathinfo($path, PATHINFO_EXTENSION);
                        $settings['logo_base64'] = 'data:image/'.$ext.';base64,'.base64_encode(file_get_contents($path));
                        break;
                    }
                }
            }
        }

        return $settings;
    }
}
