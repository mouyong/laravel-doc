<?php

namespace MouYong\LaravelDoc\Jobs;

use Illuminate\Support\Arr;
use Cblink\YApi\YApiRequest;

class YapiJobs extends \Cblink\YApiDoc\YapiJobs
{
    public function upload($project, $config, $swagger)
    {
        $swaggerContent = json_encode($swagger, 448, 512);

        if (Arr::get($this->config, 'openapi.enable')) {
            file_put_contents(
                Arr::get($this->config, 'openapi.path', public_path('openapi.json')),
                $swaggerContent
            );
        }

        if (Arr::get($this->config, 'enable')) {
            $yapi = new YApiRequest(Arr::get($this->config, 'base_url'));

            $yapi->setConfig($config['id'], $config['token'])
                ->importData($swaggerContent, Arr::get($this->config, 'merge', 'normal'));
        }

        $this->line(sprintf("%s 成功更新 %s 个文档!", $project, count($swagger['paths'])));
    }
}