<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Carbon\Carbon;
use App\Models\Item;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
 /*   protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();
    }*/



    protected function schedule(Schedule $schedule)
    {
        $schedule->call(function () {
            $itemsToDelete = Item::where('created_at', '<=', Carbon::now()->subDays(01))->get();
            foreach ($itemsToDelete as $item) {
                // حذف العنصر
                $item->delete();
            }
        })->daily(); // تشغيل الوظيفة يوميًا
    }
    protected function scheduleTimezone()
    {
        return 'Asia/Damascus';
    }



    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
