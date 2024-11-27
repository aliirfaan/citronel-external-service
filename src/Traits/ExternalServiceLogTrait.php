<?php

namespace aliirfaan\CitronelExternalService\Traits;

use Illuminate\Support\Facades\Log;

/**
 * Use this trait if we want to log request and responses for external services
 * Logging relies on events and listeners to log the request and response in corresponding database tables
 */
trait ExternalServiceLogTrait
{
    /**
     * dispatch event when request is sent
     *
     * @var object
     */
    public $logRequestEvent;
    
    /**
     * dispatch event when response is received
     *
     * @var object
     */
    public $logResponseEvent;
    
    /**
     * logRequestModel
     *
     * @var mixed
     */
    public $logRequestModel;
    
    /**
     * logResponseModel
     *
     * @var mixed
     */
    public $logResponseModel;

    /**
     * shouldPrune
     *
     * @var bool
     */
    public $shouldPrune = false;
    
    /**
     * shouldPruneRequests
     *
     * @var bool
     */
    public $shouldPruneRequests = false;
    
    /**
     * shouldPruneResponses
     *
     * @var bool
     */
    public $shouldPruneResponses = false;
    
    /**
     * pruneRequestDays
     *
     * @var bool
     */
    public $pruneRequestDays = false;
    
    /**
     * pruneResponseDays
     *
     * @var bool
     */
    public $pruneResponseDays = false;

    /**
     * config file name in /app/config for the external service
     *
     * @var mixed
     */
    public $configKey;
    
    /**
     * shouldLog
     *
     * @var bool
     */
    public $shouldLog = false;
    
    /**
     * shouldLogRequests
     *
     * @var bool
     */
    public $shouldLogRequests = false;
    
    /**
     * shouldLogResponses
     *
     * @var bool
     */
    public $shouldLogResponses = false;

    /**
     * Common request paramaters for the integration
     *
     * @var array
     */
    public $integrationRequestParams = [
        'api_operation' => null,
        'url' => null,
        'raw' => null,
        'correlation_token' => null,
        'leg_correlation_token' => null,
    ];

    /**
     * Common response paramaters for the integration
     *
     * @var array
     */
    public $integrationResponseParams = [
        'raw' => null,
        'correlation_token' => null,
        'leg_correlation_token' => null,
        'http_status' => null,
    ];

    /**
     * endpoint log config key that overrides the general cache
     *
     * @var mixed
     */
    public $endpointLogConfigKey;

    /**
     * logResponseChannel
     *
     * @var bool
     */
    public $logResponseChannel = null;

    public function setIntegrationLogConfig()
    {
        $this->shouldLog = config()->has($this->configKey . '.logging.should_log') ? config($this->configKey . '.logging.should_log') : false;

        if ($this->shouldLog) {
            $this->endpointLogConfigKey = config()->has($this->configKey . '.web_service.endpoints.' . $this->endpoint . '.logging') ? config($this->configKey . '.web_service.endpoints.' . $this->endpoint . '.logging') : null;

            if (!is_null($this->endpointLogConfigKey)) {
                $this->shouldLog = config($this->endpointLogConfigKey . '.should_log');
            }
        }

        if ($this->shouldLog) {
            $this->shouldLogRequests = config()->has($this->configKey . '.logging.requests.should_log') ? config($this->configKey . '.logging.requests.should_log') : false;

            if ($this->shouldLogRequests) {
                if (!is_null($this->endpointLogConfigKey)) {
                    $this->shouldLogRequests = config($this->endpointLogConfigKey . '.requests.should_log');
                }
            }

            $this->logRequestEvent = config()->has($this->configKey . '.logging.requests.event_class') ? config($this->configKey . '.logging.requests.event_class') : null;

            $this->logRequestModel = config()->has($this->configKey . '.logging.requests.model') ? config($this->configKey . '.logging.requests.model') : null;

            $this->shouldLogResponses = config()->has($this->configKey . '.logging.responses.should_log') ? config($this->configKey . '.logging.responses.should_log') : false;

            if ($this->shouldLogResponses) {
                if (!is_null($this->endpointLogConfigKey)) {
                    $this->shouldLogResponses = config($this->endpointLogConfigKey . '.responses.should_log');
                }
            }

            $this->logResponseEvent = config()->has($this->configKey . '.logging.responses.event_class') ? config($this->configKey . '.logging.responses.event_class') : null;

            $this->logResponseModel = config()->has($this->configKey . '.logging.responses.model') ? config($this->configKey . '.logging.responses.model') : null;

            if ($this->shouldLogResponses) {
                $this->logResponseChannel = config()->has($this->configKey . '.logging.responses.log_response_channel') ? config($this->configKey . '.logging.responses.log_response_channel') : null;

                if(!is_null($this->logResponseChannel)) {
                    $logEndpointResponseChannel = config()->has($this->endpointLogConfigKey . '.responses.log_response_channel') ? config($this->endpointLogConfigKey . '.responses.log_response_channel') : null;
                    if (!is_null($logEndpointResponseChannel)) {
                        $this->logResponseChannel = $logEndpointResponseChannel;
                    }
                }
            }
        }

        if (!is_null($this->endpoint)) {
            $this->integrationRequestParams['api_operation'] = $this->endpoint['api_operation'];
        }
    }

