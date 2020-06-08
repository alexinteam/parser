<?php


namespace App\Console\Commands;


use App\Jobs\UpdateOkUsersJob;
use App\User;
use Illuminate\Console\Command;
use DB;

class DispatchOKUsersUpdate extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'update:okUsers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'update okUsers metadata';

    public function handle()
    {
        $users = User::where('social_network_name','=','ok')
            ->orWhere('metadata', '=', '')
            ->whereNull('metadata')
            ->orderBy('id')
            ->get();
        foreach ($users->chunk(300) as $chunk) {
            $updateOkUsersJob = new UpdateOkUsersJob($chunk->pluck('id')->toArray());
            dispatch($updateOkUsersJob);
        }

    }
}