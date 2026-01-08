<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ReleaseExpiredContracts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
{
    $today = now()->startOfDay();

    // كل العقود التي انتهت وما زالت active
    $expiredContracts = \App\Models\contracts::where('rent_end', '<=', $today)
        ->where('status', 'active')
        ->get();

    foreach ($expiredContracts as $contract) {

        // تحرير الشقة
        $apartment = \App\Models\Apartment::find($contract->apartment_id);

        if ($apartment) {
            $apartment->statusApartments = 'available';
            $apartment->save();
        }

        // تحديث حالة العقد
        $contract->status = 'expired';
        $contract->save();
    }
}
}
