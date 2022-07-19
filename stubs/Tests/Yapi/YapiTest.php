<?php

namespace Tests\Yapi;

use Tests\TestCase;
use Cblink\YApiDoc\YapiJobs;

class YapiTest extends TestCase
{
    /**
     * 上传yapi文件
     */
    public function test_upload()
    {
        dispatch_sync(new YapiJobs(config('yapi')));

        $this->assertTrue(true);
    }
}