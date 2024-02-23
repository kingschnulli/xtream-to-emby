<?php

namespace App\Service\Xtream;

use App\Service\Xtream\Struct\XtreamUser;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class XtreamApiClientFactory
{
    public function __construct(
        #[Autowire(param: 'app.xtream.user')]
        private readonly string $user,
        #[Autowire(param: 'app.xtream.password')]
        private readonly string $password,
        #[Autowire(param: 'app.xtream.host')]
        private readonly string $host,
        private readonly HttpClientInterface $client,
        private readonly CacheInterface $xtreamCache
    ){}

    public function __invoke(): XtreamApiClient
    {
        $client = new XtreamApiClient($this->client, $this->xtreamCache);
        $user = new XtreamUser(
            $this->user,
            $this->password,
            $this->host
        );
        $client->setUser($user);
        return $client;
    }
}