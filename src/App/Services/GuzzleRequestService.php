<?php

namespace KnotAShell\Orders\App\Services;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

class GuzzleRequestService
{
    public $continue_on_request_error = false;
    public $only_return_contents = true;

    protected function _createClient(string $base_url, $headers = [])
    {
        $headers = array_merge($this->_getBaseHeaders(), $headers);

        return new Client([
            'base_uri' => $base_url,
            'headers' => $headers,
        ]);
    }

    /**
     * Does a HTTP request for a client and handles errors correctly.
     *
     * @param Client $client - The Guzzle client
     * @param string $uri - The URI to hit
     * @param string $method - The HTTP method
     * @param array $params - Any body params
     * @return object - The response
     */
    protected function _doRequest(Client $client, string $uri, string $method = 'GET', array $params = [], array $json = []): ?object
    {
        $options = [];
        if ($method === 'GET' && ! empty($params)) {
            $uri .= '?';
            foreach ($params as $key => $param) {
                $uri .= $key.'='.$param.'&';
            }
            $uri = substr($uri, 0, strlen($uri) - 1);
        } elseif (! empty($params)) {
            $options['form_params'] = $params;
        } elseif (! empty($json)) {
            $options['json'] = $json;
        }
        try {
            $response = $client->request($method, $uri, $options);
        } catch (RequestException | ClientException $e) {
            if (! $this->continue_on_request_error) {
                throw new Exception($e->getMessage(), $e->getCode());
            } else {
                return $e->getResponse();
            }
        }
        $status_code = $response->getStatusCode();
        if ($status_code === 500) {
            $error_msg = strtr('Guzzle request failed. URI: {uri} Method: {method} with response: {response}', [
                '{uri}' => $uri,
                '{method}' => $method,
                '{response}' => $response->getResponse()->getBody()->getContents(),
            ]);
            if (! $this->continue_on_request_error) {
                throw new Exception($error_msg);
            } else {
                Log::error($error_msg);
            }
        }

        return $this->only_return_contents ? (object) json_decode($response->getBody()->getContents()) : $response;
    }

    private function _getBaseHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }
}
