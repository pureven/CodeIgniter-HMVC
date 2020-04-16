<?php
/**
 * Created by PhpStorm.
 * User: 39260
 * Date: 2020/3/29
 * Time: 18:18
 */
defined('BASEPATH') or exit('No direct script access allowed');

$route['test']                  = 'test/test/index';
$route['test/([a-z_]+)']       = 'test/test/$1';