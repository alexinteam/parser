<?php

namespace App\Jobs;


use App\Token as TokenStorage;
use App\User;
use App\VKSocialNetworkRepository;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DownloadVkUsersPhotos extends Job
{
    /**
     * @var integer
     */
    protected $userIds;

    /**
     * DownloadVkUsersPhotos constructor.
     * @param array $userIds
     */
    public function __construct(array $userIds)
    {
        $this->userIds = $userIds;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        foreach ($this->userIds as $userId) {
            $s3 = Storage::disk('s3');
            $files = $s3->files('vk/' . $userId . '/');
            foreach ($files as $file) {
                try {
                    Storage::disk('local')->put($file,$s3->get($file));
                } catch (\Exception $e) {

                }
            }
        }

    }
}
