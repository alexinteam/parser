<?php declare(strict_types=1);

use Illuminate\Support\Facades\DB;

class User {

    private $vkObject;
    private $okObject;
    private $userIdsCache = [];

    /**
     * @var
     */
    private $availableSocialNetworks = ['vk', 'ok'];

    public function __construct($vk, $ok) {
        $this->vkObject = $vk;
        $this->okObject = $ok;
    }

    /**
     * Метод получает друзей пользователя с ID $userId
     *
     * @param Int $userId
     * @param String $socialNetworkName
     * @return array
     * @throws Exception
     */
    public function getUsersFriends($userId, String $socialNetworkName): ?array {
        $friends = [];
        try {
            $objectName = $socialNetworkName . 'Object';
            $friends = $this->{$objectName}->getFriendsOfUser($userId);
        }catch(UnexpectedValueException $e){
            if($user = \App\User::where([['social_network_name', '=', $socialNetworkName], ['id', '=', $userId]])->first()) {
                $user->delete();
            }
        }catch (Exception $e) {}
        return $friends;
    }

    /**
     * Метод сохраняет пользователя с айди $userId
     *
     * @param Int $userId
     * @param String $socialNetworkName
     * @return Int
     */
    public function saveUser($userId, String $socialNetworkName) {
        $this->userIdsCache[$socialNetworkName][] = $userId;
        return $userId;
    }

    /**
     * Метод проверяет, есть ли у нас в базе пользователь с Id $userId
     *
     * @param Int $userId
     * @param String $socialNetworkName
     * @return bool
     */
    public function isUserExists($userId, String $socialNetworkName): Bool {
        $result = DB::select('SELECT count(id) as `count` FROM `users` WHERE id = ? AND social_network_name = ?', [$userId, $socialNetworkName]);
        $isUserInDB = (bool) $result[0]->count;
        $result = false;
        if($isUserInDB) {
            $result = true;
        }
        if((!$isUserInDB) && isset($this->userIdsCache[$socialNetworkName]) && in_array($userId, $this->userIdsCache[$socialNetworkName], true)) {
            $result = true;
        }
        return $result;
    }

    /**
     * Метод возвращяет айдишник случайного пользователя из базы
     *
     * @param String $socialNetworkName;
     * @return Int
     * @throws Exception
     */
    public function getRandomUser(String $socialNetworkName) {
        $cachedList = \Illuminate\Support\Facades\Cache::get('randomCacheArray');
        if($cachedList && isset($cachedList[$socialNetworkName]) && (count($cachedList[$socialNetworkName]) > 100)) {
            $array = $cachedList[$socialNetworkName];
            $randomUserId = array_rand($array);
        }elseif($randomUser = $randomUser = \App\User::where('social_network_name', $socialNetworkName)->inRandomOrder()->first()) {
            $randomUserId = $randomUser->id;
        }else {
            $startPoints = [
                'vk' => 53083705,
                'ok' => 577930752438
            ];
            $randomUserId = $startPoints[$socialNetworkName];
        }
        return $randomUserId;
    }

    /**
     * Метод берет все айдишники, находящиеся в кэше, и делает Insert операцию
     */
    public function commitInsert(): void {
        foreach($this->userIdsCache as $networkName => $networkItems){
            $insertValues = array_unique($networkItems);
            $data = [];
            foreach($insertValues as $userId) {
                $data[] = [
                    'id' => $userId,
                    'social_network_name' => $networkName,
                    'parsed' => 0,
                    'active' => 1,
                    'created_at' => date('Y-m-d H:i:s')
                ];
            }
            DB::table('users')->insert($data);
        }
    }

    /**
     * Метод возвращяет общее количество записей пользователей в базе
     *
     * @param string
     * @return int
     */
    public function getUsersCount(String $socialNetworkName = 'all'): int {
        $cacheKey = $socialNetworkName . '_count';
        $cacheTime = 60;
        if(($socialNetworkName !== 'all') && in_array($socialNetworkName, ['vk', 'ok'], true)) {
            $count = \Illuminate\Support\Facades\Cache::remember($cacheKey, $cacheTime, function () use($socialNetworkName) {
                return \App\User::where('social_network_name', $socialNetworkName)->active()->count();
            });
        }else {
            $count = \Illuminate\Support\Facades\Cache::remember($cacheKey, $cacheTime, function () {
                return \App\User::active()->count();
            });
        }
        return $count;
    }

    /**
     * Метод отфильтровывает деактивированных, пользователей с закрытыми профилем и не из России
     *
     * @param array $userIds
     * @param String $socialNetworkName
     * @return array
     */
    public function filterOutAccounts(Array $userIds, String $socialNetworkName): Array {
        $objectName = $socialNetworkName . 'Object';
        return $this->{$objectName}->getFilteredIDs($userIds);
    }

