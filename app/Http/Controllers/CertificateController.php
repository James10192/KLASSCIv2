<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\CertificateType;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PDF;

class CertificateController extends Controller
{
    /**
     * Display a listing of the certificates.
     */
    public function index(Request $request)
    {
        $query = Certificate::with(['student.user', 'certificateType', 'issuedBy']);

        // Filtres
        if ($request->filled('certificate_type_id')) {
            $query->where('certificate_type_id', $request->certificate_type_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('certificate_number', 'like', "%{$search}%")
                  ->orWhereHas('student.user', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $certificates = $query->orderBy('issue_date', 'desc')->paginate(15);
        $certificateTypes = CertificateType::all();

        return view('certificates.index', compact('certificates', 'certificateTypes'));
    }

    /**
     * Show the form for creating a new certificate.
     */
    public function create()
    {
        $students = Student::with('user')->get();
        $certificateTypes = CertificateType::all();

        return view('certificates.create', compact('students', 'certificateTypes'));
    }

    /**
     * Store a newly created certificate in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'certificate_type_id' => 'required|exists:certificate_types,id',
            'issue_date' => 'required|date',
            'expiry_date' => 'nullable|date|after:issue_date',
            'remarks' => 'nullable|string',
            'certificate_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        DB::beginTransaction();

        try {
            // Générer un numéro de certificat unique
            $certificateNumber = 'CERT-' . date('Y') . '-' . Str::random(8);

            // Créer le certificat
            $certificate = Certificate::create([
                'student_id' => $request->student_id,
                'certificate_type_id' => $request->certificate_type_id,
                'certificate_number' => $certificateNumber,
                'issue_date' => $request->issue_date,
                'expiry_date' => $request->expiry_date,
                'remarks' => $request->remarks,
                'issued_by' => Auth::id(),
                'status' => 'issued',
            ]);

            // Gérer le fichier du certificat
            if ($request->hasFile('certificate_file')) {
                $path = $request->file('certificate_file')->store('certificates', 'public');
                $certificate->file_path = $path;
                $certificate->save();
            } else {
                // Générer un PDF si aucun fichier n'est fourni
                $this->generateCertificatePDF($certificate);
            }

            DB::commit();

            return redirect()->route('certificates.index')
                ->with('success', 'Certificat créé avec succès.');
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()
                ->with('error', 'Une erreur est survenue lors de la création du certificat: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified certificate.
     */
    public function show(Certificate $certificate)
    {
        $certificate->load(['student.user', 'certificateType', 'issuedBy']);
        
        return view('certificates.show', compact('certificate'));
    }

    /**
     * Show the form for editing the specified certificate.
     */
    public function edit(Certificate $certificate)
    {
        $certificate->load(['student.user', 'certificateType']);
        $students = Student::with('user')->get();
        $certificateTypes = CertificateType::all();

        return view('certificates.edit', compact('certificate', 'students', 'certificateTypes'));
    }

    /**
     * Update the specified certificate in storage.
     */
    public function update(Request $request, Certificate $certificate)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'certificate_type_id' => 'required|exists:certificate_types,id',
            'issue_date' => 'required|date',
            'expiry_date' => 'nullable|date|after:issue_date',
            'remarks' => 'nullable|string',
            'status' => 'required|in:issued,revoked,expired',
            'certificate_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        DB::beginTransaction();

        try {
            // Mettre à jour le certificat
            $certificate->update([
                'student_id' => $request->student_id,
                'certificate_type_id' => $request->certificate_type_id,
                'issue_date' => $request->issue_date,
                'expiry_date' => $request->expiry_date,
                'remarks' => $request->remarks,
                'status' => $request->status,
            ]);

            // Gérer le fichier du certificat
            if ($request->hasFile('certificate_file')) {
                // Supprimer l'ancien fichier si il existe
                if ($certificate->file_path) {
                    Storage::disk('public')->delete($certificate->file_path);
                }
                
                $path = $request->file('certificate_file')->store('certificates', 'public');
                $certificate->file_path = $path;
                $certificate->save();
            }

            DB::commit();

            return redirect()->route('certificates.index')
                ->with('success', 'Certificat mis à jour avec succès.');
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()
                ->with('error', 'Une erreur est survenue lors de la mise à jour du certificat: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified certificate from storage.
     */
    public function destroy(Certificate $certificate)
    {
        DB::beginTransaction();

        try {
            // Supprimer le fichier du certificat si il existe
            if ($certificate->file_path) {
                Storage::disk('public')->delete($certificate->file_path);
            }
            
            // Supprimer le certificat
            $certificate->delete();
            
            DB::commit();

            return redirect()->route('certificates.index')
                ->with('success', 'Certificat supprimé avec succès.');
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()
                ->with('error', 'Une erreur est survenue lors de la suppression du certificat: ' . $e->getMessage());
        }
    }

    /**
     * Download the certificate file.
     */
    public function download(Certificate $certificate)
    {
        if ($certificate->file_path) {
            return Storage::disk('public')->download($certificate->file_path, $certificate->certificate_number . '.pdf');
        }
        
        // Si le fichier n'existe pas, générer un PDF à la volée
        return $this->generateCertificatePDF($certificate, true);
    }

    /**
     * Revoke the specified certificate.
     */
    public function revoke(Certificate $certificate)
    {
        $certificate->update([
            'status' => 'revoked',
        ]);

        return redirect()->route('certificates.index')
            ->with('success', 'Certificat révoqué avec succès.');
    }

    /**
     * Generate a PDF for the certificate.
     */
    private function generateCertificatePDF(Certificate $certificate, $download = false)
    {
        $certificate->load(['student.user', 'certificateType', 'issuedBy']);
        
        $data = [
            'certificate' => $certificate,
            'student' => $certificate->student,
            'user' => $certificate->student->user,
            'type' => $certificate->certificateType,
            'issuer' => $certificate->issuedBy,
        ];
        
        $pdf = PDF::loadView('certificates.pdf', $data);
        
        if (!$download) {
            // Sauvegarder le PDF généré
            $filename = 'certificate_' . $certificate->certificate_number . '.pdf';
            $path = 'certificates/' . $filename;
            
            Storage::disk('public')->put($path, $pdf->output());
            
            $certificate->file_path = $path;
            $certificate->save();
            
            return true;
        }
        
        // Télécharger le PDF
        return $pdf->download('certificate_' . $certificate->certificate_number . '.pdf');
    }
} 