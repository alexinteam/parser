<?php declare(strict_types=1);

namespace App;

use VK\VK;

class VKSocialNetworkRepository extends SocialNetworkRepository {

    public $api;

    public function __construct($tokenClass)
    {
        parent::__construct($tokenClass);
        $this->api = new VK(
            getenv('VK_APP_ID'),
            getenv('VK_SECRET_KEY'),
            $this->tokenClass::getVKToken());
    }

    /**
     * Метод возвращяет список ID пользователей, которые являются друзьями $userId
     *
     * @throws \UnexpectedValueException
     * @throws \Exception
     * @param $userId
     * @return array
     */
    public function getFriendsOfUser($userId): array
    {
        $token = $this->tokenClass::getVKToken();
        $result = $this->api->api('friends.get', array(
            'v' => '5.92',
            'user_id' => $userId,
            'access_token' => $token
        ));
        if(null === $result) {
            throw new \UnexpectedValueException('CURL response error');
        }
        if(isset($result['error'])) {
            $client = new \GuzzleHttp\Client();
            $client->request('PUT', env('TOKEN_SERVICE') . 'token/vk/' . $token, [
                'query' => ['application_key' => hash('sha256', env('APP_KEY'))]
            ]);
            throw new \UnexpectedValueException($result['error']['error_msg']);
        }
        return $result['response']['items'];
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
        $token = $this->tokenClass::getVKToken();
        $result = $this->api->api('users.get', array(
            'v' => '5.92',
            'user_ids' => implode(',', $IDs),
            'fields' => implode(',', $fields),
            'access_token' => $token
        ));
        if(null === $result) {
            throw new \UnexpectedValueException('CURL response error');
        }
        if(isset($result['error'])) {
            $client = new \GuzzleHttp\Client();
            $client->request('PUT', env('TOKEN_SERVICE') . 'token/vk/' . $token, [
                'query' => ['application_key' => hash('sha256', env('APP_KEY'))]
            ]);
            throw new \UnexpectedValueException($result['error']['error_msg']);
        }
        return $result['response'];
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
            $users = $this->getInformationOfUsers($IDs, ['country']);
            if(isset($users) && is_array($users)) {
                $startCount = count($users);
                if($startCount === 0) {
                    return [];
                }
                // Фильтр деактивированных
                $users = array_filter($users, [$this, 'deactivatedFilter']);
                // Фильтр закрытых профилей
                $users = array_filter($users, [$this, 'privateFilter']);
                // Фильтр стран
                $users = array_filter($users, [$this, 'countryFilter']);
                return array_column(array_values($users), 'id');
            }
        }catch (\UnexpectedValueException $e) {}
        return [];
    }

    /**
     * Метод-фильтр для ВК, отфильтровывает деактивированных пользователей
     *
     * @param $userArray
     * @return bool
     */
    protected function deactivatedFilter($userArray): bool {
        if(isset($userArray['deactivated'])) {
            return false;
        }
        return true;
    }

    /**
     * Метод-фильтр для ВК, отфильтровывает приватные профили
     *
     * @param $userArray
     * @return bool
     */
    protected function privateFilter($userArray): bool
    {
        return (
            isset($userArray['is_closed'])
            && ($userArray['is_closed'] === false)
            && $userArray['can_access_closed']
            && ($userArray['can_access_closed'] === true)
        );
    }

    /**
     * Метод-фильтр для ВК, отфильтровывает профили не из РФ
     *
     * @param $userArray
     * @return bool
     */
    protected function countryFilter($userArray): bool
    {
        return (isset($userArray['country']) && ($userArray['country']['id'] === 1));
    }

}