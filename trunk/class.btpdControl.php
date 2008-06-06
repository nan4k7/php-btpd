<?php
/**
  * class.btpdControl.php
  * @package PHP BTPD Control panel
  */
/**
  * @author Volkov Sergey
  * @version 0.1
  * @package PHP BTPD Control panel
  */

class btpdControl {
    private $btpd_home;
    private $socket;

    private $errors = array(
	0 => false,
	1 => 'Communication error',
	2 => 'Bad content directory',
	3 => 'Bad torrent',
	4 => 'Bad torrent entry',
	5 => 'Bad Tracker',
	6 => 'Could not create content directory',
	7 => 'No such key',
	8 => 'No such torrent entry',
	9 => 'BTPD is shutting down',
	10 => 'Torrent is active',
	11 => 'Torrent entry exists',
	12 => 'Torrent is inactive');

    private $torrent_states = array (
	0 => 'Inactive',
	1 => 'Start',
	2 => 'Stop',
	3 => 'Leech',
	4 => 'Seed');

    const CGOT = 1;
    const CSIZE = 3;
    const DIR = 5;
    const NAME = 7;
    const NUM = 9;
    const IHASH = 11;
    const PCGOT = 13;
    const PCOUNT = 15;
    const PCCOUNT = 17;
    const PCSEEN = 19;
    const RATEDWN = 21;
    const RATEUP = 23;
    const SESSDWN = 25;
    const SESSUP = 27;
    const STATE = 29;
    const TOTDWN = 31;
    const TOTUP = 33;
    const TRERR = 35;

    function __construct($path) {
	if (! is_dir($path)) die('Invalid path');
	if (! is_writable($path . '/sock')) die('btpd socket not found');
	$this->btpd_home = $path;
    } // __construct

    private function connect() {
	if (! $this->socket = socket_create(AF_UNIX, SOCK_STREAM, 0)) {
	    die(socket_strerror(socket_last_error()));
	}
	if (! socket_connect($this->socket, $this->btpd_home . '/sock')) {
	    die(socket_strerror(socket_last_error()));
	}
    } // connect

    private function disconnect() {
	socket_close($this->socket);
    } // disconnect

    private function sendmsg($data) {
	$data = pack('I', strlen($data)) . $data;
	if (! $result=socket_write($this->socket, $data)) {
	    die(socket_strerror(socket_last_error()));
	}
	return $result;
    } // sendmsg

    private function recvmsg() {
	$buf = socket_read($this->socket, 4, PHP_BINARY_READ);
	if (strlen($buf) != 4) return false;
	$len = unpack('I', $buf);
	while($readed < $len[1]) {
	    $readed += socket_recv($this->socket, &$buf, $len[1], 0);
	    $out .= $buf;
	}
	return $out;
    } // recvmsg

    private function req_result($data) {
	$bencoder = new BEncodeLib();
	$this->connect();
	$this->sendmsg($bencoder->bencode($data));
	$result = $bencoder->bdecode($this->recvmsg());
	$this->disconnect();
	if (! is_array($result)) die('Invalid responce from btpd (' . __CLASS__ . ':' . __METHOD__ . ' Line:' . __LINE__ . ')');
	return $result;
    } // req_result

    public function btpd_list_torrents($from = 0) {
	if (!preg_match('/^\d+$/', $from)) $from = 0;
	$command = array('tget');
	array_push($command,
	    array('from' => ($from > 0 ? array($from) : 0),
		'keys' => array(0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18)));
	$result = $this->req_result($command);
	if (! is_array($result)) die('Invalid responce from btpd (' . __CLASS__ . ':' . __METHOD__ . ' Line:' . __LINE__ . ')');
	return $result;
    } // list_torrents

    public function btpd_add_torrent($torrent, $path, $name = '') {
	$bencoder = new BEncodeLib();
	$command = array('add');
	$args = array('content' => $path,
		'torrent' => $torrent
	    );
	if (strlen($name) > 0) $args['name']=$name;
	array_push($command, $args);
	$result = $this->req_result($command);
	if (! is_array($result)) die('Invalid responce from btpd (' . __CLASS__ . ':' . __METHOD__ . ' Line:' . __LINE__ . ')');
	return $result;
    } // add_torrent

    public function btpd_del_torrent($id) {
	if (! preg_match('/^\d+$/', $id)) return;
	$command = array('del', $id);
	$result = $this->req_result($command);
	if (! is_array($result)) die('Invalid responce from btpd (' . __CLASS__ . ':' . __METHOD__ . ' Line:' . __LINE__ . ')');
	return $result;
    } // del_torrent

    public function btpd_start_torrent($id) {
	if (! preg_match('/^\d+$/', $id)) return;
	$command = array('start', $id);
	$result = $this->req_result($command);
	if (! is_array($result)) die('Invalid responce from btpd (' . __CLASS__ . ':' . __METHOD__ . ' Line:' . __LINE__ . ')');
	return $result;
    } // start_torrent

    public function btpd_stop_torrent($id) {
	if (! preg_match('/^\d+$/', $id)) return;
	$command = array('stop', $id);
	$result = $this->req_result($command);
	if (! is_array($result)) die('Invalid responce from btpd (' . __CLASS__ . ':' . __METHOD__ . ' Line:' . __LINE__ . ')');
	return $result;
    } // del_torrent

    public function get_btpd_error($id) {
	return $this->errors[$id];
    } // get_btpd_error

    public function get_torrent_state($id) {
	return $this->torrent_states[$id];
    } // get_torrent_state

    public function get_last_log($bytes = 10240) {
	if (!preg_match('/^\d+$/', $bytes)) return;
	$log_info = stat($this->btpd_home . '/log');
	if (! is_array($log_info)) return 'err1';
	if ($log_info['size'] < $bytes) {
	    $start_pos = 0;
	} else {
	    $start_pos = $log_info['size'] - $bytes;
	}
	return file_get_contents($this->btpd_home . '/log', false, null, $start_pos, $bytes);
    } // get_last_log
} // class
?>