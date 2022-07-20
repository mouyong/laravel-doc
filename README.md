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

`routes/tenant.php`
```
// 定义路由，实际开发时替换为正式的 method、uri、controller、action
Route::any('/api/oem-info', [\MouYong\LaravelDoc\Http\Controllers\OpenapiController::class, 'example']);
Route::get('/api/patients', [\MouYong\LaravelDoc\Http\Controllers\OpenapiController::class, 'example']);
Route::post('/api/patients', [\MouYong\LaravelDoc\Http\Controllers\OpenapiController::class, 'example']);
```

`tests/TestCase.php`
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

`app/Models/Patient.php`
```
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    /**
     * 微信小程序性别合法值
     * 
     * @see https://developers.weixin.qq.com/miniprogram/dev/api/open-api/user-info/UserInfo.html#number-gender
     */
    const GENDER_UNKNOWN = 0;
    const GENDER_MAN = 1;
    const GENDER_FEMAN = 2;
    const GENDER_MAP = [
        Patient::GENDER_UNKNOWN => '未知',
        Patient::GENDER_MAN => '男',
        Patient::GENDER_FEMAN => '女',
    ];

    use HasFactory;
}

```

`tests/Feature/Tenant/OemTest.php`
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
     * Get tenant oem info.
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

`tests/Feature/Tenant/PatientTest.php`
```
<?php

namespace Tests\Feature\Tenant;

use Tests\TestCase;
use App\Models\Patient;
use Cblink\YApiDoc\YapiDTO;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\Feature\Tenant\Traits\AdminUserTrait;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PatientTest extends TestCase
{
    use AdminUserTrait;
    
    public function test_admin_add_patient()
    {
        $this->adminLogin();
        
        $response = $this->postJson($this->tenantApi('/api/patients'), [
            'name' => '张三',
            'gender' => Patient::GENDER_MAN,
            'mobile' => '13333333333',
            'address' => '广东省深圳市宝安区 xx 花园',
            'highest_education' => '',
            'nation' => '',
            'birthday' => '',
            'native_place' => '',
        ]);

        // $this->assertSuccess($response, [
        //     'patient_id',
        //     'user_id',
        // ]);

        $this->yapi($response, new YapiDTO([
            'project' => ['default'],
            'name' => '新增患者',
            'category' => '患者',
            'params' => [],
            'desc' => '',
            'request' =>[
                'trans' => [
                    'name' => '患者姓名',
                    'gender' => $this->mapDesc('患者性别', Patient::GENDER_MAP),
                    'mobile' => '患者手机号',
                    'address' => '患者居住地址，完整的省市区及详细地址。如：广东省深圳市宝安区',
                    'highest_education' => '学历枚举值',
                    'nation' => '民族',
                    'birthday' => '患者生日，格式：2022-02-02 02:02:02',
                    'native_place' => '患者籍贯，身份证的所属地，如广东、北京',
                ],
                'except' => [],
            ],
            'response' =>[
                'trans' => [
                    'patient_id' => 'patients.id 患者 ID',
                    'user_id' => 'users.id 用户 ID',
                ],
                'except' => [],
            ],
        ]));
    }

    public function test_admin_get_patient_list()
    {
        $this->adminLogin();
        
        $response = $this->getJson($this->tenantApi('/api/patients'), [
            'keyword' => '',
        ]);

        // $this->assertSuccess($response, [
        //     'patient_id',
        //     'user_id',
        // ]);

        $this->yapi($response, new YapiDTO([
            'project' => ['default'],
            'name' => '获取患者列表',
            'category' => '患者',
            'params' => [],
            'desc' => '',
            'request' =>[
                'trans' => [
                    'keyword' => '搜索关键词，搜索字段：patients.name',
                ],
                'except' => [],
            ],
            'response' =>[
                'trans' => [
                    'data.*.patient_id' => 'patients.id 患者 ID',
                    'data.*.user_id' => 'users.id 用户 ID',
                    'data.*.user_name' => 'users.name',
                    'data.*.user_mobile' => 'users.mobile 脱敏手机号，如：133****3333',
                    'data.*.user_gender' => $this->mapDesc('users.gender 用户性别', Patient::GENDER_MAP),
                    'data.*.user_gender_desc' => '性别文字描述，如：男 具体描述值见 users.gender',
                ],
                'except' => [],
            ],
        ]));
    }

    public function test_admin_delete_patient()
    {
        $this->markAsRisky();
    }

    public function test_admin_add_patient_family_info()
    {
        $this->markAsRisky();
    }

    public function test_admin_get_patient_family_list()
    {
        $this->markAsRisky();
    }

    public function test_admin_get_patient_family_detail()
    {
        $this->markAsRisky();
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
- 接口无响应数据时，响应数据采用 `YapiDTO` 的 `response.trans` 生成

2. `yapi`
- 运行生成文档后，会自动同步到 `yapi` 平台
- 可在配置文件中关闭同步 `yapi` 平台

3. `apifox`
- 在 `apifox` 项目中可开启定时同步，格式为 `openapi/swagger`
- `openapi` 版本为 `2.0`，`yapi` 平台文档导入暂不支持 `openapi 3.0`


## 问答

欢迎提交 `issue` 与 `PR`

如有使用疑问，可邮件联系我


## :heart: 赞助

[![Sponsor me](https://github.com/mouyong/mouyong/blob/master/sponsor-me.svg?raw=true)](https://github.com/sponsors/mouyong)

如果你喜欢我的项目并想支持它，[点击这里 :heart:](https://github.com/sponsors/mouyong)


## 开源协议

MIT