    /**
     * Метод проходит по $count пользователям в базе и удаляет тех, с кого нельзя скачивать фотографии
     *
     * @param int $count
     */
    public function updateUserStatus($count = 100): void {
        $userIds = [];
        $result = \App\User::select(['id', 'social_network_name'])->notParsed()->orderBy('updated_at', 'asc')->orderBy('created_at', 'asc')->take($count)->get();
        foreach ($result as $user) {
            $userIds[$user->social_network_name][] = $user->id;
        }
        foreach($this->availableSocialNetworks as $networkName) {
            if(isset($userIds[$networkName])) {
                $users = $userIds[$networkName];
                $this->_filterExistingIds($users, 'vk');
            }
        }
    }

    /**
     * Метод проходит по $count пользователям в ьазе и обновляет возраст
     *
     * @param int $count
     */
    public function updateAge($count = 1000): int {
        $userIds = [];
        $ageUpdated = 0;
        $result = \App\User::select(['id', 'social_network_name'])->ageNotSet()->orderBy('updated_at', 'asc')->orderBy('created_at', 'asc')->take($count)->get();
        foreach ($result as $user) {
            $userIds[$user->social_network_name][] = $user->id;
        }
        foreach($this->availableSocialNetworks as $networkName) {
            if(isset($userIds[$networkName])) {
                $objectName = $networkName . 'Object';
                $fields = [];
                if($networkName === 'vk') {
                    $fields = ['bdate'];
                }
                if($networkName === 'ok') {
                    $fields = ['AGE'];
                }
                $usersInfo = $this->{$objectName}->getInformationOfUsers($userIds[$networkName], $fields);
                foreach($usersInfo as $user) {
                    if(($networkName === 'vk') && (!isset($user['bdate']) || (!preg_match('@\d?\d\.\d?\d\.\d\d\d\d@', $user['bdate'])))) {
                        continue;
                    }
                    if(($networkName === 'ok') && !isset($user->age)) {
                        continue;
                    }
                    $age = 0;
                    if($networkName === 'vk') {
                        $date = new DateTime($user['bdate']);
                        $now = new DateTime();
                        $age = $date->diff($now)->y;
                        $user = App\User::find($user['id']);
                    }
                    if($networkName === 'ok') {
                        $age = $user->age;
                        $user = App\User::find($user->uid);
                    }
                    $user->age = $age;
                    $user->save();
                    $ageUpdated++;
                }
            }
        }
        return $ageUpdated;
    }

    /**
     * Метод обновляет возраст для одного пользователя $userId социальной сети $socialNetworkName
     *
     * @param $userId
     * @param $socialNetworkName
     * @throws UnexpectedValueException
     * @throws Exception
     * @return int Возраст пользователя (0 в случае, если не удалось получить возраст)
     */
    public function getAgeForUser($userId, $socialNetworkName) {
        $objectName = $socialNetworkName . 'Object';
        $fields = [];
        if($socialNetworkName === 'vk') {
            $fields = ['bdate'];
        }
        if($socialNetworkName === 'ok') {
            $fields = ['AGE'];
        }
        $usersInfo = $this->{$objectName}->getInformationOfUsers([$userId], $fields);
        $age = 0;
        foreach($usersInfo as $user) {
            if(($socialNetworkName === 'vk') && (!isset($user['bdate']) || (!preg_match('@\d?\d\.\d?\d\.\d\d\d\d@', $user['bdate'])))) {
                continue;
            }
            if(($socialNetworkName === 'ok') && !isset($user->age)) {
                continue;
            }

            if($socialNetworkName === 'vk') {
                if($date = DateTime::createFromFormat('d.m.Y', $user['bdate'])){
                    $now = new DateTime();
                    $age = $date->diff($now)->y;
                }else {
                    throw new UnexpectedValueException('Birthdate ' . $user['bdate'] . ' is not valid');
                }
            }
            if($socialNetworkName === 'ok') {
                $age = $user->age;
            }
        }
        return $age;
    }

    /**
     * Метод проводит фильтрацию ID, обновляет и удаляет записи
     *
     * @param $IDs
     * @param $socialNetworkName
     */
    private function _filterExistingIds($IDs, $socialNetworkName):void {
        $objectName = $socialNetworkName . 'Object';
        $filteredUserIds = $this->{$objectName}->getFilteredIDs($IDs);
        $deleteList = array_diff($IDs, $filteredUserIds);
        $updateDateList = array_diff($IDs, $deleteList);
        if($updateDateList) {
            DB::table('users')
                ->whereIn('id', $updateDateList)
                ->update(['updated_at' => date('Y-m-d H:i:s')]);
        }
        if($deleteList) {
            DB::table('users')
                ->whereIn('id', $deleteList)
                ->delete();
        }
    }


}