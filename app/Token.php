<?php

namespace App;

use alxmsl\Odnoklassniki\OAuth\Response\Token as OkToken;

class Token
{

    /**
     * Метод возвращяет случайный токен доступа ВК
     *
     * @return string
     */
    public static function getVKToken(): string {
        $client = new \GuzzleHttp\Client();
        $jsonTokenResponse = json_decode($client->request('GET', env('TOKEN_SERVICE') . 'token/vk', [
            'query' => ['application_key' => hash('sha256', env('APP_KEY'))]
        ])->getBody());
        return $jsonTokenResponse->access_token;
    }

    /**
     * Метод возвращяет случайный токен доступа ВК
     *
     * @return string
     */
    public static function getVKAfricaToken(): string {
        $client = new \GuzzleHttp\Client();
        $jsonTokenResponse = json_decode($client->request('GET', env('TOKEN_SERVICE') . 'token/vk_africa', [
            'query' => ['application_key' => hash('sha256', env('APP_KEY'))]
        ])->getBody());
        return $jsonTokenResponse->access_token;
    }

    /**
     * Метод возвращяет токен доступа на основе случайного токена
     *
     * @return OkToken
     */
    public static function getOkTokenObject(): OkToken {
        $client = new \GuzzleHttp\Client();
        $jsonTokenResponse = json_decode($client->request('GET', env('TOKEN_SERVICE') . 'token/ok', [
            'query' => ['application_key' => hash('sha256', env('APP_KEY'))]
        ])->getBody());
        $token = new OkToken();
        $token->setAccessToken($jsonTokenResponse->access_token)
            ->setRefreshToken($jsonTokenResponse->refresh_token)
            ->setTokenType(OkToken::TYPE_SESSION);
        return $token;
    }

}
