<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\SafetyCheck;

class ActivateScheduledSafetyCheckJob implements ShouldQueue, ShouldBeUnique
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        logger()->info('Scheduled safety check scan', [
            'now' => now()->toDateTimeString(),
            'timezone' => config('app.timezone'),
        ]);

        $safetyChecks = SafetyCheck::where('status', 'scheduled')
            ->where('scheduled_at', '<=', now())
            ->get();

        foreach ($safetyChecks as $safetyCheck) {
            logger()->info('Scheduled safety check matched', [
                'id' => $safetyCheck->id,
                'scheduled_at' => $safetyCheck->scheduled_at,
                'status' => $safetyCheck->status,
            ]);

            if ($safetyCheck->type === 'training') {
                $safetyCheck->activateTraining();
            }
        }
    }
}
