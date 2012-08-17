<?php

/**
 * 継承先のコントローラのアクションメソッドで
 * return Responseする処理を省略し、afterメソッドで
 * 自動でreturn Responseするためのコントローラ
 *
 * Responseの引数にはコントローラ名とアクション名に
 * 対応したViewModelオブジェクトが渡される
 */
abstract class Base_Controller_AutoResponse extends \Controller {

	/**
	 * 複数のアクションで同じViewModelを利用する場合はここでその対応付けを行っておく
	 * キーがアクション、値がViewModel
	 *
	 * @var array
	 */
	protected $view_model_mapping = array();

	/**
	 * Responseオブジェクトがなければリクエストを元にViewModelオブジェクトからResponseを作成
	 *
	 * @param Fuel\Core\Response $response
	 * @return Fuel\Core\Response
	 */
	public function after($response = null)
	{
		$response = parent::after($response);
		if ($response !== null) return $response;

		$controllers = explode('\\', \Request::active()->controller);
		$view = array(array_pop($controllers), \Request::active()->action);
		$view[0] = strtolower(preg_replace('/^Controller_/', '', $view[0]));
		if (isset($this->view_model_mapping[$view[1]]))
		{
			$view[1] = $this->view_model_mapping[$view[1]];
		}

		$view_model = (count($controllers)) ? $controllers[0] . '\\' : '';
		$view_model .= 'View_'.ucfirst($view[0].'_'.ucfirst($view[1]));
		if (class_exists($view_model))
		{
			$response = \Response::forge(\ViewModel::forge(join('/', $view)));
		}
		return $response;
	}
}
