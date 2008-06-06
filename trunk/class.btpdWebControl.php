<?php

/**
  * class.btpdWebControl.php
  * @package PHP BTPD Control panel
  */
/**
  * @author Volkov Sergey
  * @version 0.1
  * @package PHP BTPD Control panel
  */

final class btpdWebControl extends btpdControl {
    private $result;
    private $param;
    private $btpd_home;
    private $download_path;
    private $auth;

    function __construct() {
	parent::__construct(btpdConfig::BTPD_HOME);
	$this->auth = Auth::GetInstance();

	$this->param = array_merge($_GET,$_POST);
	switch($this->param['action']) {
	    case 'stop':
		$this->result .= $this->stop_torrent();
		break;
	    case 'start':
		$this->result .= $this->start_torrent();
		break;
	    case 'del':
		$this->result .= $this->del_torrent();
		break;
	    case 'kcaptcha';
		$this->kcaptcha();
		break;
	    case 'torrent_detals':
		$this->result .= $this->torrent_detals();
		break;
	    case 'log':
		$this->result .= nl2br($this->get_last_log());
		break;
	    case 'add_torrent':
		$this->result .= $this->add_torrent();
		break;
	    case 'login':
		$this->result .= $this->login();
		$this->result .= $this->get_torrents_list();
		$this->result .= $this->add_form();
		break;
	    case 'logoff':
		$this->result .= $this->logoff();
		$this->result .= $this->get_torrents_list();
		$this->result .= $this->add_form();
		break;
	    case 'list':
	    default:
		$this->result .= $this->get_torrents_list();
		$this->result .= $this->add_form();
	}
    } // __construct

    public function result() {
	return $this->result;
    } // result

    public function get_torrents_list() {
	$torrents = $this->btpd_list_torrents();
	if ($torrents['code'] != 0) {
	    $out .= 'Error:' . $this->get_btpd_error($torrents['code']);
	    return $out;
	}
	$out .= '<fieldset title="Current torrent files registered at BTPD"><legend> Current registered torrents at BTPD: </legend>';
	$out .= '<table width="100%" class="sortable" _class_="torrent_list">';
	$out .= '<thead><tr><th>Name</th><th>Have</th><th>Rate UP</th><th>Rate DL</th><th>Total UP</th><th>Peers</th><th>Ratio</th><th>State</th></tr></thead>';
	$rate_up = 0;
	$rate_dl = 0;
	$total_size = 0;
	$total_got = 0;
	$out .= '<tbody>';
	foreach($torrents['result'] as $key=>$val) {
	    $hash = unpack('H*', $val[self::IHASH]);
	    $out .= '<tr>';
	    $out .= '<td class="left"><A href="index.php?action=torrent_detals&id=' . $val[self::NUM] . '">' . $val[self::NAME] . '</a></td>';
	    $out .= '<td class="have">' . $this->format_size($val[self::CGOT]) . ' of ' . $this->format_size($val[self::CSIZE]) . ' (' . (round($val[self::CGOT] * 100 / $val[self::CSIZE],2)) . '%)</td>';
	    $total_got += $val[self::CGOT];
	    $total_size += $val[self::CSIZE];
	    $out .= '<td>' . $this->format_rate($val[self::RATEUP]) . '</td>';
	    $rate_up += $val[self::RATEUP];
	    $out .= '<td>' . $this->format_rate($val[self::RATEDWN]) . '</td>';
	    $rate_dl += $val[self::RATEDWN];
	    $out .= '<td>' . $this->format_size($val[self::TOTUP]) . '</td>';
	    $out .= '<td>' . $val[self::PCOUNT] . '</td>';
	    $out .= '<td>' . round($val[self::TOTUP] / $val[self::CSIZE],2) . '</td>';
	    $out .= '<td class="state_' . $val[self::STATE] . '">' . $this->get_torrent_state($val[self::STATE]) . '</td>';

	    $out .= '<td><A href="index.php?action=stop&id=' . $val[self::NUM] . '">stop</a></td>';
	    $out .= '<td><A href="index.php?action=start&id=' . $val[self::NUM] . '">start</a></td>';
	    $out .= '<td><A href="index.php?action=del&id=' . $val[self::NUM] . '">del</a></td>';
	}
	$out .= '</tbody><tfoot><tr><td align="right">Total:</td><td>' . $this->format_size($total_got) . ' of ' . $this->format_size($total_size) . ' (' . (round($total_got * 100 / $total_size,2)) . '%)</td><td>' . $this->format_rate($rate_up) . '</td><td>' . $this->format_rate($rate_dl) . '</td></tfoot>';
	$out .= '</table></fieldset>';
	return $out;
    } // get_torrents_list

