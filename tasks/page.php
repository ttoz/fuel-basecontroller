<?php

namespace Fuel\Tasks;

/**
 * Create Page Task
 *
 *
 * @package     Fuel
 * @version		1.1
 * @author		ttoz
 * @license     MIT License
 *
 * Usage:
 * php oil r page         = help
 * php oil r page:create  = create new page.
 */

class Page
{

	// default function if no command is selected. Provided user with menu
	public static function run()
	{
		static::help();
	}

	/**
	 * 新規ページ関連ファイル生成処理
	 *
	 * @static
	 *
	 */
	public static function create()
	{
		$args = func_get_args();
		$controller = array_shift($args);
		$controller ?: 'welcome';
		$controllers = explode(':', $controller);
		$controller = array_pop($controllers);
		$module = (count($controllers)) ? array_pop($controllers) : null;
		$methods = $args;

		$option = \Cli::prompt('Select template engine. ex:php[p], smarty[s]');

		$engine = $ext = null;
		switch ($option)
		{
			case 'php':
				echo 'You should do `oil g controller`.';
				exit;
			case 's':
			case 'smarty':
				$engine = 'smarty';
				$ext = 'smarty';
				break;
			default:
				echo 'Task is canceled.';
		}

		!$engine and exit;

		$option = \Cli::prompt('You would like to auth check?', array('y','n'));
		$auth_flg = ($option == 'y') ? true : false;

		$controller_path = APPPATH.'classes'.DS.'controller'.DS.$controller.'.php';
		$viewmodel_dir = APPPATH.'classes'.DS.'view'.DS.$controller.DS;
		$view_dir = APPPATH.'views'.DS.$controller.DS;
		$view_base_dir = APPPATH.'views'.DS.'_base_'.DS;

		$cfg = \Config::load('config.php');
		$namespace = null;
		foreach ($cfg['module_paths'] as $path)
		{
			if ($module && (is_dir($path.$module) || (mkdir($path.$module) && chmod($path.$module, 0777))))
			{
				$controller_path = $path.$module.DS.'classes'.DS;
				!is_dir($controller_path) && mkdir($controller_path) && chmod($controller_path, 0777);
				!is_dir($controller_path.DS.'model') && mkdir($controller_path.DS.'model') && chmod($controller_path.DS.'model', 0777);
				$controller_path .= 'controller'.DS;
				!is_dir($controller_path) && mkdir($controller_path) && chmod($controller_path, 0777);
				$controller_path .= $controller.'.php';
				$viewmodel_dir = $path.$module.DS.'classes'.DS.'view'.DS;
				!is_dir($viewmodel_dir) && mkdir($viewmodel_dir) && chmod($viewmodel_dir, 0777);
				$viewmodel_dir .= $controller.DS;
				$view_dir = $path.$module.DS.'views'.DS;
				!is_dir($view_dir) && mkdir($view_dir) && chmod($view_dir, 0777);
				$view_dir .= $controller.DS;
				$view_base_dir = $path.$module.DS.'views'.DS;
				!is_dir($view_base_dir) && mkdir($view_base_dir) && chmod($view_base_dir, 0777);
				$view_base_dir .= '_base_'.DS;
				$namespace = $module;
				break;
			}
		}

		!is_dir($viewmodel_dir) && mkdir($viewmodel_dir) && chmod($viewmodel_dir, 0777);
		!is_dir($view_dir) && mkdir($view_dir) && chmod($view_dir, 0777);
		!is_dir($view_base_dir) && mkdir($view_base_dir) && chmod($view_base_dir, 0777);

		$path = array();
		$path['controller'] = array(
			'tmpl' => dirname(__DIR__).DS.'views'.DS.$engine.DS.'controller.tmpl',
			'path' => $controller_path,
			'option' => $auth_flg
		);
		$path['baseviewmodel'] = array(
			'tmpl' => dirname(__DIR__).DS.'views'.DS.$engine.DS.'baseviewmodel.tmpl',
			'path' => rtrim($viewmodel_dir, DS ).'.php',
			'option' => null
		);
		$path['baseview'] = array(
			'tmpl' => dirname(__DIR__).DS.'views'.DS.$engine.DS.'baseview.tmpl',
			//'path' => rtrim($view_dir, DS).'.'.$ext,
			'path' => dirname($view_dir).DS.'_base_'.DS.$controller.'.'.$ext,
			'option' => null
		);
		$path['basebaseview'] = array(
			'tmpl' => dirname(__DIR__).DS.'views'.DS.$engine.DS.'_base_.tmpl',
			'path' => dirname($view_dir).DS.'_base_.'.$ext,
			'option' => null
		);
		foreach ($methods as $method)
		{
			$path['action_'.$method.'_action'] = array(
				'tmpl' => $method,
				'path' => $controller_path,
				'option' => null
			);
			$path['action_'.$method.'_viewmodel'] = array(
				'tmpl' => dirname(__DIR__).DS.'views'.DS.$engine.DS.'viewmodel.tmpl',
				'path' => $viewmodel_dir.$method.'.php',
				'option' => null
			);
			$path['action_'.$method.'_view'] = array(
				'tmpl' => dirname(__DIR__).DS.'views'.DS.$engine.DS.'view.tmpl',
				'path' => $view_dir.$method.'.'.$ext,
				'option' => $controller
			);
		}

		foreach ($path as $type => $info)
		{
			$type_ = $type;
			if (strpos($type, 'action_') === 0)
			{
				$types = explode('_', $type);
				$type = array_pop($types);
				$type_ = ($type == 'action') ? join('_', $types) : $type;
			}
			$method = ($type == 'action') ? 'add_action' : 'create_' . str_replace('base', '', $type);
			if (file_exists($info['path']) && $type != 'action')
			{
				echo "Already exists $type_: {$info['path']}\n";
			}
			elseif (static::$method($info['tmpl'], $info['path'], $info['option'], $namespace))
			{
				if ($type == 'action')
				{
					echo "Adding $type_ method: $controller_path\n";
				}
				else
				{
					echo "Creating $type: {$info['path']}\n";
				}
			}
		}

	}

