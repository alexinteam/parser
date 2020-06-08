<?php declare(strict_types=1);

namespace App;

use alxmsl\Odnoklassniki\API\Client;
use Illuminate\Support\Facades\Log;

class OKSocialNetworkRepository extends SocialNetworkRepository {

    public $api;

    public function __construct($tokenClass)
    {
        parent::__construct($tokenClass);
        $this->api = new Client();
        $this->api->setApplicationKey(getenv('OK_PUBLIC_KEY'))
            ->setToken($this->tokenClass::getOkTokenObject())
            ->setClientId(getenv('OK_APP_ID'))
            ->setClientSecret(getenv('OK_SECRET_KEY'));
    }

    /**
     * Метод возвращяет список ID пользователей, которые являются друзьями $userId
     *
     * @throws \UnexpectedValueException
     * @param $userId
     * @return array
     */
    public function getFriendsOfUser($userId): array
    {
        $result = $this->api->call('friends.get', ['fid' => $userId]);
        if(!is_array($result)) {
            throw new \UnexpectedValueException($result->getMessage());
        }
        return $result;
    }

    /**
     * Метод возвращяет массив
     *
     *
     * @param $IDs
     * @param array $fields
     * @return array
     */
    public function getInformationOfUsers($IDs, $fields = []): array
    {
        $okApiMaxUids = 100;
        $processed = 0;
        $result = [[]];
        while($processed < count($IDs)) {
            $batch = array_slice($IDs, $processed, $okApiMaxUids);
            try {
                $result[] = $this->api->callConfidence('users.getInfo', [
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


    /**
     * Метод возвращяет массив
     *
     *
     * @param $IDs
     * @param array $fields
     * @return array
     */
    public function getInformationOfUsersCall($IDs, $fields = []): array
    {
        $okApiMaxUids = 100;
        $processed = 0;
        $result = [[]];
        while($processed < count($IDs)) {
            $batch = array_slice($IDs, $processed, $okApiMaxUids);
            try {
                $result[] = $this->api->call('users.getInfo', [
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

    /**
     * Метод возвращяет $IDs, прошедшие фильтр
     *
     * @param $IDs
     * @return array
     */
    public function getFilteredIDs($IDs): array
    {
        try {
            $users = $this->getInformationOfUsers($IDs, ['pic_max', 'location', 'allows_anonym_access', 'accessible']);
            if(isset($users) && is_array($users)) {
                $startCount = count($users);
                if($startCount === 0) {
                    return [];
                }
                $users = array_filter($users, [$this, 'privateFilter']);
                $users = array_filter($users, [$this, 'countryFilter']);
                return array_map([$this, '_objectToUids'], array_values($users));
            }
        }catch (\UnexpectedValueException $e) {}
        return [];
    }

    /**
     * @return mixed
     */
    public function getTokenClass()
    {
        return $this->tokenClass;
    }

    /**
     * @param mixed $tokenClass
     */
    public function setTokenClass($tokenClass): void
    {
        $this->tokenClass = $tokenClass;
    }

    /**
     * Метод-фильтр для OK.ru, отфильтровывает профили не из РФ
     *
     * @param $userObject
     * @return bool
     */
    protected function countryFilter($userObject): bool
    {
        return ($userObject->location->countryCode === 'RU');
    }

    /**
     * Метод-фильтр для OK.ru, отфильтровывает приватные профили
     *
     * @param $userObject
     * @return bool
     */
    protected function privateFilter($userObject): bool
    {
        return (isset($userObject->allows_anonym_access) && $userObject->allows_anonym_access === true);
    }

    /**
     * Метод-фильтр для OK.ru, отфильтровывает деактивированных пользователей
     *
     * @param $userObject
     * @return bool
     */
    protected function deactivatedFilter($userObject): bool
    {
        return true;
    }

    /**
     * Метод возвращяет UID пользователя, приведенный к целому
     *
     * @param $user
     * @return int
     */
    private function _objectToUids($user) {
        return $user->uid;
    }

}