<?php
/**
 * Created by PhpStorm.
 * User: 39260
 * Date: 2020/3/28
 * Time: 19:40
 */
defined('BASEPATH') or exit('No direct script access allowed');

if (!function_exists('test_func')) {
    function test_func()
    {
        return __FILE__;
    }
}