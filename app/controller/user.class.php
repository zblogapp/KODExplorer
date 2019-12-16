<?php
/*
* @link http://kodcloud.com/
* @author warlee | e-mail:kodcloud@qq.com
* @copyright warlee 2014.(Shanghai)Co.,Ltd
* @license http://kodcloud.com/tools/license/license.txt
*/

class user extends Controller{
	private $user;  //用户相关信息
	private $auth;  //用户所属组权限
	private $notCheck;
	function __construct(){
		parent::__construct();

		//php5.4 bug;需要重新读取一次
		@session_start();
		@session_write_close();
		global $zbp;
		if (!$zbp->CheckRights('root')) {$zbp->ShowError(6);die();}
		$this->loginFirst();
		$member = systemMember::loadData();
		$user = $member->get('name', 'admin');
		$this->user = &$user;
		$_SESSION['kodUser'] = &$user;
		$_SESSION['kodLogin'] = 1;
		//不需要判断的action
		$this->notCheckST = array('share','debug');
		$this->notCheckACT = array(
			'loginFirst','login','logout','loginSubmit',
			'checkCode','publicLink','qrcode','sso');

		$this->notCheckApp = array();//'pluginApp.to'
		if(!$this->user){
			$this->notCheckApp = array('pluginApp.to','api.view');
		}
		$this->config['forceWap'] = is_wap() && (!isset($_COOKIE['forceWap']) || $_COOKIE['forceWap'] == '1');
		if( isset($_GET['forceWap']) ){
			$this->config['forceWap'] = $_GET['forceWap'];
		}
	}

	public function bindHook(){
		$this->loadModel('Plugin')->init();
	}

	/**
	 * 登录状态检测;并初始化数据状态
	 */
	public function loginCheck(){
		// CSRF-TOKEN更新后同步;关闭X-CSRF-TOKEN的httpOnly
		if( ACT == 'commonJs' && isset($_SESSION['X-CSRF-TOKEN'])){
			$this->_setCsrfToken();
		}
		if(in_array(ST,$this->notCheckST)) return;//不需要判断的控制器
		if(in_array(ACT,$this->notCheckACT))   return;//不需要判断的action
		if(in_array(ST.'.'.ACT,$this->notCheckApp))   return;//不需要判断的对应入口

		if($this->user){
			$user = systemMember::getInfo($this->user['userID']);
			$this->_loginSuccess($user);
			return;
		}
	}
	private function _setCsrfToken(){
		setcookie_header('X-CSRF-TOKEN',$_SESSION['X-CSRF-TOKEN'], time()+3600*24*100);
	}

	private function _loginSuccess($user){
		$this->user = $user;
		if(!$user){//false
			show_tips('[Error Code:1001] user data error!');
		}else if(!$user['path']){//服务器管理后立即生效
			$this->login("Your 'path' is empty,please install again！");
		}else if($user['status'] == 0){
			$this->login(LNG('login_error_user_not_use'));
		}else if($user['role']==''){
			$this->login(LNG('login_error_role'));
		}
		define('USER',USER_PATH.$this->user['path'].'/');//utf-8
		define('USER_TEMP',USER.'data/temp/');
		define('USER_RECYCLE',USER.'recycle_kod/');

		@session_start();//re start
		$_SESSION['kodUser']= $user;
		@session_write_close();
		if (!file_exists(iconv_system(USER))) {
			$this->login("User/".get_path_this(USER)." ".LNG('not_exists'));
		}
		$user_home = user_home_path($this->user);//utf-8
		define('HOME_PATH',$user_home);
		if ($this->user['role'] == '1') {
			define('MYHOME',$user_home);
			define('HOME','');
			$GLOBALS['webRoot'] = WEB_ROOT;//服务器目录
			$GLOBALS['isRoot'] = 1;
		}else{
			define('HOME',$user_home);
			define('MYHOME','/');
			$GLOBALS['webRoot'] = '';//从服务器开始到用户目录
			$GLOBALS['isRoot'] = 0;
		}
		$desktop = $this->config['settingSystem']['desktopFolder'];
		if(isset($this->config['settingSystemDefault']['desktopFolder'])){
			$desktop = $this->config['settingSystemDefault']['desktopFolder'];
		}
		define('DESKTOP_FOLDER',$desktop);
		$this->config['user']  = FileCache::load(USER.'data/config.php');

		if(!is_array($this->config['user'])){
			$this->config['user'] = array();
		}
		foreach($this->config['settingDefault'] as $key=>$val){
			if(!isset($this->config['user'][$key]) ){
				$this->config['user'][$key] = $val;
			}
		}
	}

