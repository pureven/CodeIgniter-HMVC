<?php (defined('BASEPATH')) OR exit('No direct script access allowed');

// 该文件在MX_Router文件中加载
// 默认扩展名.php
(defined('EXT')) OR define('EXT', '.php');

// 这里的$CFG在CI类中定义： if ( ! $CFG instanceof MX_Config) $CFG = new MX_Config;
global $CFG;

// 这里的modules_locations可在config.php中定义，如果没有定义就用默认的
// 默认：[APPPATH . 'modules/' => '../modules/']
is_array(Modules::$locations = $CFG->item('modules_locations')) OR Modules::$locations = array(
	APPPATH.'modules/' => '../modules/',
);

/* PHP5 spl_autoload */
// 将Modules::autoload()静态方法注册到SPL __autoload函数队列中作为__autoload的实现
// 当发现待使用的类未加载时就使用该方法加载对应的类文件
spl_autoload_register('Modules::autoload');

/**
 * Modular Extensions - HMVC
 *
 * Adapted from the CodeIgniter Core Classes
 * @link	http://codeigniter.com
 *
 * Description:
 * This library provides functions to load and instantiate controllers
 * and module controllers allowing use of modules and the HMVC design pattern.
 *
 * Install this file as application/third_party/MX/Modules.php
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
class Modules
{
    // $routes用于存放模块下是否包含路由文件
	public static $routes, $registry, $locations;
	
	/**
	* Run a module controller method
	* Output from module is buffered and returned.
	**/
	public static function run($module) 
	{	
		$method = 'index';
		
		if(($pos = strrpos($module, '/')) != FALSE) 
		{
			$method = substr($module, $pos + 1);		
			$module = substr($module, 0, $pos);
		}

		if($class = self::load($module)) 
		{	
			if (method_exists($class, $method))	{
				ob_start();
				$args = func_get_args();
				$output = call_user_func_array(array($class, $method), array_slice($args, 1));
				$buffer = ob_get_clean();
				return ($output !== NULL) ? $output : $buffer;
			}
		}
		
		log_message('error', "Module controller failed to run: {$module}/{$method}");
	}
	
	/** Load a module controller **/
	public static function load($module) 
	{
		(is_array($module)) ? list($module, $params) = each($module) : $params = NULL;	
		
		/* get the requested controller class name */
		$alias = strtolower(basename($module));

		/* create or return an existing controller from the registry */
		if ( ! isset(self::$registry[$alias])) 
		{
			/* find the controller */
			list($class) = CI::$APP->router->locate(explode('/', $module));
	
			/* controller cannot be located */
			if (empty($class)) return;
	
			/* set the module directory */
			$path = APPPATH.'controllers/'.CI::$APP->router->directory;
			
			/* load the controller class */
			$class = $class.CI::$APP->config->item('controller_suffix');
			self::load_file(ucfirst($class), $path);
			
			/* create and register the new controller */
			$controller = ucfirst($class);	
			self::$registry[$alias] = new $controller($params);
		}
		
		return self::$registry[$alias];
	}
	
	/** Library base class autoload **/
	public static function autoload($class) 
	{	
		// 这里不加载'CI_'或'MY_'开头的类
		if (strstr($class, 'CI_') OR strstr($class, config_item('subclass_prefix'))) return;

		// HMVC扩展类大都时MX目录下的并且'MX_'开头，这里负责加载'MX_'开头的类文件
		if (strstr($class, 'MX_')) 
		{
		    // 举例：$location = .../application/third_party/MX/Loader.php
			if (is_file($location = dirname(__FILE__).'/'.substr($class, 3).EXT)) 
			{
				include_once $location;
				return;
			}
			show_error('Failed to load MX core class: '.$class);
		}
		
		// 加载'application/core/'目录下的类文件
		if(is_file($location = APPPATH.'core/'.ucfirst($class).EXT)) 
		{
			include_once $location;
			return;
		}		
		
		// 加载'application/libraries/'目录下的类文件
		if(is_file($location = APPPATH.'libraries/'.ucfirst($class).EXT)) 
		{
			include_once $location;
			return;
		}		
	}

    /**
     * 加载模块文件
     * @param $file            - routes
     * @param $path            - APPPATH . 'modules/test/config/'
     * @param string $type     - route
     * @param bool $result     - true
     * @return bool
     */
	public static function load_file($file, $path, $type = 'other', $result = TRUE)	
	{
		$file = str_replace(EXT, '', $file);		
		$location = $path.$file.EXT;// $location = APPPATH . 'modules/test/config/routes.php'

		if ($type === 'other')
		{
			if (class_exists($file, FALSE))	
			{
				log_message('debug', "File already loaded: {$location}");				
				return $result;
			}	
			include_once $location;
		} 
		else 
		{
			/* 加载配置文件或语言文件 */
			include $location;

            // 这里的$type可以是config route autoload language
			if ( ! isset($$type) OR ! is_array($$type))				
				show_error("{$location} does not contain a valid {$type} array");

			$result = $$type;
		}
		log_message('debug', "File loaded: {$location}");
		return $result;
	}

    /**
     * 从模块中寻找指定文件，比如从模块配置目录找路由文件
     * @param $file     -   routes
     * @param $module   -   test | welcome
     * @param $base     -   config/
     * @return array
     */
	public static function find($file, $module, $base) 
	{
		$segments = explode('/', $file);

		$file = array_pop($segments);
		// pathinfo($file, PATHINFO_EXTENSION)是从$file中获取扩展名
		$file_ext = (pathinfo($file, PATHINFO_EXTENSION)) ? $file : $file.EXT;// routes.php
		
		$path = ltrim(implode('/', $segments).'/', '/');// 获取子目录
		$module ? $modules[$module] = $path : $modules = array();// $modules['test'] = '';

        // 入参$file如果是test/config/routes就能直接确定module是test了
		if ( ! empty($segments)) 
		{
			$modules[array_shift($segments)] = ltrim(implode('/', $segments).'/','/');
		}	

		// Modules:$locations = [APPPATH . 'modules/' => '../modules/'];
		foreach (Modules::$locations as $location => $offset) 
		{					     // test => ''
			foreach($modules as $module => $subpath) 
			{
			    // G:\CodeIgniter-HMVC\application\modules/welcome/config/
				$fullpath = $location.$module.'/'.$base.$subpath;
				
				if ($base == 'libraries/' OR $base == 'models/')
				{
				    // libraries文件或models文件首字母大写
					if(is_file($fullpath.ucfirst($file_ext))) return array($fullpath, ucfirst($file));
				}
				else
				/* load non-class files */
				if (is_file($fullpath.$file_ext)) return array($fullpath, $file);
			}
		}
		
		return array(FALSE, $file);	
	}

    /**
     * 解析module路由
     * @param $module test | welcome
     * @param $uri    test/test/index | welcome//
     * @return array|void
     */
	public static function parse_routes($module, $uri) 
	{
		/* load the route file */
		if ( ! isset(self::$routes[$module])) 
		{
			if (list($path) = self::find('routes', $module, 'config/'))
			{
			    // 因为welcome模块config目录下没有routes.php文件，所以$path = false
                // test模块 => $path = APPPATH . 'modules/test/config/'
                // 作用： 每一个模块可以包含一个config/routes.php文件，在文件中定义该模块的路由和默认控制器
				$path && self::$routes[$module] = self::load_file('routes', $path, 'route');
			}
		}

		// module为welcome没有在模块下找到配置文件，退出。如果是test则继续。
		if ( ! isset(self::$routes[$module])) return;
			
		/* 解析module路由 */
		foreach (self::$routes[$module] as $key => $val) 
		{
		    // $key中的通配符转换为正则表达式
			$key = str_replace(array(':any', ':num'), array('.+', '[0-9]+'), $key);

			// 执行正则匹配
			if (preg_match('#^'.$key.'$#', $uri)) 
			{
			    // $val中存在'$'或者$key中存在'('则执行一个正则表达式的搜索和替换
				if (strpos($val, '$') !== FALSE AND strpos($key, '(') !== FALSE) 
				{
					$val = preg_replace('#^'.$key.'$#', $val, $uri);
				}
				return explode('/', $module.'/'.$val);
			}
		}
	}
}