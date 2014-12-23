<?php


namespace h4cc\HHVMProgressBundle\Services;


class PackagistApi
{
    /** @var \Packagist\Api\Client  */
    private $client;

    public function __construct() {
        $this->client = new \Packagist\Api\Client();
    }

    public function getAllPackageNames() {
        return $this->client->all();
    }

    public function getInfosByName($name) {
        return $this->client->get($name);
    }
}
 