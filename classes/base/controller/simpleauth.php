<?php

/**
 * SimpleAuthによる認証を実行するためのコントローラ
 *
 * SimpleAuthで認証を行うためには次の準備が必要
 * 0, oilコマンドでセッションテーブル作成
 *    php oil refine session:create
 * 1. APPPATH/config/config.phpのalways_load['packages']にormとauthを追加
 * 2. PKGPATH/auth/config/auth.phpをAPPPATH/config/auth.phpにコピー
 * 3. APPPATH/config/auth.phpのsaltをランダムな文字列に書き換え
 * 4. oilコマンドでモデルを生成
 *    php oil generate model users username:varchar[50] password:string group:int email:string last_login:int login_hash:string profile_fields:text
 * 5. APPPATH/migration/***_create_uses.phpのupメソッドの末尾に次の式を追記
 *    \DBUtil::create_index('users', 'username', 'username', 'unique');
 * 6. oilコマンドでマイグレーションを実行
 *    php oil refine migrate
 * 7. oilコマンドのコンソールから次のコマンドを実行してログインユーザを作成
 *    \Auth::create_user('ユーザ名', 'パスワード', 'メールアドレス');
 */
abstract class Base_Controller_SimpleAuth extends \Base_Controller_AutoResponse
{
	/**
	 * 継承先のコントローラで認証を行うか
	 *
	 * @var bool
	 */
	protected $auth_flg = false;
	/**
	 * 認証を行わないアクションメソッドのリスト
	 *
	 * 継承先のコントローラにログイン処理のアクションがあるならば、
	 * それを入れておかないと、リダイレクトで無限ループしてしまう
	 *
	 * @var array
	 */
	protected $no_auth_actions = array('login');
	/**
	 * 継承先のコントローラでログイン処理を実施するか
	 *
	 * @var bool
	 */
	protected $login_flg = false;
	/**
	 * ログインページにリダイレクトするためのURL
	 *
	 * @var string
	 */
	protected $url_login = 'login';
	/**
	 * ログイン成功時にリダイレクトするURL
	 *
	 * @var string
	 */
	protected $redirect_login = '/';
	/**
	 * 継承先のコントローラでログアウト処理を実施するか
	 *
	 * @var bool
	 */
	protected $logout_flg = false;
	/**
	 * ログアウト時にリダイレクトするURL
	 *
	 * @var string
	 */
	protected $redirect_logout = '/';
	/**
	 * ログイン用フォーム/バリデーションオブジェクト
	 *
	 * @var string
	 */
	protected $login_form = null;

	/**
	 * 継承先のコントローラが認証を必要とするならば
	 * アクションメソッド処理前にログインチェックを行う
	 */
	public function before()
	{
		parent::before();

		if ($this->auth_flg && !in_array(\Fuel\Core\Request::active()->action, $this->no_auth_actions))
		{
			if (\Auth::check())
			{
				// Assign current_user to the instance so controllers can use it
				$this->current_user = \Model_User::find_by_username(\Auth::get_screen_name());

				// Set a global variable so views can use it
				\Fuel\Core\View::set_global('current_user', $this->current_user);
			}
			else
			{
				\Fuel\Core\Response::redirect($this->url_login);
			}
		}
	}

	/**
	 * デフォルトのログイン処理アクション
	 *
	 * @throws Fuel\Core\HttpNotFoundException
	 */
	public function action_login()
	{
		if (!$this->login_flg)
		{
			throw new \Fuel\Core\HttpNotFoundException;
		}

		// Already logged in
		\Auth::check() and \Fuel\Core\Response::redirect($this->redirect_login);

		$this->login_form = \Fieldset::forge('login_form');
		$val = $this->login_form->validation();
		$val->add('email', 'Email or Username')->add_rule('required');
		$val->add('password', 'Password')->add_rule('required');
		// ログイン処理をバリデーションルールに定義
		$val->add('login', '', array('type' => 'submit', 'value' => 'login'))
			->add_rule(array('login' => function($v) {
			// 既にエラーがあれば、なにもしない
			if ($this->login_form->show_errors()) return true;
			$auth = \Auth::instance();

			// check the credentials. This assumes that you have the previous table created
			if ($auth->login(\Fuel\Core\Input::post('email'), \Fuel\Core\Input::post('password')))
			{
				\Fuel\Core\Response::redirect($this->redirect_login);
			}
			return false;
		}));
		$val->set_message('login', 'login error');

		if (\Fuel\Core\Input::method() == 'POST' && !$val->run())
		{
			\Fuel\Core\View::set_global('login_error', 'Fail');
		}
		\Fuel\Core\View::set_global('val', $this->login_form);
	}

	/**
	 * ログインフォームのページが存在していなければ、デフォルトのログインページのレスポンスを作成
	 *
	 * @param Fuel\Core\Response $response
	 * @return Fuel\Core\Response
	 */
	public function after($response = null)
	{
		$response = parent::after($response);
		if ($response !== null) return $response;

		if ($this->login_form)
		{
			$this->login_form->repopulate();
			return \Fuel\Core\Response::forge(sprintf('%s%s', $this->login_form->show_errors(), $this->login_form));
		}
	}

	/**
	 * ログアウト処理を実行
	 *
	 * @throws Fuel\Core\HttpNotFoundException
	 */
	public function action_logout()
	{
		if (!$this->logout_flg)
		{
			throw new \Fuel\Core\HttpNotFoundException;
		}
		\Auth::logout();
		\Fuel\Core\Response::redirect($this->redirect_logout);
	}
}
