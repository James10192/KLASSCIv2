<?php

namespace App\Http\Controllers\Concerns;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

trait ResetsStaffPassword
{
    protected function resetDefaultStaffPassword(User $user, string $expectedRole, string $label): JsonResponse|RedirectResponse
    {
        $this->authorize('update', $user);

        if (! $user->hasRole($expectedRole)) {
            abort(404);
        }

        $password = 'Bonjour@2025';
        $user->password = Hash::make($password);
        $user->must_change_password = true;
        $user->save();

        Log::info('Password reset for staff to default', [
            'user_id' => $user->id,
            'role' => $expectedRole,
            'reset_by' => auth()->id(),
        ]);

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'password' => $password,
                'message' => 'Mot de passe réinitialisé avec succès!',
            ]);
        }

        return back()
            ->with('success', 'Mot de passe réinitialisé à Bonjour@2025. '.$label.' devra le changer à la prochaine connexion.')
            ->with('new_password', $password);
    }
}
