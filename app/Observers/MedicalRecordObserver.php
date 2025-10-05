<?php

namespace App\Observers;

use App\Models\MedicalRecord;
use App\Models\MedicalRecordAudit;

class MedicalRecordObserver
{
    /**
     * Handle the MedicalRecord "created" event.
     */
    public function created(MedicalRecord $medicalRecord): void
    {
        MedicalRecordAudit::create([
            'medical_record_id' => $medicalRecord->id,
            'user_id' => auth()->id() ?? 1, // Usar ID 1 como fallback para pruebas
            'action' => 'created',
            'new_values' => $medicalRecord->toArray(),
            'ip_address' => request()->ip() ?? '127.0.0.1',
            'user_agent' => request()->userAgent() ?? 'System',
        ]);
    }

    /**
     * Handle the MedicalRecord "updated" event.
     */
    public function updated(MedicalRecord $medicalRecord): void
    {
        MedicalRecordAudit::create([
            'medical_record_id' => $medicalRecord->id,
            'user_id' => auth()->id() ?? 1,
            'action' => 'updated',
            'old_values' => $medicalRecord->getOriginal(),
            'new_values' => $medicalRecord->getChanges(),
            'ip_address' => request()->ip() ?? '127.0.0.1',
            'user_agent' => request()->userAgent() ?? 'System',
        ]);
    }

    /**
     * Handle the MedicalRecord "deleted" event.
     */
    public function deleted(MedicalRecord $medicalRecord): void
    {
        MedicalRecordAudit::create([
            'medical_record_id' => $medicalRecord->id,
            'user_id' => auth()->id() ?? 1,
            'action' => 'deleted',
            'old_values' => $medicalRecord->toArray(),
            'ip_address' => request()->ip() ?? '127.0.0.1',
            'user_agent' => request()->userAgent() ?? 'System',
        ]);
    }

    /**
     * Handle the MedicalRecord "restored" event.
     */
    public function restored(MedicalRecord $medicalRecord): void
    {
        MedicalRecordAudit::create([
            'medical_record_id' => $medicalRecord->id,
            'user_id' => auth()->id() ?? 1,
            'action' => 'restored',
            'new_values' => $medicalRecord->toArray(),
            'ip_address' => request()->ip() ?? '127.0.0.1',
            'user_agent' => request()->userAgent() ?? 'System',
        ]);
    }

    /**
     * Handle the MedicalRecord "force deleted" event.
     */
    public function forceDeleted(MedicalRecord $medicalRecord): void
    {
        MedicalRecordAudit::create([
            'medical_record_id' => $medicalRecord->id,
            'user_id' => auth()->id() ?? 1,
            'action' => 'force_deleted',
            'old_values' => $medicalRecord->toArray(),
            'ip_address' => request()->ip() ?? '127.0.0.1',
            'user_agent' => request()->userAgent() ?? 'System',
        ]);
    }
}
