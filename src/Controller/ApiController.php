<?php

namespace App\Controller;

use Ackintosh\Ganesha\Builder;
use Ackintosh\Ganesha\GuzzleMiddleware;
use Ackintosh\Ganesha\Storage\Adapter\Redis;
use GuzzleHttp\HandlerStack;
use Predis\Client as RedisClient;
use GuzzleHttp\Client;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class ApiController extends AbstractController
{
    protected Client $client;

    /**
     * ApiController constructor.
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @Route("/api", name="api_no_cb")
     */
    public function api()
    {
        $client = new Client([
            'base_uri' => 'https://randomuser.me'
        ]);
        $response = $client->request('GET', '/api/');
        $userData = json_decode($response->getBody(), true);

        return $this->json($userData['results'][0]);
    }

    /**
     * @Route("/api-cb", name="api_cb")
     */
    public function apiWithCircuitBreaker()
    {
        // Creating Redis instance
        $redis = new RedisClient();

        // Creating adapter
        $adapter = new Redis($redis);

        // Creating Ganesha instance
        $ganesha = Builder::build([
            'timeWindow'           => 30,
            'failureRateThreshold' => 50,
            'minimumRequests'      => 10,
            'intervalToHalfOpen'   => 5,
            'adapter' => $adapter,
        ]);

        // Creating guzzle middleware and handler stack
        $middleware = new GuzzleMiddleware($ganesha);
        $handlers = HandlerStack::create();
        $handlers->push($middleware);

        $client = new Client([
            'base_uri' => 'https://randomuser.me',
            'handler' => $handlers
        ]);
        $response = $client->request('GET', '/api/');
        $userData = json_decode($response->getBody(), true);

        return $this->json($userData['results'][0]);
    }

    /**
     * @Route("/api-cb-di", name="api_cb_di")
     */
    public function apiWithCircuitBreakerAndDependencyInjection()
    {
        $response = $this->client->request('GET', '/api/');
        $userData = json_decode($response->getBody(), true);

        return $this->json($userData['results'][0]);
    }
}
