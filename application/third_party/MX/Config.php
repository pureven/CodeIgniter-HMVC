<?php (defined('BASEPATH')) OR exit('No direct script access allowed');

/**
 * Modular Extensions - HMVC
 *
 * Adapted from the CodeIgniter Core Classes
 * @link	http://codeigniter.com
 *
 * Description:
 * This library extends the CodeIgniter CI_Config class
 * and adds features allowing use of modules and the HMVC design pattern.
 *
 * Install this file as application/third_party/MX/Config.php
 *
 * @copyright	Copyright (c) 2015 Wiredesignz
 * @version 	5.5
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 **/
class MX_Config extends CI_Config 
{
    /**
     * 加载指定配置文件，(设置好自动加载配置后测试，比如:$autoload['config'] = array('config'))
     * @param string $file
     * @param bool $use_sections
     * @param bool $fail_gracefully
     * @param string $_module
     * @return bool|null|string
     */
	public function load($file = '', $use_sections = FALSE, $fail_gracefully = FALSE, $_module = '') 
	{
	    // 如果已加载了就返回
		if (in_array($file, $this->is_loaded, TRUE)) return $this->item($file);

		$_module OR $_module = CI::$APP->router->fetch_module();
		list($path, $file) = Modules::find($file, $_module, 'config/');// 模块配置文件路径、文件名

        // 没找到则说明模块下没有了，去APPPATH下找
		if ($path === FALSE)
		{
			parent::load($file, $use_sections, $fail_gracefully);					
			return $this->item($file);
		}  
		
		if ($config = Modules::load_file($file, $path, 'config'))
		{
			/* 引用父类的config变量，表示所有已加载的配置项 */
			$current_config =& $this->config;

			if ($use_sections === TRUE)	
			{
				if (isset($current_config[$file])) 
				{
				    // 如果加载过则合并，这里用的array_merge函数表示后面的值将覆盖前面设置的值
					$current_config[$file] = array_merge($current_config[$file], $config);
				} 
				else 
				{
					$current_config[$file] = $config;
				}
				
			} 
			else 
			{
				$current_config = array_merge($current_config, $config);
			}

			$this->is_loaded[] = $file;
			unset($config);
			return $this->item($file);
		}
	}
}