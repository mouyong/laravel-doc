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
        $result = $this->upload();

        $this->line('更新文档数量 '.$result['data.apiCollection.item.updateCount']);
    }

    public function isErrorResponse(array $data): bool
    {
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

    public function upload()
    {
        $token = $this->login(
            Arr::get($this->config, 'apifox.account'), 
            Arr::get($this->config, 'apifox.password')
        );

        if (!$token) {
            $this->line('获取 token 失败');
            return null;
        }

        $projectId = Arr::get($this->config, 'apifox.project_id', null);

        return $this->post('/api/v1/import-data', [
            'headers' => [
                'authorization' => $token,
                'x-project-id' => $projectId,
            ],
        ]);
    }

    public function line($message)
    {
        dump($message);
    }
}
