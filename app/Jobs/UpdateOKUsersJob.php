<?php

namespace App\Jobs;


use alxmsl\Odnoklassniki\OAuth\Response\Token as OkToken;
use App\OKSocialNetworkRepository;
use App\Token as TokenStorage;
use App\User;
use Illuminate\Support\Facades\Log;
use alxmsl\Odnoklassniki\API\Client;

class UpdateOkUsersJob extends Job
{
    /**
     * @var integer
     */
    protected $userIds;

    /**
     * @var Client
     */
    protected $client;

    /**
     * UpdateVkUsersJob constructor.
     * @param array $userIds
     */
    public function __construct(array $userIds)
    {
        $this->userIds = $userIds;
        $this->client = new Client();
        $token = new OkToken();
        // using Вечный access_token: tkn1IxyP3ucaWVYegTUdM0pGL72fZHBcoofmOMjTOVOpplki4ZsGsnlbeN7Oe3bhcKRw6
        // https://ok.ru/app/1274036480
        $token->setAccessToken('tkn1IxyP3ucaWVYegTUdM0pGL72fZHBcoofmOMjTOVOpplki4ZsGsnlbeN7Oe3bhcKRw6')
            ->setTokenType(OkToken::TYPE_NONE);
        $this->client->setApplicationKey(getenv('OK_PUBLIC_KEY'))
            ->setToken($token)
            ->setClientId(getenv('OK_APP_ID'))
            ->setClientSecret(getenv('OK_SECRET_KEY'));
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
                'AGE',
                'BIRTHDAY',
                'BUSINESS',
                'CITY_OF_BIRTH',
                'COMMON_FRIENDS_COUNT',
                'CURRENT_LOCATION',
                'CURRENT_STATUS',
                'EMAIL',
                'FIRST_NAME',
                'FOLLOWERS_COUNT',
                'FRIENDS_COUNT',
                'GENDER',
                'HAS_EMAIL',
                'HAS_PHONE',
                'HAS_PRODUCTS',
                'LAST_NAME',
                'LOCATION',
                'LOCATION_OF_BIRTH',
                'NAME',
                'ODKL_EMAIL',
                'ODKL_LOGIN',
                'ODKL_MOBILE',
                'REGISTERED_DATE',
                'REGISTERED_DATE_MS',
                'SHORTNAME',
                'STATUS',
                'UID',
            ];

            $result = $this->getInformationOfUsers($this->userIds, $fields);
            foreach ($this->userIds as $userId) {
                foreach ($result as $resultItem) {
                    if($userId === (int)$resultItem->uid) {
                        unset($resultItem->uid);
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


    private function getInformationOfUsers($IDs, $fields = []): array
    {
        $okApiMaxUids = 100;
        $processed = 0;
        $result = [[]];
        while($processed < count($IDs)) {
            $batch = array_slice($IDs, $processed, $okApiMaxUids);
            try {
                $result[] = $this->client->callConfidence('users.getInfo', [
                    'uids' => implode(',', $batch),
                    'fields' => implode(',', $fields)
                ]);
                $processed += count($batch);
            } catch (\Exception $e) {
                Log::error($e->getMessage());
            }

        }
        return array_merge(...$result);
    }
}
