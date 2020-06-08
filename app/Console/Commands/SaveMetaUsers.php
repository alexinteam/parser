<?php


namespace App\Console\Commands;


use App\Jobs\UpdateVkUsersJob;
use App\User;
use Illuminate\Console\Command;
use DB;

class SaveMetaUsers extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'users:export';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'export users';

    public function handle()
    {
        $users = User::select(['id','metadata'])
            ->where('social_network_name','=','ok')
            ->orWhere('metadata', '!=', '')
            ->whereNotNull('metadata')
            ->orderBy('id')
            ->get()->toArray();

        $fp = fopen('ok.json', 'w');
        fwrite($fp, json_encode($users, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
        fclose($fp);

    }
}