<?php

namespace Tests\Yapi;

use Tests\TestCase;
use Cblink\YApiDoc\YapiJobs;
use MouYong\LaravelDoc\Jobs\ApifoxJobs;

class YapiTest extends TestCase
{
    /**
     * 上传yapi文件
     */
    public function test_upload()
    {
        dispatch_sync(new YapiJobs());
        dispatch_sync(new ApifoxJobs());

        $this->assertTrue(true);
    }
}