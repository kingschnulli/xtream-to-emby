<?php

namespace App\Service\Xtream;

use App\Service\Xtream\Exception\XtreamException;
use App\Service\Xtream\Struct\XtreamUser;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class XtreamApiClient
{
    private ?XtreamUser $user = null;

    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly CacheInterface $xtreamCache
    )
    {
    }

    public function setUser(XtreamUser $user): void
    {
        $this->user = $user;
    }
    public function get(string $action = '', array $params = [], $endpoint = 'player_api.php'): object|array
    {
        if (null === $this->user) {
            throw new XtreamException('User not set, use setUser method to set user before making requests.');
        }

        $key = md5($this->user->getHost() . '/' . $endpoint . '?' . http_build_query(array_merge([
            'username' => $this->user->getUsername(),
            'password' => $this->user->getPassword(),
            'action' => $action,
        ], $params)));

        return $this->xtreamCache->get($key, function (ItemInterface $item) use ($action, $params, $endpoint) {
            $response = $this->client->request('GET', $this->user->getHost() . '/' . $endpoint, [
                'query' => array_merge([
                    'username' => $this->user->getUsername(),
                    'password' => $this->user->getPassword(),
                    'action' => $action,
                ], $params),
            ]);
            return json_decode($response->getContent());
        });
    }

    public function getStreamUrl($streamId): string
    {
        return $this->user->getHost() . '/' . $this->user->getUsername() . '/' . $this->user->getPassword() . '/' . $streamId;
    }

    public function getAccountInfo(): object
    {
        return $this->get();
    }

    public function getLiveCategories(?string $filter = null): array
    {
        $categories = $this->get('get_live_categories');
        // Filter categories by regex
        if (null !== $filter) {
            $categories = array_filter($categories, fn($category) => preg_match($filter, $category->category_name));
        }
        return $categories;
    }

    public function getVODCategories(?string $filter = null): array
    {
        $categories = $this->get('get_vod_categories');
        // Filter categories by regex
        if (null !== $filter) {
            $categories = array_filter($categories, fn($category) => preg_match($filter, $category->category_name));
        }
        return $categories;
    }

    public function getSeriesCategories(?string $filter = null): array
    {
        $categories = $this->get('get_series_categories');
        // Filter categories by regex
        if (null !== $filter) {
            $categories = array_filter($categories, fn($category) => preg_match($filter, $category->category_name));
        }
        return $categories;
    }

    public function getLiveStreams(string $category): array
    {
        return $this->get('get_live_streams', ['category_id' => $category]);
    }

    public function getVODStreams(string $category): array
    {
        return $this->get('get_vod_streams', ['category_id' => $category]);
    }

    public function getSeries(string $category): array
    {
        return $this->get('get_series', ['category_id' => $category]);
    }

    public function getVODInfo(string $id): object
    {
        return $this->get('get_vod_info', ['vod_id' => $id]);
    }

    public function getSeriesInfo(string $id): object
    {
        return $this->get('get_series_info', ['series_id' => $id]);
    }

    public function getShortEpg(string $id): object
    {
        return $this->get('get_short_epg', ['stream_id' => $id]);
    }

    public function getSimpleDataTable(string $id): object
    {
        return $this->get('get_simple_data_table', ['stream_id' => $id]);
    }

    public function getXmlTv(): object
    {
        return $this->get('', [], 'xmltv.php');
    }

}