    public function setIntegrationPruneConfig()
    {
        $this->shouldPrune = config()->has($this->configKey . '.pruning.should_prune') ? config($this->configKey . '.pruning.should_prune') : false;

        if ($this->shouldPrune) {
            $this->shouldPruneRequests = config()->has($this->configKey . '.pruning.requests.should_prune') ? config($this->configKey . '.pruning.requests.should_prune') : false;

            $this->shouldPruneResponses = config()->has($this->configKey . '.pruning.responses.should_prune') ? config($this->configKey . '.pruning.responses.should_prune') : false;
        }

        $this->pruneRequestDays = config()->has($this->configKey . '.pruning.requests.prune_days') ? intval(config($this->configKey . '.pruning.requests.prune_days')) : 60;

        $this->pruneResponseDays = config()->has($this->configKey . '.pruning.responses.prune_days') ? intval(config($this->configKey . '.pruning.responses.prune_days')) : 60;
    }

    /**
     * Method removeIntegrationRequestParams
     *
     * @param array $data [explicite description]
     *
     * @return array
     */
    public function removeIntegrationRequestParams($data)
    {
        if (is_array($data['result']) && array_key_exists('request', $data['result'])) {
            unset($data['result']['request']);
        }

        return $data;
    }
    
    /**
     * Method removeIntegrationResponseParams
     *
     * @param array $data [explicite description]
     *
     * @return array
     */
    public function removeIntegrationResponseParams($data)
    {

        if (is_array($data['result']) && array_key_exists('response', $data['result'])) {
            unset($data['result']['response']);
        }

        return $data;
    }
    
    /**
     * Method dispatchRequestSentEvent
     *
     * @param array $data
     *
     * @return void
     */
    public function dispatchRequestSentEvent($data)
    {
        if ($this->shouldLogRequests) {
            $this->logRequestEvent::dispatch($data);
        }
    }
    
    /**
     * Method dispatchResponseReceivedEvent
     *
     * @param array $data
     *
     * @return void
     */
    public function dispatchResponseReceivedEvent($data)
    {
        if ($this->shouldLogResponses) {
            $this->logResponseEvent::dispatch($data);
        }
    }

    public function logToChannel()
    {
        $logMessage = $this->endpoint['api_operation'];
        $logContext = [
            'correlation_token' => $this->correlationToken,
            'leg_correlation_token' => $this->legCorrelationToken,
            'raw' => $this->rawResponse,
            'http_status' => $this->httpStatus,
        ];

        Log::channel($this->logResponseChannel)->info($logMessage, $logContext);
    }
}