	/**
	 * コントローラのひな形を生成
	 *
	 * @static
	 * @param $tmpl_path
	 * @param $path
	 * @param $auth_flg
	 * @param $namespace
	 * @return bool
	 */
	private static function create_controller($tmpl_path, $path, $auth_flg, $namespace)
	{
		$param = array();
		$paths = explode(DS, $path);
		$param['class'] = ucfirst(preg_replace('/\.php$/', '', array_pop($paths)));
		$param['extends_class'] = $auth_flg ? '\\Base_Controller_SimpleAuth' : '\\Base_Controller_AutoResponse';
		$param['namespace'] = ($namespace) ? 'namespace '.ucfirst($namespace).';' : '';

		return static::write_file(file_get_contents($tmpl_path), $path, $param);
	}

	/**
	 * コントローラにアクションメソッドを追加
	 *
	 * @static
	 * @param $method
	 * @param $path
	 * @param $option
	 * @param $namespace
	 * @return bool
	 */
	private static function add_action($method, $path, $option, $namespace)
	{
		$src = file_get_contents($path);
		$str = 'public function action_'.strtolower($method);
		if (preg_match('/'.$str.'[ (]+/', $src))
		{
			echo "Already exists action_{$method} method: $path\n";
			return false;
		}

		$param = array();
		$param['action'] = <<<EOS
	/**
	 * $method page
	 *
	 * @access public
	 * @return mixed Response|null
	 */
	$str()
	{
	}

###ACTION###
EOS;
		return static::write_file($src, $path, $param);
	}

	/**
	 * ビューモデルのひな形を生成
	 *
	 * @static
	 * @param $tmpl_path
	 * @param $path
	 * @param $option
	 * @param $namespace
	 * @return bool
	 */
	private static function create_viewmodel($tmpl_path, $path, $option, $namespace)
	{
		$param = array();
		$paths = explode(DS, $path);
		$param['class'] = ucfirst(preg_replace('/\.php$/', '', array_pop($paths)));
		$param['controller'] = ucfirst(array_pop($paths));
		$param['namespace'] = ($namespace) ? 'namespace '.ucfirst($namespace).';' : '';

		return static::write_file(file_get_contents($tmpl_path), $path, $param);
	}

	/**
	 * ビューのひな形を生成
	 *
	 * @static
	 * @param $tmpl_path
	 * @param $path
	 * @param $option
	 * @param $namespace
	 * @return bool
	 */
	private static function create_view($tmpl_path, $path, $option, $namespace)
	{
		$param = array();
		$param['controller'] = ($option) ? $option : '_base_';

		return static::write_file(file_get_contents($tmpl_path), $path, $param);
	}

	private static function write_file($src, $path, $param)
	{
		foreach ($param as $k => $v)
		{
			$mark = '###'.strtoupper($k).'###';
			$src = preg_replace("/$mark/", $v, $src);
		}
		$src = preg_replace('/###[^#]###/', '', $src);
		$res = file_put_contents($path, $src);
		return ($res > 0) ? true : false;
	}

	/**
	 * help
	 */
	public static function help()
	{
		echo <<<HELP
			Usage:
				php oil refine page:create

			Description:
				The page task will create new page skeltons.
				If controller under the module, your controller named `module:controller`.

			Examples:
				php oil r page:create controller method1 method2..

HELP;
	}
}

/* End of file tasks/page.php */