	/**
	 * 共享kod登陆并跳转
	 * check: 校验方式:userID|userName|roleID|roleName|groupID|groupName,为空则所有登陆用户
	 * value: 对应的值
	 * link : 登陆后的跳转链接
	 */
	public function sso(){
		$result = false;
		$error  = "未登录!";
		if(!isset($_SESSION) || $_SESSION['kodLogin'] != 1){//避免session不可写导致循环跳转
			$this->login($error);
		}
		$user = $_SESSION['kodUser'];
		//admin 或者不填则允许所有kod用户登陆
		if( $user['role'] == '1' ||
			!isset($this->in['check']) ||
			!isset($this->in['value']) ){
			$result = true;
		}

		$checkValue = false;
		switch ($this->in['check']) {
			case 'userID':$checkValue = $user['userID'];break;
			case 'userName':$checkValue = $user['name'];break;
			case 'roleID':$checkValue = $user['role'];break;
			case 'roleName':
				$role = systemRole::getInfo($user['role']);
				$checkValue = $role['name'];
				break;
			case 'groupID':
				$checkValue = array_keys($user['groupInfo']);
				break;
			case 'groupName':
				$checkValue = array();
				foreach ($user['groupInfo'] as $groupID=>$val){
					$item = systemGroup::getInfo($groupID);
					$checkValue[] = $item['name'];
				}
				break;
			default:break;
		}
		if(!$result && $checkValue != false){
			if( (is_string($checkValue) && $checkValue == $this->in['value']) ||
				(is_array($checkValue)  && in_array($this->in['value'],$checkValue))
				){
				$result = true;
			}else{
				$error = $this->in['check'].' 没有权限, 配置权限需要为: "'.$this->in['value'].'"';
			}
		}
		if($result){
			include(LIB_DIR.'api/sso.class.php');
			SSO::sessionSet($this->in['app']);
			header('location:'.$this->in['link']);
			exit;
		}
		$this->login($error);
	}
	public function accessToken(){
		if($_SESSION['kodLogin'] === true){
			show_json(access_token_get(),true);
		}else{
			show_json('not login!',false);
		}
	}