    private function format_size($size) {
	$labels = array(0=>'B', 1=>'KB', 2=>'MB', 3=>'GB');
	foreach($labels as $key=>$val) {
	    if ($size < 1024) break;
	    $size /= 1024.0;
	}
	if ($key == 0) {
	    return $size . $labels[$key];
	} else {
	    return sprintf('%.02f%s', $size , $labels[$key]);
	}
    } // format_size

    private function format_rate($rate) {
	return $this->format_size($rate) . '/s';
    } // format_rate

    public function stop_torrent() {
	if (! $this->auth->user()) return 'Please login first!';
	if (! preg_match('/^\d+$/', $this->param['id'])) return '';
	$res = $this->btpd_stop_torrent($this->param['id']);
	if ($res['code'] != 0) {
	    $out .= 'Error:' . $this->get_btpd_error($res['code']);
	    return $out;
	}
	$out .= 'Torrent Stopped.';
	return $out;
    } // stop_torrent

    public function start_torrent() {
	if (! $this->auth->user()) return 'Please login first!';
	if (! preg_match('/^\d+$/', $this->param['id'])) return '';
	$res = $this->btpd_start_torrent($this->param['id']);
	if ($res['code'] != 0) {
	    $out .= 'Error:' . $this->get_btpd_error($res['code']);
	    return $out;
	}
	$out .= 'Torrent Sarted.';
	return $out;
    } // stop_started

    public function torrent_detals () {
	if (! preg_match('/^\d+$/', $this->param['id'])) return '';
	$res = $this->btpd_list_torrents($this->param['id']);
	if ($res['code'] != 0) {
	    $out .= 'Error:' . $this->get_btpd_error($res['code']);
	    return $out;
	}
	$hash = unpack('H*', $res['result'][0][self::IHASH]);
	$out .= '<table class="torrent_info"><tbody>';
	$out .= '<tr><th colspan="2">Download progress info:</th></td>';
	$out .= '<tr><td>Downloaded bytest:</td><td>' .	$res['result'][0][self::CGOT] . '</td></tr>';
	$out .= '<tr><td>Total size:</td><td>' .	$res['result'][0][self::CSIZE] . '</td></tr>';
	$out .= '<tr><td>Download directory:</td><td>' .$res['result'][0][self::DIR] . '</td></tr>';
	$out .= '<tr><td>Torrent name:</td><td>' .	$res['result'][0][self::NAME] . '</td></tr>';
	$out .= '<tr><td>BTPD ID:</td><td>' .		$res['result'][0][self::NUM] . '</td></tr>';
	$out .= '<tr><td>Hash:</td><td>' .		$hash[1] . '</td></tr>';
	$out .= '<tr><td>Peaces downloaded:</td><td>' .	$res['result'][0][self::PCGOT] . '</td></tr>';
	$out .= '<tr><td>Total peaces:</td><td>' .	$res['result'][0][self::PCCOUNT] . '</td></tr>';
	$out .= '<tr><td>Peaces seen:</td><td>' .	$res['result'][0][self::PCSEEN] . '</td></tr>';
	$out .= '<tr><td>Download rate:</td><td>' .	$res['result'][0][self::RATEDWN] . '</td></tr>';
	$out .= '<tr><td>Upload rate:</td><td>' .	$res['result'][0][self::RATEUP] . '</td></tr>';
	$out .= '<tr><td>Downloaded (this session):</td><td>' .	$res['result'][0][self::SESSDWN] . '</td></tr>';
	$out .= '<tr><td>Uploaded (this session):</td><td>' .	$res['result'][0][self::SESSUP] . '</td></tr>';
	$out .= '<tr><td>Total downloaded:</td><td>' .	$res['result'][0][self::TOTDWN] . '</td></tr>';
	$out .= '<tr><td>Total uploaded:</td><td>' .	$res['result'][0][self::TOTUP] . '</td></tr>';
	$out .= '<tr><td>State:</td><td>' .		$this->get_torrent_state($res['result'][0][self::STATE]) . '</td></tr>';
	$out .= '<tr><td>Peers:</td><td>' .		$res['result'][0][self::PCOUNT] . '</td></tr>';
	$out .= '<tr><td>Error count:</td><td>' .	$res['result'][0][self::TRERR] . '</td></tr>';
	$out .= '<tr><th colspan="2">Torrent file content:</th></td>';
	$torrent = file_get_contents(btpdConfig::BTPD_HOME . '/torrents/' . $hash[1] . '/torrent');
	$bencoder = new BEncodeLib();
	$torrent_info = $bencoder->bdecode($torrent);
	$out .= '<tr><td colspan=2>' . $this->get_torrent_detals($torrent_info) . '</tr></td>';
	$out .= '</tbody></table>';
	return $out;
    } // torrent_detals

