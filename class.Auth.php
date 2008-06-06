<?php
/**
  * class.Auth.php
  * @package PHP BTPD Control panel
  */
/**
  * @author Volkov Sergey
  * @version 0.1
  * @package PHP BTPD Control panel
  */

final class Auth {
    private static $instance = null;
    private $user;
    
    private function __construct() {
	session_name('BTPD');
	session_start();
	if (preg_match('/^\w+$/',$_SESSION['user'])) $this->user =& $_SESSION['user'];
    } // __construct

    private function __clone() {}
    function __destruct() {
	session_write_close();
    }
    public static function GetInstance() {
	if (!self::$instance instanceof self) self::$instance =& new self();
	return self::$instance;
    } // GetInstance

    public function user() {
	return $this->user;
    } // user

    public function logoff() {
	unset($_SESSION['user'], $this->user);
	session_destroy();
    } // logoff

    public function login($login, $pass) {
	if (btpdConfig::$users[$login] == md5($pass)) {
	    $_SESSION['user'] = $login;
	    $this->user = $login;
	} else {
	    unset($_SESSION['user'], $this->user);
	}
	return $this->user;
    } // login
} // class Auth

?>