<?php

namespace MouYong\LaravelDoc\Jobs;

use Illuminate\Support\Arr;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Cache;
use ZhenMu\Support\Traits\Clientable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use ZhenMu\Support\Traits\DefaultClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ApifoxJobs  implements ShouldQueue, \ArrayAccess, \IteratorAggregate, \Countable
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    use Clientable;
    use DefaultClient;

    public $config = [];

    public function __construct(array $config = [])
    {
        $this->config = empty($config) ? config('yapi', []) : $config;
    }

    public function handle()
    {
        if (!Arr::get($this->config, 'apifox.enable', false)) {
            return;
        }

        $result = $this->upload();

        $this->line(sprintf("Apifox 同步数据成功 \n\t新增 %d\n\t修改 %d\n\t忽略 %d\n\t错误 %d\n\t",
            $result['data.apiCollection.item.createCount'],
            $result['data.apiCollection.item.updateCount'],
            $result['data.apiCollection.item.ignoreCount'],
            $result['data.apiCollection.item.errorCount']
        ));

        $url = 'https://www.apifox.cn/web/project/'.Arr::get($this->config, 'apifox.project_id', 0);
        $this->line("Apifox 当前只支持 web 端、客户端触发同步。请前往后台->项目概览，点击立即导入按钮，完成接口的导入，前往 $url");
    }

    public function isErrorResponse(array $data): bool
    {
        if (!empty($data['message'])) {
            return true;
        }
        
        return $data['success'] != true;
    }

    public function handleErrorResponse(?string $content = null, array $data = [])
    {
        $message = '同步出现错误 '.json_encode($data, 448);

        $this->line($message);

        throw new \RuntimeException($message);
    }

    public function getBaseUri(): ?string
    {
        return 'https://api.apifox.cn';
    }

    public function login(string $account, string $password, ?int $projectId = null)
    {
        $projectId = Arr::get($this->config, 'apifox.project_id', $projectId);

        $cacheKey = sprintf('apifox_token_%s_%s', $account, $projectId);
        $cacheTime = now()->addMinutes(55);

        $token = Cache::remember($cacheKey, $cacheTime, function () use ($account, $password) {
            $result = $this->post('/api/v1/login', [
                'json' => [
                    'account' => $account,
                    'password' => $password,
                ],
            ]);

            return $result['data']['accessToken'];
        });

        if (!$token) {
            Cache::pull($cacheKey);
        }

        return $token;
    }

    public function getToken()
    {
        $token = $this->login(
            Arr::get($this->config, 'apifox.account'), 
            Arr::get($this->config, 'apifox.password')
        );

        return $token;
    }

    public function getHeaders()
    {
        return [
            'authorization' => $this->getToken(),
            'x-project-id' => Arr::get($this->config, 'apifox.project_id', null),
            'content-type' => 'application/x-www-form-urlencoded',
        ];
    }

    public function getAutoImportOption()
    {
        $result = $this->get('/api/v1/auto-import-settings?locale=zh-CN', [
            'headers' => $this->getHeaders(),
            'form_params' => [
                'data' => $this->convertCurrentApis(),
            ],
        ]);

        // dd($result['data']);
        return $result['data'];
    }

    public function upload()
    {
        $option = $this->getAutoImportOption();

        return $this->post('/api/v1/import-data?locale=zh-CN', [
            'headers' => $this->getHeaders(),
            'form_params' => [
                'data' => $this->convertCurrentApis(),
                'apiOverwriteMode' => $option['apiOverwriteMode'],
                'schemaOverwriteMode' => $option['schemaOverwriteMode'],
                'docOverwriteMode' => $option['docOverwriteMode'],
                'apiFolderId' => $option['apiFolderId'],
                'schemaFolderId' => $option['schemaFolderId'],
                'importFullPath' => $option['importFullPath'],
                'autoImport' => true,
                'autoImportId' => $option['id'],
            ],
        ]);
    }

    public function getCurrentApis()
    {
        $result = $this->get('/api/v1/api-details?locale=zh-CN', [
            'headers' => $this->getHeaders(),
        ]);

        return $result['data'];
    }

    public function convertCurrentApis()
    {
        $result = json_decode('{
            "apifoxProject": "1.1.0",
            "httpCollection": [],
            "socketCollection": [],
            "docCollection": [],
            "dataSchemaCollection": [],
            "environments": [],
            "hasFullPath": false,
            "contentMimeTypes": []
        }', true) ?? [];

        $data = $this->getCurrentApis();

        // 将当前 api 按 tag 分组
        $currentApis = [];
        foreach ($data as $item) {
            foreach ($item['tags'] as $tag) {
                $currentApis[$tag][] = $item;
            }
        }

        $apis = [];
        foreach ($currentApis as $tag => $tagApiGroups) {
            $apiItem = [];
            $apiItem['name'] = $tag;
            $apiItem['children'] = [];

            $apiItem['items'] = [];
            foreach ($tagApiGroups as $tagApiGroupItem) {
                $tagApiItem = [];
                $tagApiItem['name'] = $tagApiGroupItem['name'];
                $tagApiItem['description'] = $tagApiGroupItem['description'];
                $tagApiItem['tags'] = $tagApiGroupItem['tags'];
                $tagApiItem['path'] = $tagApiGroupItem['path'];
                $tagApiItem['method'] = $tagApiGroupItem['method'];
                $tagApiItem['parameters'] = $tagApiGroupItem['parameters'];
                $tagApiItem['commonParameters'] = $tagApiGroupItem['commonParameters'];

                $tagApiItem['responses'] = [];
                foreach ($tagApiGroupItem['responses'] as $key => $response) {
                    $tagApiItem['responses'][$key]['id'] = $key + 1;
                    $tagApiItem['responses'][$key]['name'] = $response['name'];
                    $tagApiItem['responses'][$key]['code'] = $response['code'];
                    $tagApiItem['responses'][$key]['contentType'] = $response['contentType'];

                    $jsonSchema = $response['jsonSchema'];
                    unset($jsonSchema['x-apifox-orders']);
                    if (!empty($jsonSchema['properties']['data']['x-apifox-orders'])) {
                        unset($jsonSchema['properties']['data']['x-apifox-orders']);
                    }
                    $tagApiItem['responses'][$key]['jsonSchema'] = $jsonSchema;
                }

                $tagApiItem['responseExamples'] = $tagApiGroupItem['responseExamples'];
                $tagApiItem['requestBody'] = $tagApiGroupItem['requestBody'];

                $tagApiItem['cases'] = [];
                $casesItem = [];
                $casesItem['name'] = '成功';
                $casesItem['parameters'] = [
                    'path' => [],
                    'query' => [],
                    'cookie' => [],
                    'header' => [],
                ];
                $casesItem['requestBody'] = [
                    'parameters' => [],
                ];
                $casesItem['responseId'] = strval(1);

                $tagApiItem['cases'][0] = $casesItem;
                $tagApiItem['customApiFields'] = $tagApiGroupItem['customApiFields'];

                $apiItem['items'][] = $tagApiItem;
            }

            $apis[] = $apiItem;
        }

        $result['httpCollection'] = $apis;

        return json_encode($result, 320);
    }

    public function line($message)
    {
        dump($message);
    }
}