    private function get_torrent_detals($param) {
	if (! is_array($param)) return;
	$out .= '<ul>';
	foreach($param as $key => $val) {
	    $out .= '<li><b>' . $key . ':</b>';
	    if (is_array($val)) {
		$out .= $this->get_torrent_detals($val);
	    } else {
		if (preg_match('/announce/', $key) and preg_match('/^http/', $val)) $val = preg_replace('/(.*)(\?.*)/', '$1<i><b>&lt;Query-String in URL stripped for security reason&gt;</b></i>', $val);
		switch($key . '') {
		    case 'pieces':
			$out .= ' <i>skipped binary data</i>';
			break;
		    case 0:
		    default:
			$out .= ' ' . $val;
		}
	    }
	}
	$out .= '</ul>';
	return $out;
    } // get_torrent_detals

    public function add_form() {
	if (! $this->auth->user()) return;
	$out .= '<form action="' . $_SERVER['PHP_SELF'] . '" Method="POST" enctype="multipart/form-data" >';
	$out .= '<input type="hidden" name="MAX_FILE_SIZE" value="30000000" />';
	$out .= '<input type="hidden" name="action" value="add_torrent" />';
	$out .= '<fieldset title="Add new torrent"><legend>Add new torrent</legend>';
	$out .= '<input type="file" name="file" /> Torrent file<br />';
	$out .= '<input type="text" name="name" /> Download directory name<br />';
	$out .= '<input type="checkbox" name="autostart" value="on" id="autostart" /> <label for="autostart">Start download/seeding after registering torrent file at BTPD</label><br />';
	$free_space = disk_free_space(btpdConfig::DOWNLOAD_PATH);
	$out .= 'Download directory free space: ' . $this->format_size($free_space) . '<br />';
	$out .= '<input type="submit" value=" Upload and register new torrent at BTPD " class="submit" />';
	$out .= '</fieldset>';
	$out .= '</form>';
	return $out;
    } // add_form

