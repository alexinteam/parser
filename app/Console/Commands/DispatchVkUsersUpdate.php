<?php


namespace App\Console\Commands;


use App\Jobs\UpdateVkUsersJob;
use App\User;
use Illuminate\Console\Command;
use DB;

class DispatchVkUsersUpdate extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'update:vkUsers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'update vkUsers metadata';

    public function handle()
    {
        $users = User::where('social_network_name','=','vk')
            ->orWhere('metadata', '=', '')
            ->whereNull('metadata')
            ->orderBy('id')
            ->get();
        foreach ($users->chunk(300) as $chunk) {
            $updateVkUsersJob = new UpdateVkUsersJob($chunk->pluck('id')->toArray());
            dispatch($updateVkUsersJob);
        }
//
//        DB::table('users')
//            ->orderBy('id')
//            ->where('social_network_name','=','vk')
//            ->whereNull('metadata')
//            ->chunk(300, function($users) {
//                try {
//                    $updateVkUsersJob = new UpdateVkUsersJob($users->pluck('id')->toArray());
//                    dispatch($updateVkUsersJob);
//                } catch (\Exception $e) {
//                    //
//                }
//
//        });
    }
}