	//临时文件访问
	public function publicLink(){
		$pass = $this->config['settingSystem']['systemPassword'];
		$fid = $this->in['fid'];//$this->in['fid']  第三项
		$path = Mcrypt::decode($fid,$pass);
		if (strlen($path) == 0) {
			show_json(LNG('error'),false);
		}
		$download = isset($_GET['download']);
		$filename = isset($_GET['downFilename'])?$_GET['downFilename']:false;
		file_put_out($path,$download,$filename);
	}
	public function commonJs(){
		$out = ob_get_clean();
		$basicPath = BASIC_PATH;
		$userPath  = USER_PATH;
		$groupPath = GROUP_PATH;
		if (!$GLOBALS['isRoot']) {//对非root用户隐藏地址
			$basicPath = '/';
			$userPath  = '/';
			$groupPath = '/';
		}
		$theConfig = array(
			'environment'	=> STATIC_JS,
			'lang'          => I18n::getType(),
			'systemOS'		=> $this->config['systemOS'],
			'isRoot'        => $GLOBALS['isRoot'],
			'userID'        => $this->user['userID'],
			'webRoot'       => $GLOBALS['webRoot'],
			'webHost'       => HOST,
			'appHost'       => APP_HOST,
			'staticPath'    => STATIC_PATH,
			'appIndex'  	=> $_SERVER['SCRIPT_NAME'],
			'basicPath'     => $basicPath,
			'userPath'      => $userPath,
			'groupPath'     => $groupPath,

			'myhome'        => MYHOME,
			'myDesktop'		=> MYHOME.DESKTOP_FOLDER.'/',
			'settings'		=> array(
				'updloadChunkSize'	=> file_upload_size(),
				'updloadThreads'	=> $this->config['settings']['updloadThreads'],
				'updloadBindary'	=> $this->config['settings']['updloadBindary'],
				'uploadCheckChunk'	=> $this->config['settings']['uploadCheckChunk'],

				'paramRewrite'		=> $this->config['settings']['paramRewrite'],
				'pluginServer'		=> $this->config['settings']['pluginServer'],
				'appType'			=> $this->config['settings']['appType']
			),
			'phpVersion'	=> PHP_VERSION,
			'version'       => KOD_VERSION,
			'kodID'			=> md5(BASIC_PATH.$this->config['settingSystem']['systemPassword']),
			'jsonData'   	=> "",
			'selfShare'		=> systemMember::userShareList($this->user['userID']),
			'userConfig' 	=> $this->config['user'],
			'accessToken'	=> access_token_get(),
			'versionEnv'	=> base64_encode(serverInfo()),

			//虚拟目录
			'KOD_GROUP_PATH'		=>	KOD_GROUP_PATH,
			'KOD_GROUP_SHARE'		=>	KOD_GROUP_SHARE,
			'KOD_USER_SELF'			=>  KOD_USER_SELF,
			'KOD_USER_SHARE'		=>	KOD_USER_SHARE,
			'KOD_USER_RECYCLE'		=>	KOD_USER_RECYCLE,
			'KOD_USER_FAV'			=>	KOD_USER_FAV,
			'KOD_GROUP_ROOT_SELF'	=>	KOD_GROUP_ROOT_SELF,
			'KOD_GROUP_ROOT_ALL'	=>	KOD_GROUP_ROOT_ALL,
			'ST'					=> $this->in['st'],
			'ACT'					=> $this->in['act'],
		);

		if(isset($this->config['settingSystem']['versionHash'])){
			$theConfig['versionHash'] = $this->config['settingSystem']['versionHash'];
			$theConfig['versionHashUser'] = $this->config['settingSystem']['versionHashUser'];
		}
		if (!isset($GLOBALS['auth'])) {
			$GLOBALS['auth'] = array();
		}

		$useTime = mtime() - $GLOBALS['config']['appStartTime'];
		header("Content-Type: application/javascript; charset=utf-8");
		echo 'if(typeof(kodReady)=="undefined"){kodReady=[];}';
		Hook::trigger('user.commonJs.insert',$this->in['st'],$this->in['act']);
		echo ';AUTH='.json_encode($GLOBALS['auth']).';';
		echo 'G='.json_encode($theConfig).';';

		$lang = json_encode_force(I18n::getAll());
		if(!$lang){
			$lang = '{}';
		}
		echo 'LNG='.$lang.';G.useTime='.$useTime.';';
	}
	public function appConfig(){
		$theConfig = array(
			'lang'          => I18n::getType(),
			'isRoot'        => $GLOBALS['isRoot'],
			'userID'        => $this->user['userID'],
			'myhome'        => MYHOME,
			'settings'		=> array(
				'updloadChunkSize'	=> file_upload_size(),
				'updloadThreads'	=> $this->config['settings']['updloadThreads'],
				'uploadCheckChunk'	=> $this->config['settings']['uploadCheckChunk'],
			),
			'version'       => KOD_VERSION,
			// 'userConfig' 	=> $this->config['user'],
		);
		show_json($theConfig);
	}

	/**
	 * 登录view
	 */
	public function login($msg = ''){
	}

	/**
	 * 首次登录
	 */
	public function loginFirst(){
		if (!file_exists(USER_SYSTEM.'install.lock')) {
			touch(USER_SYSTEM.'install.lock');
			if(!isset($this->in['password'])){
				$this->in['password'] = 'admin';
			}
			$root = '1';
			$sql  = systemMember::loadData();
			$user = array(//重置admin
				'name'			=> 'admin',
				'path'			=> "admin",
				'password'		=> md5($this->in['password']),
				'userID'		=> $root,
				'role'			=> '1',
				'config'		=> array('sizeMax'=>'0','sizeUse'=>1024),
				'groupInfo'		=> array('1'=>'write'),
				'createTime'	=> time(),
				'status'		=> 1,
			);
			$sql->set($root,$user);
			$member = new systemMember();
			$member->initInstall();
		}
	}
	/**
	 * 退出处理
	 */
	public function logout(){
		global $zbp;
		Redirect($zbp->host . 'zb_system/admin/');
		session_start();
		user_logout();
	}

	/**
	 * 登录数据提交处理；登陆跳转：
	 *
	 * 自动登陆：index.php?user/loginSubmit&name=guest&password=guest
	 * 登陆自动跳转：index.php?user/login&link=http://baidu.com
	 * api登陆:index.php?user/loginSubmit&login_token=ZGVtbw==|da9926fdab0c7c32ab2c329255046793
	 */
	public function loginSubmit(){
	}

	//登陆token
	private function _makeLoginToken($userInfo){
		//$ua = $_SERVER['HTTP_USER_AGENT'];
		$system_pass = $this->config['settingSystem']['systemPassword'];
		return md5($userInfo['password'].$system_pass.$userInfo['userID']);
	}
	public function versionInstall(){
	}

	/**
	 * 修改密码
	 */
	public function changePassword(){
	}

