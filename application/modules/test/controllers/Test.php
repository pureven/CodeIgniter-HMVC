<?php
/**
 * Created by PhpStorm.
 * User: 39260
 * Date: 2020/3/28
 * Time: 19:46
 */

class Test extends MX_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('test/func_test');
        $this->load->helper('test/test');
    }

    public function index()
    {
        $test_lib = $this->func_test->test_lib();
        $test_helper = test_func();
        $test_conf = TEST_SUCCESS;
        var_dump($test_lib, $test_helper, $test_conf);
    }

    public function test()
    {
        var_dump(__FUNCTION__);
    }
}