<?php

namespace App\Http\Controllers;

use App\Models\CertificateType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CertificateTypeController extends Controller
{
    /**
     * Display a listing of the certificate types.
     */
    public function index()
    {
        $certificateTypes = CertificateType::withCount('certificates')->paginate(15);
        
        return view('certificate_types.index', compact('certificateTypes'));
    }

    /**
     * Show the form for creating a new certificate type.
     */
    public function create()
    {
        return view('certificate_types.create');
    }

    /**
     * Store a newly created certificate type in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:certificate_types',
            'description' => 'nullable|string',
            'template' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            CertificateType::create([
                'name' => $request->name,
                'description' => $request->description,
                'template' => $request->template,
            ]);

            DB::commit();

            return redirect()->route('certificate-types.index')
                ->with('success', 'Type de certificat créé avec succès.');
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()
                ->with('error', 'Une erreur est survenue lors de la création du type de certificat: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified certificate type.
     */
    public function show(CertificateType $certificateType)
    {
        $certificateType->loadCount('certificates');
        $certificateType->load(['certificates' => function ($query) {
            $query->with('student.user')->latest()->take(10);
        }]);
        
        $activeCertificateCount = $certificateType->getActiveCertificateCount();
        $revokedCertificateCount = $certificateType->getRevokedCertificateCount();
        $expiredCertificateCount = $certificateType->getExpiredCertificateCount();
        
        return view('certificate_types.show', compact('certificateType', 'activeCertificateCount', 'revokedCertificateCount', 'expiredCertificateCount'));
    }

    /**
     * Show the form for editing the specified certificate type.
     */
    public function edit(CertificateType $certificateType)
    {
        return view('certificate_types.edit', compact('certificateType'));
    }

    /**
     * Update the specified certificate type in storage.
     */
    public function update(Request $request, CertificateType $certificateType)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:certificate_types,name,' . $certificateType->id,
            'description' => 'nullable|string',
            'template' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            $certificateType->update([
                'name' => $request->name,
                'description' => $request->description,
                'template' => $request->template,
            ]);

            DB::commit();

            return redirect()->route('certificate-types.index')
                ->with('success', 'Type de certificat mis à jour avec succès.');
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()
                ->with('error', 'Une erreur est survenue lors de la mise à jour du type de certificat: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified certificate type from storage.
     */
    public function destroy(CertificateType $certificateType)
    {
        // Vérifier si des certificats sont associés à ce type
        if ($certificateType->certificates()->count() > 0) {
            return redirect()->back()
                ->with('error', 'Impossible de supprimer ce type de certificat car des certificats y sont associés.');
        }

        DB::beginTransaction();

        try {
            $certificateType->delete();
            
            DB::commit();

            return redirect()->route('certificate-types.index')
                ->with('success', 'Type de certificat supprimé avec succès.');
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()
                ->with('error', 'Une erreur est survenue lors de la suppression du type de certificat: ' . $e->getMessage());
        }
    }

    /**
     * Preview the certificate template.
     */
    public function preview(CertificateType $certificateType)
    {
        return view('certificate_types.preview', compact('certificateType'));
    }
} 