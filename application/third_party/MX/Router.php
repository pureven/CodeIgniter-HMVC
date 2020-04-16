<?php (defined('BASEPATH')) OR exit('No direct script access allowed');

/* load the MX core module class */
require dirname(__FILE__).'/Modules.php';

/**
 * Modular Extensions - HMVC
 *
 * Adapted from the CodeIgniter Core Classes
 * @link	http://codeigniter.com
 *
 * Description:
 * This library extends the CodeIgniter router class.
 *
 * Install this file as application/third_party/MX/Router.php
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
class MX_Router extends CI_Router
{
	public $module;
	private $located = 0;

    /**
     * 获取模块名，只有定位到模块才有
     * @return mixed
     */
	public function fetch_module()
	{
		return $this->module;
	}

    /**
     * 重写_set_request方法
     * @param array $segments
     */
	protected function _set_request($segments = array())
	{
		if ($this->translate_uri_dashes === TRUE)
		{
		    // 路由的value可能包含三部分 模块、控制器、方法
			foreach(range(0, 2) as $v)
			{
				isset($segments[$v]) && $segments[$v] = str_replace('-', '_', $segments[$v]);
			}
		}

		$segments = $this->locate($segments);

		if($this->located == -1)
		{
			$this->_set_404override_controller();
			return;
		}

		if(empty($segments))
		{
			$this->_set_default_controller();
			return;
		}
		
		$this->set_class($segments[0]);
		
		if (isset($segments[1]))
		{
			$this->set_method($segments[1]);
		}
		else
		{
			$segments[1] = 'index';
		}
       
		array_unshift($segments, NULL);
		unset($segments[0]);
		$this->uri->rsegments = $segments;
	}
	
	protected function _set_404override_controller()
	{
		$this->_set_module_path($this->routes['404_override']);
	}

	protected function _set_default_controller()
	{
		if (empty($this->directory))
		{
			/* set the default controller module path */
			$this->_set_module_path($this->default_controller);
		}

		parent::_set_default_controller();
		
		if(empty($this->class))
		{
			$this->_set_404override_controller();
		}
	}

    /**
     *  定位控制器
     * @param $segments welcome/null/null | test/test/index
     * @return array|void
     */
	public function locate($segments)
	{
		$this->located = 0;
		$ext = $this->config->item('controller_suffix').EXT;// 这里说明HMVC支持控制器后缀可配置

		/* $segments[0]表示模块，这里是去模块下找路由配置文件，找到就用该模块下的路由配置 */
		if (isset($segments[0]) && $routes = Modules::parse_routes($segments[0], implode('/', $segments)))
		{
		    // $routes为[$segments[0]，$route[$segments[0]]]
			$segments = $routes;
		}

		/* get the segments array elements */
		list($module, $directory, $controller) = array_pad($segments, 3, NULL);

		/* check modules */
		foreach (Modules::$locations as $location => $offset)
		{
			/* 模块是否存在 */
			if (is_dir($source = $location.$module.'/controllers/'))
			{
				$this->module = $module;
				$this->directory = $offset.$module.'/controllers/';

				/* 子控制器是否存在 */
				if($directory)
				{
					/* controller下是否有子目录，也就是子控制器 */
                    // 比如: APPPATH . 'modules/test/controllers/test/'
					if(is_dir($source.$directory.'/'))
					{	
						$source .= $directory.'/';
						$this->directory .= $directory.'/';

						/* 子控制器是否存在 */
						if($controller)
						{
						    // APPPATH . 'modules/test/controllers/test/Test.php'
							if(is_file($source.ucfirst($controller).$ext))
							{
								$this->located = 3;// 模块 控制器 子控制器
								return array_slice($segments, 2);
							}
							else $this->located = -1;
						}
					}
					else
					    // APPPATH . 'modules/test/controllers/Test.php'
					if(is_file($source.ucfirst($directory).$ext))
					{
						$this->located = 2;// 模块 控制器(directory)
						return array_slice($segments, 1);
					}
					else $this->located = -1;
				}

				/* module controller exists? */
                // APPPATH . 'modules/test/controller/Test.php
				if(is_file($source.ucfirst($module).$ext))
				{// 模块 控制器(module)
					$this->located = 1;
					return $segments;
				}
			}
		}

		if( ! empty($this->directory)) return;

		/* application sub-directory controller exists? */
		if($directory)
		{
		    // 模块作为控制器子目录 APPPATH . 'controllers/test/Test.php'
			if(is_file(APPPATH.'controllers/'.$module.'/'.ucfirst($directory).$ext))
			{
				$this->directory = $module.'/';
				return array_slice($segments, 1);
			}

			/* application sub-sub-directory controller exists? */
			if($controller)
			{
			    // APPPATH . 'controllers/test/test/Test.php'
				if(is_file(APPPATH.'controllers/'.$module.'/'.$directory.'/'.ucfirst($controller).$ext))
				{
					$this->directory = $module.'/'.$directory.'/';
					return array_slice($segments, 2);
				}
			}
		}

		/* application controllers sub-directory exists? */
        // APPPATH . 'controllers/test/'
		if (is_dir(APPPATH.'controllers/'.$module.'/'))
		{
			$this->directory = $module.'/';
			return array_slice($segments, 1);
		}

		/* application controller exists? */
        // APPPATH . 'controllers/Test.php'
		if (is_file(APPPATH.'controllers/'.ucfirst($module).$ext))
		{
			return $segments;
		}
		
		$this->located = -1;
	}

	/* 默认控制器和404重定向控制器调用此方法 */
	protected function _set_module_path(&$_route)
	{
		if ( ! empty($_route))
		{
			// 将$_route解析到$module $directory $class $method，并返回解析值的个数
            // 比如默认控制器，$_route = 'welcome';
			$sgs = sscanf($_route, '%[^/]/%[^/]/%[^/]/%s', $module, $directory, $class, $method);

			// set the module/controller directory location if found 没找到传空
			if ($this->locate(array($module, $directory, $class)))
			{
				//reset to class/method
				switch ($sgs)
				{
					case 1:	$_route = $module.'/index';// welcome/index
						break;
					case 2: $_route = ($this->located < 2) ? $module.'/'.$directory : $directory.'/index';
						break;
					case 3: $_route = ($this->located == 2) ? $directory.'/'.$class : $class.'/index';
						break;
					case 4: $_route = ($this->located == 3) ? $class.'/'.$method : $method.'/index';
						break;
				}
			}
		}
	}

	public function set_class($class)
	{
	    // HMVC模式下控制器类可自定义后缀，如果设置了后缀则补上
		$suffix = $this->config->item('controller_suffix');
		if (strpos($class, $suffix) === FALSE)
		{
			$class .= $suffix;
		}
		parent::set_class($class);
	}
}	