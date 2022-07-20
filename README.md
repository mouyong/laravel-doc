# laravel-doc

用于生成 swagger 2.0 文档的扩展包。可根据配置自动同步 yapi 文档。可开启 apifox 文档定时同步功能 

[![Sponsor me](https://github.com/mouyong/mouyong/blob/master/sponsor-me-button-s.svg?raw=true)](https://github.com/sponsors/mouyong)


## 安装

```shell
$ composer require mouyong/laravel-doc -vvv
```

## 使用

### 1. 配置文件修改

```php
// yapi.php
base_url 是 yapi 的 url.
project_id 是 yapi 的项目 id
token 是项目的 token

openapi 可以配置与 openapi 的路由信息。以及是否生成 openapi 文档。

openapi 文档的访问路由，默认是 /openapi
```

### 2. 单元测试与文档生成

文档生成目录：`storage/app/yapi/`

使用示例：

`tests/TestCase`
```php
<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use MouYong\LaravelDoc\Traits\YapiTrait; // here

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    use YapiTrait; // here
}

```

```php
<?php

namespace Tests\Feature\Tenant;

use App\Models\Tenant;
use Cblink\YApiDoc\YapiDTO;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\Feature\Tenant\Traits\AdminUserTrait;
use Tests\TestCase;

class OemTest extends TestCase
{
    use AdminUserTrait;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_get_tenant_oem_info()
    {
        $response = $this->getJson($this->tenantApi('/api/oem-info', false));

         // 断言接口响应数据格式
        $this->assertSuccess($response, [
            'api',
            'tenant_id',
            'doctor_name',
            'hospital_name',
            'department_name',
            'doctor_username',
            'domains',
        ]);

        // 生成 yapi 文档 与 openapi 2.0 文档
        $this->yapi($response, new YapiDTO([
            // 可以同时生成到多个 yapi 项目
            'project' => ['default'],
            // api 名称
            'name' => '获取租户 oem 信息',
            // api 分类
            'category' => '系统',
            'params' => [],
            'desc' => '',
            'request' =>[
                // 这里是字段含义
                'trans' => [
                ],
                // 这里是非必填字段
                'except' => [],
            ],
            'response' =>[
                // 这里是字段含义
                'trans' => [
                    'api' => '租户 api 地址',
                    'tenant_id' => '租户 id',
                    'doctor_name' => '医生名',
                    'hospital_name' => '医院名',
                    'department_name' => '科室名',
                    'doctor_username' => '医生用户名',
                    'domains' => '租户域名',
                ],
                // 这里是非必填字段
                'except' => [],
            ],
        ]));
    }
}

```


### 3. 同步文档

生成文档：`./vendor/bin/phpunit tests/Yapi/YapiTest.php`

1. 文档
- 可根据需要删除 `storage/app/yapi` 后重新生成文档
- 可通过 `$this->mapDesc` 将 `XxxModel` 中的常量转为文档中的释义
- 文档会根据内容 `md5` 进行版本化。避免重复生成与上传
- 可根据需要，结合自动化流程完成接口文档的自动化构建

2. `yapi`
- 运行生成文档后，会自动同步到 `yapi` 平台
- 可在配置文件中关闭同步 `yapi` 平台

3. `apifox`
- 在 `apifox` 项目中可开启定时同步，格式为 `openapi/swagger`
- `openapi` 版本为 `2.0`，`yapi` 平台文档导入暂不支持 `openapi 3.0`


## 问答

欢迎提交 `issue` 与 `PR`

如有使用疑问，可邮件联系我


## :heart: Sponsor me 

[![Sponsor me](https://github.com/mouyong/mouyong/blob/master/sponsor-me.svg?raw=true)](https://github.com/sponsors/mouyong)

如果你喜欢我的项目并想支持它，[点击这里 :heart:](https://github.com/sponsors/mouyong)


## 开源协议

MIT
