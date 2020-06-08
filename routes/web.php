<?php
/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', 'UserController@stats');
$router->get('livenessCheck', 'HealthCheckController@healthCheck');
$router->get('readinessCheck', 'HealthCheckController@readinessCheck');

$router->group(['prefix' => 'cron'], function () use ($router) {
    $router->get('grabIds[/{count:[0-9]+}]', 'UserController@grabIds');
    $router->get('updateAllParsedUsers[/{count:[0-9]+}]', 'UserController@updateAllParsedUsers');
    $router->get('updateIds[/{count:[0-9]+}]', 'UserController@updateIds');
    $router->get('updateAge[/{count:[0-9]+}]', 'UserController@updateAge');
});
$router->group(['prefix' => 'v1'], function () use ($router) {
    /**
     * Consumers:
     * PhotoGrabber
     */
    $router->put('checkUserWithRemove/{socialNetworkName}/{userId}', 'UserController@checkUserWithRemove');
    /**
     * Consumers:
     * PhotoGrabber
     */
    $router->get('getUserIds/{count:[0-9]+}', 'UserController@getUserIds');
    /**
     * Consumers:
     * PhotoGrabber
     */
    $router->patch('markUserAsParsed/{socialNetworkName}/{userId:[0-9]+}', 'UserController@markUserAsParsed');
    /**
     * Consumers:
     * PhotoGrabber
     */
    $router->get('getParsedIdsCount', 'UserController@getParsedIdsCount');
    /**
     * Consumers:
     * MessageFileDownloader
     */
    $router->patch('markUserAsInactive/{socialNetworkName}/{userId:[0-9]+}', 'UserController@markUserAsInactive');

    $router->post('updateAgeForUser', 'UserController@updateAgeForUser');
});