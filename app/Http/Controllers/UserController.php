<?php
namespace App\Http\Controllers;

include_once dirname(__DIR__, 3) . DIRECTORY_SEPARATOR. 'User.php';

use App\OKSocialNetworkRepository;
use App\VKSocialNetworkRepository;
use Aws\Credentials\CredentialProvider;
use Aws\Exception\AwsException;
use Aws\Sns\SnsClient;
use Curl\Curl;
use Google\Cloud\Logging\LoggingClient;
use Google\Cloud\PubSub\PubSubClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Stopwatch\Stopwatch;
use User;
use App\User as UserOrm;
use App\Token as TokenStorage;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * @var User
     */
    private $userInstance;

    /**
     * @var
     */
    private $availableSocialNetworks = ['vk', 'ok'];

    /**
     * Create a new controller instance.
     *
     * @throws \Exception
     * @return void
     */
    public function __construct()
    {
        $vk = new VKSocialNetworkRepository(TokenStorage::class);
        $ok = new OKSocialNetworkRepository(TokenStorage::class);
        $this->userInstance = new User($vk, $ok);
    }

    /**
     * Информация для главной страницы
     *
     * @return JsonResponse
     */
    public function stats(): JsonResponse {
        $healthController = new HealthCheckController();
        $healthCheckResult = $healthController->healthCheck()->getStatusCode();
        $readinessCheckResult = $healthController->readinessCheck()->getStatusCode();
        return response()->json([
            'service_name' => 'id-grabber',
            'health-check-result' => $healthCheckResult,
            'readiness-check-result' => $readinessCheckResult
        ]);
    }

    /**
     * Главный метод сбора айдишников
     *
     * @param int $count
     * @throws \Exception
     * @return JsonResponse
     */
    public function grabIds($count = 1000): JsonResponse {
        set_time_limit(120);
        $startingTime = microtime(true);
        $split = $this->_calculateSplitCount($count);
        $realTotalCount = 0;
        $softTimeLimit = 55;
        $messageQueue = [];
        /*
        $logging = new LoggingClient([
            'projectId' => getenv('GOOGLE_PROJECT_ID')
        ]);
        $logger = $logging->psrLogger('app');
        */
        foreach($this->availableSocialNetworks as $networkName) {
            $maxCount = $split;
            $currentCount = 0;
            $randomFriendId = null;
            while($currentCount < $maxCount) {
                $elapsedTime = round(microtime(true) - $startingTime, 3);
                if($softTimeLimit < $elapsedTime) {
                    //$logger->debug('Time limit is reached');
                    break;
                }
                $userId = $this->userInstance->getRandomUser($networkName);
                //$logger->debug("Random user for network $networkName is $userId");
                if($randomFriendId !== null) {
                    $userId = $randomFriendId;
                }
                $userFriends = $this->userInstance->getUsersFriends($userId, $networkName);
                if((count($userFriends) > 0) &&(
                    $friendList = $this->userInstance->filterOutAccounts($userFriends, $networkName))
                    && count($friendList) > 0
                ) {
                    $cachedList = Cache::remember('randomCacheArray', 5, function () use($friendList, $networkName) {
                        return [$networkName => $friendList];
                    });
                    if(isset($cachedList[$networkName]) && count($cachedList[$networkName]) < 10000) {
                        foreach($friendList as $id) {
                            if(in_array($id, $cachedList[$networkName])) {
                                $cachedList[$networkName][] = $id;
                            }
                            if(count($cachedList[$networkName]) >= 10000) {
                                break;
                            }
                        }
                    }
                    if(!isset($cachedList[$networkName])) {
                        $cachedList[$networkName] = $friendList;
                    }
                    Cache::put('randomCacheArray', $cachedList, 5);
                    foreach($friendList as $friend) {
                        if(!$this->userInstance->isUserExists($friend, $networkName)) {
                            $this->userInstance->saveUser($friend, $networkName);
                            $currentCount++;
                            $realTotalCount++;
                            $messageQueue = $this->_putMessageToQueue($messageQueue, $friend, $networkName);
                            if($currentCount === $maxCount) {
                                break;
                            }
                        }
                    }
                    $randomIndex = random_int(0, count($friendList)-1);
                    $randomFriendId = $friendList[$randomIndex];
                }else {
                    $randomFriendId = $this->userInstance->getRandomUser($networkName);
                }
            }
        }
        $this->userInstance->commitInsert();
        $this->_publishMessageToPubSub($messageQueue);
        $endTime = microtime(true);
        $elapsedTime = round($endTime - $startingTime, 3);
        return response()->json(['id_count' => $realTotalCount, 'time_elapsed' => $elapsedTime, 'queue' => $messageQueue]);
    }

    private function _putMessageToQueue($messageQueue, $userId, $socialNetwork) {
        $messageQueue[] = [
            'data' => 'New id fetched',
            'attributes' => [
                'social_network_name' => $socialNetwork,
                'user_id' => (string) $userId,
            ]
        ];
        return $messageQueue;
    }

    private function _publishMessageToPubSub($messageQueue): void {
        /*
        $pubSub = new PubSubClient();
        $topic = $pubSub->topic('new-id-fetched');
        if((count($messageQueue) > 0) && $topic->exists()) {
            $chunks = array_chunk($messageQueue,  250);
            foreach ($chunks as $chunk) {
                $topic->publishBatch($chunk);
            }
        }
        */
        $credentials = CredentialProvider::env();
        $snsClient = new SnsClient([
            'version' => 'latest',
            'region' => 'eu-central-1',
            'credentials' => $credentials
        ]);
        $topic = 'arn:aws:sns:eu-central-1:957656580109:new-id-fetched';
        foreach($messageQueue as $message) {
            try {
                $snsClient->publish([
                    'Message' => serialize($message),
                    'TopicArn' => $topic,
                ]);
            } catch (AwsException $e) {
                // output error message if fails
            }
        }
    }

    /**
     * Метод обновляет $count пользователям поле photo_count (кол-во фотографий)
     *
     * @todo переместить получение статуса фото в отдельный сервис
     * @param int $count
     * @return JsonResponse
     */
    public function updateAllParsedUsers($count = 100): JsonResponse {
        $startingTime = microtime(true);
        $users = UserOrm::select('id')
            ->where(['parsed' => 1, 'photo_count' => 0])
            ->orderBy('updated_at', 'asc')
            ->take($count)->get();
        foreach($users as $user) {
            $count = DB::table('photos')
                ->where(['user_id' => $user->id, 'downloaded' => 1])
                ->orderBy('created_at')
                ->count();
            $user = UserOrm::find($user->id);
            $user->photo_count = $count;
            $user->updated_at = date('Y-m-d H:i:s');
            $user->save();
        }
        $endTime = microtime(true);
        $elapsedTime = round($endTime - $startingTime, 3);
        return response()->json(['updated_count' => $count, 'time_elapsed' => $elapsedTime]);
    }

    /**
     * Метод обходит имеющуюся базу ID и удаляет неактульные (страна, приватность, удаление профиля)
     *
     * @param int $count
     * @return JsonResponse
     */
    public function updateIds($count = 10000): JsonResponse {
        $startingTime = microtime(true);
        $maxCount = (int) $count;
        $currentCount = 0;
        while($currentCount < $maxCount) {
            $batchSize = 250;
            if($maxCount < $batchSize) {
                $batchSize = $maxCount;
            }
            $this->userInstance->updateUserStatus($batchSize);
            $currentCount += $batchSize;
        }
        $endTime = microtime(true);
        $elapsedTime = round($endTime - $startingTime, 3);
        return response()->json(['updated_count' => $maxCount, 'time_elapsed' => $elapsedTime]);
    }

    /**
     * Метод обходит имеющуюся базу ID и удаляет неактульные (страна, приватность, удаление профиля)
     *
     * @param int $count
     * @return JsonResponse
     */
    public function updateAge($count = 1000): JsonResponse {
        $startingTime = microtime(true);
        $maxCount = (int) $count;
        $currentCount = 0;
        $realCount = 0;
        while($currentCount < $maxCount) {
            $batchSize = 250;
            if($maxCount < $batchSize) {
                $batchSize = $maxCount;
            }
            $realCount += $this->userInstance->updateAge($batchSize);
            $currentCount += $batchSize;
        }
        $endTime = microtime(true);
        $elapsedTime = round($endTime - $startingTime, 3);
        return response()->json(['updated_count' => $realCount, 'time_elapsed' => $elapsedTime]);
    }

    /**
     * Метод для обработки сообщения о получении нового ID и установки возраста
     *
     */
    public function updateAgeForUser(Request $request)
    {
        /*
        $logging = new LoggingClient([
            'projectId' => getenv('GOOGLE_PROJECT_ID')
        ]);
        $logger = $logging->psrLogger('app');
        */
        $statusCode = 204;
        $stopwatch = new Stopwatch();
        $stopwatch->start('Method run');
        if($request->user_id && $request->social_network_name) {
            $attributes['user_id'] = $request->user_id;
            $attributes['social_network_name'] = $request->social_network_name;
        }else {
            $input = file_get_contents('php://input');
            $message = json_decode($input, true);
            $message = unserialize($message['Message'], ['allowed_classes' => false]);
            $attributes = $message['attributes'];
        }
        try {
            $stopwatch->start('user-fetching');
            $user = UserOrm::where(
                ['social_network_name' => $attributes['social_network_name'], 'id' => $attributes['user_id']]
            )->first();
            //$logger->debug('User fetching duration ' . $stopwatch->stop('user-fetching'));
            if ($user) {
                $stopwatch->start('calling getAgeForUser');
                $age = $this->userInstance->getAgeForUser($user->id, $user->social_network_name);
                //$logger->debug('Getting age duration ' . $stopwatch->stop('calling getAgeForUser'));
                $user->age = $age;
                $user->save();
            }
        }catch(\UnexpectedValueException $e) {
            /*$logger->debug($e->getMessage());
            $logger->debug($attributes['user_id']);
            $logger->debug($attributes['social_network_name']);*/
            $this->_deactivateUser($attributes['social_network_name'], $attributes['user_id']);
        }catch (\Exception $e) {
            /*$logger->debug($e->getMessage());
            $logger->debug($e->getCode());
            $logger->debug($attributes['user_id']);
            $logger->debug($attributes['social_network_name']);*/
            $statusCode = 500;
        }
        $event = $stopwatch->stop('Method run');
        //$logger->debug('Method run duration ' . $event->getDuration());
        return response('', $statusCode);
    }

    /**
     * Метод отправляет запрос на деактивацию пользователя
     *
     * @param $socialNetworkName
     * @param $userId
     * @throws \ErrorException
     */
    private function _deactivateUser($socialNetworkName, $userId): void {
        $logging = new LoggingClient([
            'projectId' => getenv('GOOGLE_PROJECT_ID')
        ]);
        $logger = $logging->psrLogger('app');
        $logger->info('Deactivating user ' . $userId);
        $curl = new Curl();
        $postData = ['request-by' => 'photo-grabber'];
        $curl->setOpt(CURLOPT_TIMEOUT, 2);
        $curl->patch('http://' . getenv('SERVICE_ID_GRABBER_URL') . "/v1/markUserAsInactive/$socialNetworkName/$userId", $postData, true)->response;
    }

    /**
     * Метод проверяет соответствие ID правилам, и удаляет его, в случае несоответсвия фильтрам
     *
     * @param $socialNetworkName
     * @param $userId
     * @return \Illuminate\Http\JsonResponse
     * @todo нужен рефакторинг https://facechain.tpondemand.com/entity/354-proizvesti-refaktoring-koda-svyazannogo-s-filtraciej
     */
    public function checkUserWithRemove($socialNetworkName, $userId): JsonResponse {
        if ($userRecord = UserOrm::where(['social_network_name' => $socialNetworkName, 'id' => $userId])->first()) {
            $possibleUserId = $this->userInstance->filterOutAccounts([$userId], $socialNetworkName);
            if(!empty($possibleUserId)) {
                $userRecord->updated_at = date('Y-m-d H:i:s');
                $userRecord->save();
                $status = 200;
                $message = 'User was checked and not removed';
            }else {
                $userRecord->delete();
                $status = 2000;
                $message = 'User was checked was removed';
            }
            return response()->json(['status' => $status, 'message' => $message, 'parameters' => [
                'socialNetworkName' => $socialNetworkName, 'userId' => $userId
            ]]);
        }
        return response()->json(['error' => 'User not found', 'status' => 404, 'parameters' => [
            'socialNetworkName' => $socialNetworkName, 'userId' => $userId
        ]], 404);
    }

    /**
     * Метод отдает $count самых старых клиентов, $skip - сколько пропустить записей от начала (пагинация)
     *
     * @param Request $request
     * @param $count
     * @return mixed
     */
    public function getUserIds(Request $request, $count) {
        $skip = (int) $request->input('skip');
        $users = UserOrm::select('id', 'social_network_name')->where('parsed', 0)->active()->orderBy('created_at', 'asc')->skip($skip)->take((int) $count)->get();
        return response()->json([
            'status' => 200,
            'message' => 'Users\' ids fetched successfully',
            'parameters' => [
                'count' => $count, 'skip' => $skip
            ],
            'body' => $users
        ]);
    }

    /**
     * Метод помечает пользоваться $userId как распарсенных пользователя
     *
     * @param $socialNetworkName
     * @param $userId
     * @return \Illuminate\Http\JsonResponse
     */
    public function markUserAsParsed($socialNetworkName, $userId): JsonResponse {
        $userId = (int) $userId;
        if($user = UserOrm::where(['social_network_name' => $socialNetworkName, 'id' => $userId])->first()) {
            $user->parsed = 1;
            $user->updated_at = date('Y-m-d H:i:s');
            $user->save();
            return response()->json(['status' => 200, 'message' => 'User was mark as parsed', 'parameters' => [
                'socialNetworkName' => $socialNetworkName,
                'userId' => $userId
            ]], 200);
        }
        return response()->json(['error' => 'User not found', 'status' => 404, 'parameters' => [
            'socialNetworkName' => $socialNetworkName,
            'userId' => $userId
        ]], 404);
    }

    /**
     * Метод помечает пользоваться $userId как неактивного пользователя
     *
     * @param $socialNetworkName
     * @param $userId
     * @return \Illuminate\Http\JsonResponse
     */
    public function markUserAsInactive($socialNetworkName, $userId): JsonResponse {
        $userId = (int) $userId;
        if($user = UserOrm::where(['social_network_name' => $socialNetworkName, 'id' => $userId])->first()) {
            $user->active = 0;
            $user->updated_at = date('Y-m-d H:i:s');
            $user->save();
            return response()->json(['status' => 200, 'message' => 'User was mark as inactive', 'parameters' => [
                'socialNetworkName' => $socialNetworkName,
                'userId' => $userId
            ]], 200);
        }
        return response()->json(['error' => 'User not found', 'status' => 404, 'parameters' => [
            'socialNetworkName' => $socialNetworkName,
            'userId' => $userId
        ]], 404);
    }

    /**
     * Метод возвращаяет количество распарсенных пользователей
     *
     * @return JsonResponse
     */
    public function getParsedIdsCount(): JsonResponse {
        $count = UserOrm::where('parsed', 1)->active()->count();
        return response()->json(['status' => 200, 'message' => 'IDs were counted', 'body' => ['count' => $count
        ]], 200);
    }

    /**
     * Метод делит входящее число желаемого количество итераций на кол-во активных социальных сетей
     *
     * @param Int $count
     * @return Int
     */
    private function _calculateSplitCount(Int $count): Int{
        $socialNetworkCount = count($this->availableSocialNetworks);
        return (int) ceil($count / $socialNetworkCount);
    }

}
