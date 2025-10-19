<?php

namespace App\Jobs;

use App\Models\AppointmentWaitlist;
use App\Services\AppointmentAssignmentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessAppointmentAssignment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $assignmentService;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        $this->assignmentService = app(AppointmentAssignmentService::class);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $processed = $this->assignmentService->processWaitlist();

            if (!empty($processed)) {
                Log::info('Appointment assignments processed', [
                    'processed_count' => count($processed),
                    'assignments' => $processed
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error processing appointment assignments: ' . $e->getMessage(), [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }
}
