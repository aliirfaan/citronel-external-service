# Citronel external service

Consume external APIs using a standard interface.

## Features
* Contract class to consume external APIs.
* Takes configuration from a configuration file that follows a standard format.
* Helper function for caching responses.
* Helper classes for events and subscribers.
* **You can use `aliirfaan/citronel-external-service-generator` package as dev dependency to generate config file, service class, migrations, models, events, listeners for your external service.**

## Requirements

* [Composer](https://getcomposer.org/)
* [Laravel](http://laravel.com/)

## Installation

* Install the package using composer:

```bash
 $ composer require aliirfaan/citronel-external-service
```

* Install the package with dev dependency:

```bash
 $ composer require aliirfaan/citronel-external-service --dev
```

## Contracts
* AbstractExternalService.php  
Your main service class must extend this abstract class.

## Traits
* ExternalServiceLogTrait  
Use this trait if we want to log request and responses for external services

* ExternalServiceEventTrait  
Use this trait in your event class

* ExternalServiceEventSubscriberTrait  
Use this trait in your subscriber class

## Steps
1. Create a configuration file with proper values for your external service that follows the format expected  by the `AbstractExternalService`.
2. Create your service class and extend `AbstractExternalService`.
3. Use `ExternalServiceLogTrait` if you want to log requests and responses. **This trait expects events, listeners and models to exist for the external service.**
3. Use `ExternalServiceCacheTrait` if you want to cache requests and responses.

## Usage
An example of how to consume an example external service [httpbin](https://httpbin.org/).

## Configuration

`app/config/http-bin.config`

```php
<?php
/*
| web_service
| connect_timeout_seconds | int
| Connection timeout in seconds
|
| timeout_seconds | int
| Request timeout in seconds
|
| endpoints | array
| Specific endpoints for the web service
|
| logging
| should_log | bool
| Global flag to enable or disable logging for this service
|
| caching
| should_cache | bool
| Global flag to enable or disable caching for this service
|
| pruning
| should_prune | bool
| Global flag to enable or disable pruning for this service
*/
return [
    'web_service' => [
        'base_url' => env('HTTP_BIN_PLATFORM_BASE_URL'),
        'connect_timeout_seconds' => env('HTTP_BIN_PLATFORM_CONNECT_TIMEOUT_SECONDS', 10),
        'timeout_seconds' => env('HTTP_BIN_PLATFORM_TIMEOUT_SECONDS', 60),
        'username' => env('HTTP_BIN_PLATFORM_USERNAME'),
        'password' => env('HTTP_BIN_PLATFORM_PASSWORD'),
        'api_key' => env('HTTP_BIN_PLATFORM_KEY'),
        'endpoints' => [
            'ip_endpoint' => [
                'api_operation' => 'ip',
                'endpoint' => '/example-endpoint',
                'method' => 'GET',
                'logging' => [ // this will override the global logging settings and can be omitted if not needed
                    'should_log' => env('HTTP_BIN_PLATFORM_SHOULD_LOG'),
                    'requests' => [
                        'should_log' => env('HTTP_BIN_PLATFORM_SHOULD_LOG_REQUESTS'),
                    ],
                    'responses' => [
                        'should_log' => env('HTTP_BIN_PLATFORM_SHOULD_LOG_RESPONSES'),
                        'log_channel' => env('HTTP_BIN_PLATFORM_LOG_RESPONSE_CHANNEL'),
                    ]
                ],
                'caching' => [
                    'should_cache' => env('HTTP_BIN_PLATFORM_SHOULD_CACHE'),
                    'cache_key' => 'HTTP_BIN_PLATFORM_example',
                    'cache_seconds' => env('HTTP_BIN_PLATFORM_CACHE_EXAMPLE_ENDPOINT_SEC', 3600),
                ]
            ]
        ],
    ],
    'logging' => [
        'should_log' => env('HTTP_BIN_PLATFORM_SHOULD_LOG', false),
        'requests' => [
            'should_log' => env('HTTP_BIN_PLATFORM_SHOULD_LOG_REQUESTS', true),
            'event_class' => env('HTTP_BIN_PLATFORM_LOG_REQUEST_EVENT_CLASS', App\Events\Test::class),
            'model' => env('HTTP_BIN_PLATFORM_LOG_REQUEST_MODEL', App\Models\Request::class),
        ],
        'responses' => [
            'should_log' => env('HTTP_BIN_PLATFORM_SHOULD_LOG_RESPONSES', true),
            'event_class' => env('HTTP_BIN_PLATFORM_LOG_RESPONSE_EVENT_CLASS', App\Events\Test::class),
            'model' => env('HTTP_BIN_PLATFORM_LOG_RESPONSE_MODEL', App\Models\Response::class),
            'log_response_channel' => env('HTTP_BIN_PLATFORM_LOG_RESPONSE_CHANNEL', 'HTTP_BIN_PLATFORM_response', null),
        ],
    ],
    'caching' => [
        'should_cache' => env('HTTP_BIN_PLATFORM_SHOULD_CACHE', false),
    ],
    'pruning' => [
        'should_prune' => env('HTTP_BIN_PLATFORM_SHOULD_PRUNE', true),
        'requests' => [
            'should_prune' => env('HTTP_BIN_PLATFORM_SHOULD_PRUNE_REQUESTS', true),
            'prune_days' => env('HTTP_BIN_PLATFORM_PRUNE_REQUESTS_DAYS', 60),
        ],
        'responses' => [
            'should_prune' => env('HTTP_BIN_PLATFORM_SHOULD_PRUNE_RESPONSES', true),
            'prune_days' => env('HTTP_BIN_PLATFORM_PRUNE_RESPONSES_DAYS', 60),
        ]
    ]
];

```
## Service class

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use aliirfaan\CitronelExternalService\Contracts\AbstractExternalService;
use aliirfaan\CitronelExternalService\Traits\ExternalServiceLogTrait;
use aliirfaan\CitronelExternalService\Traits\ExternalServiceCacheTrait;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\TooManyRedirectsException;
use Illuminate\Http\Client\ClientException;
use Illuminate\Http\Client\ServerException;

class HttpbinPlatformService extends AbstractExternalService
{
    use ExternalServiceLogTrait, ExternalServiceCacheTrait;

    public $configKey = 'http-bin';

    public function __construct()
    {
        parent::__construct();

        $this->setEndpoint('ip_endpoint');

        // set success code
        $this->successCode = 200;

        // set additional params for logging purposes
        $this->integrationResponseParams = array_merge($this->integrationResponseParams, [
            'origin' => null,
        ]);

        $this->setIntegrationLogConfig();

        $this->setIntegrationPruneConfig();
    }

    public function sendServiceRequest($correlationToken = null, $body = [])
    {
        $data = [
            'success' => false,
            'result' => null,
            'errors' => null,
            'message' => null,
            'issues' => [],
        ];

        $cacheConfigKey = 'cache_ip';
        $request = null;
        $processExtra = [];

        $legCorrelationToken = 'uuid';
        $processExtra['leg_correlation_token'] = $legCorrelationToken;

        try {
            // return cached response if available
            $getCachedResponseResult = $this->getCachedResponse();
            if (!is_null($getCachedResponseResult)) {
                return $getCachedResponseResult;
            }

            $requestUrl = $this->baseUrl . $this->endpoint['endpoint'];

            $data['result']['request']['integration']['url'] = $requestUrl;
            $data['result']['request']['integration']['correlation_token'] = $correlationToken;

            $data['result']['request']['integration']['leg_correlation_token'] = $legCorrelationToken;

            // dispatch event
            $this->dispatchRequestSentEvent($data);
            
            // remove sensitive data
            $data = $this->removeIntegrationRequestParams($data);

            $request = Http::connectTimeout($this->connectTimeoutSeconds)
            ->timeout($this->timeoutSeconds)
            ->accept($this->acceptContentType)
            ->send($this->endpoint['method'], $requestUrl)
            ->throw();
            
        } catch (RequestException $e) {
            report($e);

            // @todo handle
            $code = null;
            $exceptionMessage = 'a message';

            $processExtra = array_merge(
                $processExtra,
                [
                    'exception' => [
                        'message' => $exceptionMessage
                    ]
                ]
            );
        } catch (ConnectionException  $e) {
            report($e);

            // @todo handle
            $code = null;
            $exceptionMessage = 'a message';

            $processExtra = array_merge(
                $processExtra,
                [
                    'exception' => [
                        'message' => $exceptionMessage
                    ]
                ]
            );
        } catch (\Exception $e) { // any other errors
            report($e);

            // @todo handle
            $code = null;
            $exceptionMessage = 'a message';

            $processExtra = array_merge(
                $processExtra,
                [
                    'exception' => [
                        'message' => $exceptionMessage
                    ]
                ]
            );
        }

        $processResponse = $this->processServiceResponse($request, $correlationToken, $processExtra);
        $data = array_replace_recursive($data, $processResponse);

        return $data;
    }

    public function processServiceResponse($response = null, $correlationToken = null, $extra = [])
    {
        $data = [
            'success' => false,
            'result' => null,
            'errors' => null,
            'message' => null,
            'issues' => [],
        ];

        $data['result']['response']['integration'] = $this->integrationResponseParams;

        // set response correlation early to be able to trace in case of failure
        $data['result']['response']['integration']['correlation_token'] = $this->correlationToken;

        $data['result']['response']['integration']['leg_correlation_token'] = $this->legCorrelationToken;

        $this->rawResponse = !is_null($response) ? (string) $response->getBody() : null;
        $data['result']['response']['integration']['raw'] = $this->rawResponse;

        $this->httpStatus  = !is_null($response) ? $response->getStatusCode() : null;
        $data['result']['response']['integration']['http_status'] = $this->httpStatus;

        $this->logToChannel();

        // handle empty response
        if (empty($response)) {
            $data['errors'] = true;

            // @todo
            $code = null;
            $data['message'] = null;
        }

        // handle exceptions other than client and server exceptions
        if (is_null($data['errors']) && array_key_exists('exception', $extra)) {
            $data['errors'] = true;
            $data['message'] = $extra['exception']['message'];
        }

        // handle other errors
        if (is_null($data['errors'])) {
            $responseBody = json_decode($this->rawResponse, true);
            $responseCode = $response->getStatusCode();
            if (intval($responseCode) !== $this->successCode) {
                $data['errors'] = true;
                // @todo, create process error for endpoint if format is not the same as the general error
                $processErrorResponse = $this->processGeneralError($response);
                $data = array_replace_recursive($data, $processErrorResponse);
            }
        }

        if (is_null($data['errors'])) {
            $data['success'] = true;
            $data['result']['data'] = $responseBody;
            $data['result']['response']['integration']['success'] = true;

            // @todo include endpoint specific response params
            $data['result']['response']['integration']['origin'] = $responseBody['origin'] ?? null;
        }

        $this->dispatchResponseReceivedEvent($data);

        // remove sensitive data
        $data = $this->removeIntegrationResponseParams($data);

        if (is_null($data['errors'])) {
            $this->cacheResponse($data);
        }

        return $data;
    }

    public function processGeneralError($response = null, $extra = [])
    {
        $data = [
            'success' => false,
            'result' => null,
            'errors' => null,
            'message' => null,
            'issues' => [],
        ];
        
        $data['errors'] = true;

        $responseRawBody = (string) $response->getBody();
        $data['result']['response']['integration']['raw'] = $responseRawBody;

        $responseBody = json_decode($responseRawBody, true);

        $subProcessKey = null;
        if (array_key_exists('sub_process_key', $extra)) {
            $subProcessKey = $extra['sub_process_key'];
        }

        $eventKey = null;
        if (array_key_exists('event_key', $extra)) {
            $eventKey = $extra['event_key'];
        }

        $data['result']['response']['integration']['origin'] = array_key_exists('origin', $responseBody) ? $responseBody['origin'] : null;

        // generate code

        // message
        $data['message'] = 'a message';

        return $data;
    }
}
```