	//CSRF 防护；cookie设置：CSRF-TOKEN；header:提交X-CSRF-TOKEN
	//referer检测
	private function _checkCSRF(){
		$not_check = array('user.commonJs','pluginApp.index');
		if( !$this->config['settingSystem']['csrfProtect'] ||
			isset($this->in['accessToken']) ||
			in_array(ST.'.'.ACT, $not_check)
			){
			return;
		}
		if( !isset($_SERVER['HTTP_X_CSRF_TOKEN'])||
			$_SERVER['HTTP_X_CSRF_TOKEN'] != $_SESSION['X-CSRF-TOKEN']
		){
			show_json('token_error',false);
		}
	}
	private function _checkKey($key){
		if(!isset($this->in[$key])){
			return '';
		}
		return is_string($this->in[$key])? rawurldecode($this->in[$key]):'';
	}

	private function initAuth(){
		$auth = systemRole::getInfo($this->user['role']);
		//向下版本兼容处理
		//未定义；新版本首次使用默认开放的功能
		if(!isset($auth['userShare.set'])){
			$auth['userShare.set'] = 1;
		}
		if(!isset($auth['explorer.fileDownload'])){
			$auth['explorer.fileDownload'] = 1;
		}
		//默认扩展功能 等价权限
		$auth['user.commonJs'] = 1;//权限数据配置后输出到前端
		$auth['explorer.pathDeleteRecycle'] = $auth['explorer.pathDelete'];
		$auth['explorer.pathCopyDrag']      = $auth['explorer.pathCuteDrag'];

		$auth['explorer.officeSave']        = $auth['editor.fileSave'];
		$auth['explorer.fileSave']          = $auth['editor.fileSave'];
		$auth['explorer.imageRotate']       = $auth['editor.fileSave'];
		$auth['explorer.fileDownloadRemove']= $auth['explorer.fileDownload'];
		$auth['explorer.zipDownload']       = $auth['explorer.fileDownload'];
		$auth['explorer.unzipList']         = $auth['explorer.unzip'];

		//彻底禁止下载；文件获取
		//$auth['explorer.fileProxy']         = $auth['explorer.fileDownload'];
		//$auth['editor.fileGet']             = $auth['explorer.fileDownload'];
		//$auth['explorer.officeView']        = $auth['explorer.fileDownload'];
		$auth['editor.fileGet']     = 1;
		$auth['explorer.fileProxy'] = 1;
		$auth['explorer.officeView']= 1;
		$auth['explorer.pathList']  = 1;
		$auth['explorer.treeList']  = 1;
		if(!$auth['explorer.fileDownload']){
			$auth['explorer.zip'] = 0;
		}
		$auth['userShare.del'] = $auth['userShare.set'];
		$GLOBALS['auth'] = $auth;
	}

	/**
	 * 权限验证；统一入口检验
	 */
	public function authCheck(){
		$this->initAuth();
		if(in_array(ST,$this->notCheckST)) return;//不需要判断的控制器
		if(in_array(ACT,$this->notCheckACT))   return;//不需要判断的action
		if(in_array(ST.'.'.ACT,$this->notCheckApp)) return;//不需要判断的对应入口
		if (!array_key_exists(ST,$this->config['roleSetting']) ) return;
		if (!in_array(ACT,$this->config['roleSetting'][ST])) return;//输出处理过的权限
		$this->_checkCSRF();
		if (isset($GLOBALS['isRoot']) && $GLOBALS['isRoot'] == 1) return;

		if ($GLOBALS['auth'][ST.'.'.ACT] != 1) show_json(LNG('no_permission'),false);
		//扩展名限制：新建文件&上传文件&重命名文件&保存文件&zip解压文件
		$check_arr = array(
			'mkfile'    =>  $this->_checkKey('path'),
			'pathRname' =>  $this->_checkKey('rnameTo'),
			'fileUpload'=>  $_FILES['file']['name']? $_FILES['file']['name']:$GLOBALS['in']['name'],
			'fileSave'  =>  $this->_checkKey('path')
		);
		if (array_key_exists(ACT,$check_arr) && !checkExt($check_arr[ACT])){
			show_json(LNG('no_permission_ext'),false);
		}
	}
	public function checkCode() {
		session_start();//re start
		$captcha = new MyCaptcha(4);
		$_SESSION['checkCode'] = $captcha->getString();
	}

	public function qrcode(){
		$url = $this->in['url'];
		if(function_exists('imagecolorallocate')){
			ob_get_clean();
			QRcode::png($this->in['url']);
		}else{
			header('location: http://qr.topscan.com/api.php?text='.rawurlencode($url));
		}
	}
}
