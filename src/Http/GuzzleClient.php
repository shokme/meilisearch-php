<?php

namespace MeiliSearch\Http;

use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use MeiliSearch\Contracts\Http;
use MeiliSearch\Exceptions\HTTPRequestException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

use function GuzzleHttp\choose_handler;
use function GuzzleHttp\Psr7\stream_for;

class GuzzleClient implements Http
{
    /**
     * @var ClientInterface
     */
    private $http;

    private static $headers;

    public function __construct(ClientInterface $http = null)
    {
        $this->http = $http;
    }

    private function sendRequest(RequestInterface $request): ResponseInterface
    {
        try {
            $response = $this->http->sendRequest($request);
        } catch (ClientExceptionInterface $exception) {
            var_dump($exception);
            die;
        }

        return $response;
    }

    public static function buildClient(array $config = [], $apiKey = null)
    {
        $handlerStack = new HandlerStack(choose_handler());
        $handlerStack->push(Middleware::prepareBody(), 'prepare_body');
        $config = array_merge(['handler' => $handlerStack], $config);
        self::$headers = array_filter([
            'Content-type' => 'application/json',
            'Accept' => 'application/json',
            'X-Meili-API-Key' => $apiKey
        ]);

        return new \GuzzleHttp\Client($config);
    }

    public function get($path, array $query = [])
    {
        $request = new Request('GET', $path . $this->buildQueryString($query));
        return $this->parseResponse($this->sendRequest($request));
    }

    public function post(string $path, $body = null, array $query = [])
    {
        $request = new Request('POST', $path . $this->buildQueryString($query), self::$headers, stream_for(json_encode($body)));
        return $this->parseResponse($this->sendRequest($request));
    }

    public function put(string $path, $body = null, array $query = [])
    {
        // TODO: Implement put() method.
    }

    public function patch(string $path, $body = null, array $query = [])
    {
        // TODO: Implement patch() method.
    }

    public function delete($path, array $query = [])
    {
        // TODO: Implement delete() method.
    }

    private function buildQueryString(array $queryParams = []): string
    {
        return $queryParams ? '?' . http_build_query($queryParams) : '';
    }

    private function parseResponse(ResponseInterface $response)
    {
        if ($response->getStatusCode() >= 300) {
            $body = json_decode($response->getBody()->getContents(), true);
            throw new HTTPRequestException($response->getStatusCode(), $body);
        }

        return json_decode($response->getBody()->getContents(), true);
    }
}
