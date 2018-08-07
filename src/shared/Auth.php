<?php
class Auth {
	// property declaration
	private $netid = '';

	public function __construct($connectionInfo) {
		$this->connectionInfo = $connectionInfo;
	}

	public function init($redirect) {
		switch ($_SERVER['REMOTE_ADDR']) {
			case '128.174.3.40':
				$_SERVER['HTTP_SM_USER'] = 'asharda2';
				break;
			case '128.174.3.47':
				$_SERVER['HTTP_SM_USER'] = 'jlagrou2';
				break;
			case '128.174.3.46':
				$_SERVER['HTTP_SM_USER'] = 'bbevers2';
				break;
			/*case '128.174.3.49':
				$_SERVER['HTTP_SM_USER']='akuhl2';
				break;*/
			case '128.174.3.52':
				$_SERVER['HTTP_SM_USER'] = 'spwashi2';
				break;
			default:
				break;
		}


		if (isset($_SERVER['HTTP_SM_USER']) && $_SERVER['HTTP_SM_USER'] != '') {
			echo $_SERVER['HTTP_SM_USER'];
			$this->netid = $_SERVER["HTTP_SM_USER"];
		} else {
			//Go to Siteminder Folder and Authenticate
			header('Location: /grantors/admin/auth.php?redirect=' . $redirect);
		}
		if ($this->netid == '')
			return false;
		else
			return $this->netid;
	}
	/**
	 * @param $redirect
	 * @return bool|ExtStaff
	 */
	public function AuthExtStaff($redirect) {
		if (!$this->init($redirect)) return false;

		$extStaff = new ExtStaff('NetID', $this->netid);

		return $extStaff->TermDate != null
			? false
			: $extStaff;
	}
	function Logout() {
		session_unset();
		header('Location: https://auth.uillinois.edu/logout/sm_logout.fcc');
	}

	function AuthShib(){
		print 'Location: https://'.$_SERVER['SERVER_NAME'].'/Shibboleth.sso/Login';
	}
}
?>