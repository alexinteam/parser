<?php


namespace App\Console\Commands;


use App\Jobs\DownloadVkUsersPhotos;
use App\Jobs\UpdateVkUsersJob;
use App\User;
use Illuminate\Console\Command;
use DB;
use Illuminate\Support\Facades\Storage;

class DispatchVkPhotoDownloader extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'download:vkUsersPhotos';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'download vkUsers photos';

    public function handle()
    {
        $users = User::where('social_network_name','=','vk')
            ->select('id')
            ->orWhere('metadata', '=', '')
            ->whereNull('metadata')
            ->orderBy('id')
            ->limit(5)
            ->get();

        foreach ($users->chunk(10) as $chunk) {
            $downloadVkUsersPhotosJob = new DownloadVkUsersPhotos($chunk->pluck('id')->toArray());
            dispatch($downloadVkUsersPhotosJob);
        }
    }
}