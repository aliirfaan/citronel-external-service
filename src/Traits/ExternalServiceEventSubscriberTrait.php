<?php

namespace aliirfaan\CitronelExternalService\Traits;

use Illuminate\Database\Eloquent\Model;

trait ExternalServiceEventSubscriberTrait
{
    /**
     * config file name in /app/config for the external service
     *
     * @var mixed
     */
    public $configKey;

    public function handleRequestSent(object $event): void
    {
        try{
            $eventData = $event->eventData;

            $saveData = $this->requestSaveData($eventData);
            
            $this->logRequestModel::create($saveData);

        } catch (\Illuminate\Database\QueryException $e) {
            report($e);

        } catch (\Exception $e) {
            report($e);

        }
    }

    public function handleResponseReceived(object $event): void
    {
        try {
            $eventData = $event->eventData;

            $saveData = $this->responseSaveData($eventData);

            $this->logResponseModel::create($saveData);

        } catch (\Illuminate\Database\QueryException $e) {
            report($e);

        } catch (\Exception $e) {
            report($e);

        }
    }

    /**
     * Method requestSaveData
     *
     * @param array $eventData request fields to log
     *
     * @return array
     */
    public function requestSaveData($eventData)
    {
        $eventResultIntegration = $eventData['result']['request']['integration'];
        return $this->getFillableAttributes(new $this->logRequestModel, $eventResultIntegration);
    }

    /**
     * Method responseSaveData
     *
     * @param array $eventData response fields to log
     *
     * @return array
     */
    public function responseSaveData($eventData)
    {
        $eventResultIntegration = $eventData['result']['response']['integration'];

        return $this->getFillableAttributes(new $this->logResponseModel, $eventResultIntegration);
    }

    /**
     * Get fillable property of a model and check if columns exist as keys in an array.
     *
     * @param Model $model
     * @param array $data
     * @return array
     */
    public function getFillableAttributes(Model $model, array $data): array
    {
        $fillableAttributes = $model->getFillable();
        $result = [];

        foreach ($fillableAttributes as $attribute) {
            $result[$attribute] = array_key_exists($attribute, $data) ? $data[$attribute] : null;
        }

        return $result;
    }

    public function subscribe($events)
    {
        $events->listen(
            $this->logRequestEvent,
            [get_class($this),
            'handleRequestSent']
        );

        $events->listen(
            $this->logResponseEvent,
            [get_class($this),
            'handleResponseReceived']
        );
    }
}
