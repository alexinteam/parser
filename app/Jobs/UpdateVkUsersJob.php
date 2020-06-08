<?php

namespace App\Jobs;


use App\Token as TokenStorage;
use App\User;
use App\VKSocialNetworkRepository;
use Illuminate\Support\Facades\Log;

class UpdateVkUsersJob extends Job
{
    /**
     * @var integer
     */
    protected $userIds;

    /**
     * @var VKSocialNetworkRepository
     */
    protected $vkSocialRepository;

    /**
     * UpdateVkUsersJob constructor.
     * @param array $userIds
     */
    public function __construct(array $userIds)
    {
        $this->userIds = $userIds;
        $this->vkSocialRepository = new VKSocialNetworkRepository(TokenStorage::class);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $fields = [
                'verified', 'sex', 'bdate', 'city', 'country', 'home_town', 'has_photo',
                'has_mobile', 'education', 'universities', 'schools', 'maiden_name',
            ];
            $result = $this->vkSocialRepository->getInformationOfUsers($this->userIds, $fields);
            foreach ($this->userIds as $userId) {
                foreach ($result as $resultItem) {
                    if($userId === (int)$resultItem['id']) {
                        unset($resultItem['id']);
                        User::where('id', '=', $userId)->update([
                            'metadata' => json_encode($resultItem)
                        ]);
                    }
                }
            }

        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
    }
}
