<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Model Observers
    |--------------------------------------------------------------------------
    |
    | Este archivo contiene el mapeo de modelos a sus observers.
    | Laravel descubrirá y registrará automáticamente estos observers.
    |
    */

    'observers' => [
        \App\Models\User::class => \App\Observers\UserObserver::class,
        // Agregar más observers aquí según sea necesario
        // \App\Models\Patient::class => \App\Observers\PatientObserver::class,
        // \App\Models\MedicalStaff::class => \App\Observers\MedicalStaffObserver::class,
    ],
];
