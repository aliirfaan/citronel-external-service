<?php

namespace aliirfaan\CitronelExternalService\Contracts;

abstract class AbstractExternalService
{
    /**
     *
     * api baseUrl
     *
     * @var string
     */
    public $baseUrl;

    /**
     *
     * endpoint
     *
     * @var array
     */
    public $endpoint;
    
    /**
     * success code for external service
     *
     * @var string
     */
    public $successCode;

    /**
     * timeoutSeconds
     *
     * timeout in seconds before throwing error
     *
     * @var int
     */
    public $timeoutSeconds;
    
    /**
     * connectTimeoutSeconds
     *
     * @var int
     */
    public $connectTimeoutSeconds;
    
    /**
     * requestHeaders
     *
     * @var array
     */
    public $requestHeaders;

    /**
     * username
     *
     * @var mixed
     */
    protected $username;
    
    /**
     * password
     *
     * @var mixed
     */
    protected $password;

    /**
     * api key if external service uses key authentication
     *
     * @var mixed
     */
    protected $apiKey;

    /**
     * config file name in /app/config for the external service
     *
     * @var mixed
     */
    public $configKey;

    /**
     * mainProcessKey for audit
     *
     * @var string
     */
    public $mainProcess;

    /**
     * subProcessKey for audit
     *
     * @var string
     */
    public $subProcess;
    
    /**
     * the content type that your application is expecting in response to your request
     *
     * @var string
     */
    public $acceptContentType;
    
    /**
     * correlationToken
     *
     * @var mixed
     */
    public $correlationToken;
    
    /**
     * legCorrelationToken
     *
     * @var mixed
     */
    public $legCorrelationToken;
    
    /**
     * response http status code
     *
     * @var mixed
     */
    public $httpStatus;
    
    /**
     * rawResponse
     *
     * @var mixed
     */
    public $rawResponse;

    public function __construct()
    {
        $this->baseUrl = config()->has($this->configKey . '.web_service.base_url') ? config($this->configKey . '.web_service.base_url') : null;

        $this->connectTimeoutSeconds =  config()->has($this->configKey . '.web_service.connect_timeout_seconds') ? intval(config($this->configKey . '.web_service.connect_timeout_seconds')) : null;

        $this->timeoutSeconds = config()->has($this->configKey . '.web_service.timeout_seconds') ? intval(config($this->configKey . '.web_service.timeout_seconds')) : null;
        
        $this->username = config()->has($this->configKey . '.web_service.username') ? config($this->configKey . '.web_service.username') : null;
        
        $this->password = config()->has($this->configKey . '.web_service.password') ? config($this->configKey . '.web_service.password') : null;

        $this->apiKey = config()->has($this->configKey . '.web_service.api_key') ? config($this->configKey . '.web_service.api_key') : null;
        
        $this->requestHeaders = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];

        $this->acceptContentType = 'application/json';
    }
    
    /**
     * Method setEndpoint
     *
     * @param $endpoint $endpoint [explicite description]
     *
     * @return void
     */
    public function setEndpoint($endpoint)
    {
        $this->endpoint = config()->has($this->configKey . '.web_service.endpoints.' . $endpoint) ? config($this->configKey . '.web_service.endpoints.' . $endpoint) : null;
    }
    
    /**
     * Send api request
     *
     * @param string $correlationToken
     * @param array $body
     *
     * @return mixed
     */
    abstract public function sendServiceRequest($correlationToken = null, $body = []);
    
    /**
     * Process api response
     *
     * @param mixed $response
     * @param array $extra
     *
     * @return mixed
     */
    abstract public function processServiceResponse($response = null, $extra = []);
    
    /**
     * Process general api error response
     * If the api has a common structure for error response, this method should be implemented
     *
     * @param mixed $response [explicite description]
     * @param array $extra [explicite description]
     *
     * @return mixed
     */
    abstract public function processGeneralError($response = null, $extra = []);
}
