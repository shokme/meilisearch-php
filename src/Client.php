<?php

declare(strict_types=1);

namespace MeiliSearch;

use MeiliSearch\Delegates\HandlesIndex;
use MeiliSearch\Delegates\HandlesSystem;
use MeiliSearch\Endpoints\Health;
use MeiliSearch\Endpoints\Indexes;
use MeiliSearch\Endpoints\Keys;
use MeiliSearch\Endpoints\Stats;
use MeiliSearch\Endpoints\Version;
use MeiliSearch\Http\GuzzleClient;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

class Client
{
    use HandlesIndex;
    use HandlesSystem;

    private $http;

    private $config;

    /**
     * @var Indexes
     */
    private $index;

    /**
     * @var Health
     */
    private $health;

    /**
     * @var Version
     */
    private $version;

    /**
     * @var Keys
     */
    private $keys;

    /**
     * @var Stats
     */
    private $stats;

    public function __construct(string $url, string $apiKey = null, ClientInterface $httpClient = null, RequestFactoryInterface $requestFactory = null, StreamFactoryInterface $streamFactory = null)
    {
        $this->http = $this->getHttpClient($url, $apiKey);
        $this->index = new Indexes($this->http);
        $this->health = new Health($this->http);
        $this->version = new Version($this->http);
        $this->stats = new Stats($this->http);
        $this->keys = new Keys($this->http);
    }

    public function getHttpClient($url, $apiKey)
    {
        $guzzleVersion = null;
        if (interface_exists('\GuzzleHttp\ClientInterface')) {
            if (defined('\GuzzleHttp\ClientInterface::VERSION')) {
                $guzzleVersion = (int) substr(\GuzzleHttp\Client::VERSION, 0, 1);
            } else {
                $guzzleVersion = \GUzzleHttp\ClientInterface::MAJOR_VERSION;
            }
        }

        if (null === $this->http) {
            if (class_exists('\GuzzleHttp\Client') && 7 <= $guzzleVersion) { // Guzzle >= 7 but should also support Guzzle 6
                $client = GuzzleClient::buildClient(['base_uri' => $url], $apiKey);
                $this->http = new Http\GuzzleClient($client);
            } else {
                $this->http = new Http\Client($url, $apiKey); // HttpClient / Symfony / ...
            }
        }

        return $this->http;
    }
}
