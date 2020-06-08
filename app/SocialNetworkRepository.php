<?php declare(strict_types=1);

namespace App;

abstract class SocialNetworkRepository {

    protected $tokenClass;

    public function __construct($tokenClass)
    {
        $this->tokenClass = $tokenClass;
    }

    abstract public function getFriendsOfUser($userId): array;
    abstract public function getInformationOfUsers($IDs, $fields = []): array;
    abstract public function getFilteredIDs($users): array;

    abstract protected function deactivatedFilter($userArray): bool;
    abstract protected function privateFilter($userArray): bool;
    abstract protected function countryFilter($userArray): bool;
}