    public function add_torrent() {
	if (! $this->auth->user()) return 'Please login first!';
	if (! is_uploaded_file($_FILES['file']['tmp_name'])) return 'No torrent file uploaded';
	if (! preg_match('/^[0-9a-zA-Z _:-]/', $this->param['name'])) return 'Invalid name';

	$torrent = file_get_contents($_FILES['file']['tmp_name']);
	$bencoder = new BEncodeLib();
	$torrent_info = $bencoder->bdecode($torrent);
	if (! is_array($torrent_info['info'])) return 'Invalis torrent file - no "info" section.';
	$hash = (sha1($bencoder->bencode($torrent_info['info'])));
	$directory = btpdConfig::DOWNLOAD_PATH . '/download/' . $hash;
	@mkdir(btpdConfig::DOWNLOAD_PATH . '/download/');
	@mkdir($directory);
	if (! is_dir($directory)) return 'Unable create download directory.';
	@symlink($directory, btpdConfig::DOWNLOAD_PATH . '/' . $this->param['name']);
	$result = $this->btpd_add_torrent($torrent, $directory, $this->param['name']);
	if ($result['code'] != 0) {
	    $out .= 'Error:' . $this->get_btpd_error($result['code']);
	    return $out;
	}
	$out .= 'Torrent added.<BR />';
	if ($this->param['autostart'] == 'on') {
	    $result = $this->btpd_start_torrent($result['num']);
		if ($resilt['code'] != 0) {
		$out .= 'Error:' . $this->get_btpd_error($result['code']);
		return $out;
	    }
	    $out .= 'Torrent ' . $result['num'] . ' Sarted.<br />';
	}
	return $out;
    } // add_torrent

    public function del_torrent() {
	if (! $this->auth->user()) return 'Please login first!';
	if (! preg_match('/^\d+$/', $this->param['id'])) return '';
	if(isset($_SESSION['captcha_keystring']) && $_SESSION['captcha_keystring'] ==  $_POST['keystring']){
	    $res = $this->btpd_del_torrent($this->param['id']);
	    if ($res['code'] != 0) {
		$out .= 'Error:' . $this->get_btpd_error($res['code']);
		return $out;
	    }
	    $out .= 'Torrent removed.';
	} else {
	    $out .= 'Removing torrent ' . $this->param['id'] . '<br />';
	    $out .= '<form action="' . $_SERVER['PHP_SELF'] . '" method="post">';
	    $out .= '<input type="hidden" name="action" value="del" />';
	    $out .= '<input type="hidden" name="id" value="' . $this->param['id'] . '" />';
	    $out .= '<img src="' . $_SERVER['PHP_SELF'] . '?action=kcaptcha" align=middle />';
	    if ($_POST['keystring']) $out .= 'Invalid confirmation code. try again.';
	    $out .= '<br /><input type="text" size="6" maxlength="6" name="keystring" /><br />';
	    $out .= '<input type="submit" value=" Please enter confirmation code. " class="submit" />';
	    $out .= '</form>';
	}
	return $out;
    } // del_torrent

    public function kcaptcha() {
	$captcha = new KCAPTCHA();
	$_SESSION['captcha_keystring'] = $captcha->getKeyString();
	die();
    } // kcaptcha

    public function login_form() {
	if (! $this->auth->user()) {
	    $out .= '<a href="javascript:open_login_form()">Login</a>';
	} else {
	    $out .= '<a href="' . $_SERVER['PHP_SELF'] . '?action=logoff">Logoff</a>';
	}
	return $out;
    } // login_form

    public function refresh_selector() {
	$timings = array (
	    0 => false,
	    10 => '10 sec.',
	    30 => '30 sec.',
	    60 => '1 min.',
	    300 => '5 min.',
	    600 => '10 min'
	);
	$out .= '<div class="counter"><span id="sec">Refresh:</span><form id="refresher"><select id="timer" onChange="set_refresh(this.value);">';
	foreach($timings as $key => $val) {
	    $out .= '<option value="' . $key . '"' . ($_COOKIE['refresh'] == $key ? ' selected' : '') . '>' . $val . '</option>';
	}
	$out .= '</select></form></div>';
	if (array_key_exists($_COOKIE['refresh'],$timings)) $out .= '<script language="JavaScript" type="text/javascript">set_refresh(' . $_COOKIE['refresh'] . ');</script>';
	return $out;
    } // refresh_selector
    public function login() {
	if (! $this->auth->login($this->param['login'], $this->param['pass'])) {
	    $out .= 'Invalid login/password combination.';
	}
	return $out;
    } // login

    public function logoff() {
	$this->auth->logoff();
    } // login
} // class

?>