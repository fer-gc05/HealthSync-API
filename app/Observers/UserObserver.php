<?php

namespace App\Observers;

use App\Models\User;

class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        //
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        //
    }

    /**
     * Handle the User "deleted" event.
     * Cuando se elimina un usuario, también se eliminan sus relaciones
     */
    public function deleted(User $user): void
    {
        // Soft delete del paciente asociado
        if ($user->patient) {
            $user->patient->delete();
        }
        
        // Soft delete del personal médico asociado
        if ($user->medicalStaff) {
            $user->medicalStaff->delete();
        }
    }

    /**
     * Handle the User "restored" event.
     * Cuando se restaura un usuario, también se restauran sus relaciones
     */
    public function restored(User $user): void
    {
        // Restaurar el paciente asociado
        if ($user->patient) {
            $user->patient->restore();
        }
        
        // Restaurar el personal médico asociado
        if ($user->medicalStaff) {
            $user->medicalStaff->restore();
        }
    }

    /**
     * Handle the User "force deleted" event.
     * Cuando se elimina permanentemente un usuario, también se eliminan sus relaciones
     */
    public function forceDeleted(User $user): void
    {
        // Force delete del paciente asociado
        if ($user->patient) {
            $user->patient->forceDelete();
        }
        
        // Force delete del personal médico asociado
        if ($user->medicalStaff) {
            $user->medicalStaff->forceDelete();
        }
    }
}
