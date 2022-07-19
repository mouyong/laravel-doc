<?php

namespace MouYong\LaravelDoc\Traits;

use Cblink\YApiDoc\YApi;
use Cblink\YApiDoc\YapiDTO;

trait YapiTrait
{
    use RequestTrait;
    use UnitHelperTrait;
    
    /**
     * @param $response
     * @param YapiDTO $dto
     *
     * @return YApi
     */
    public function yapi($response, YapiDTO $dto)
    {
        $callable = function () use ($response, $dto) {
            $config = config('yapi');

            $yapi = new YApi($this->app['request'], $response, $dto, $config);
            $yapi->make();

            return $yapi;
        };

        if (function_exists('central')) {
            return central($callable);
        }

        return $callable();
    }
}
