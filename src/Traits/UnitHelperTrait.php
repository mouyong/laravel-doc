<?php

namespace MouYong\LaravelDoc\Traits;

use Illuminate\Testing\TestResponse;

trait UnitHelperTrait
{
    /**
     * @param TestResponse $response
     * @param array        $struct
     * @param bool         $metaCheck
     *
     * @return TestResponse
     */
    protected function assertSuccess(TestResponse $response, array $struct = [], $metaCheck = false)
    {
        $response->assertStatus(200)->assertJson(['err_code' => 200]);

        // 如果需要验证结构体，并且不是列表数据的结构体
        if ($struct && !empty($response->getContent())) {
            $response->assertJsonStructure([
                'data' => $struct,
            ]);
        }

        if ($metaCheck) {
            $response->assertJsonStructure([
                'meta' => [
                    'total', 'current_page', 'per_page',
                ],
            ]);
        }

        return $response;
    }

    /**
     * @param TestResponse $response
     * @param $errCode
     *
     * @return TestResponse
     */
    public function assertFailed(TestResponse $response, $errCode): TestResponse
    {
        $response->assertStatus(200)->assertJson(['err_code' => $errCode]);

        return $response;
    }

    /**
     * 模型常量转换成注释
     *
     * @param $name
     * @param array $array
     *
     * @return string|string[]
     */
    public function mapDesc($name, array $array = [])
    {
        $return = urldecode(http_build_query($array));
        $return = str_replace('=', ' : ', $return);
        $return = str_replace('&', ' ， ', $return);

        return sprintf('%s %s', $name, $return);
    }
}
