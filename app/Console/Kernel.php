<?php

namespace App\Console;

use App\Console\Commands\DispatchOKUsersUpdate;
use App\Console\Commands\DispatchVkPhotoDownloader;
use App\Console\Commands\DispatchVkUsersUpdate;
use App\Console\Commands\SaveMetaUsers;
use Illuminate\Console\Scheduling\Schedule;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        DispatchVkUsersUpdate::class,
        DispatchOKUsersUpdate::class,
        SaveMetaUsers::class,
        DispatchVkPhotoDownloader::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        //
    }
}
