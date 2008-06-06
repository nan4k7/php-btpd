<?php
header('Content-type: text/html; charset=utf-8');
require_once('bencode.php');
require_once('config.php');
require_once('class.btpdControl.php');
require_once('class.btpdWebControl.php');
require_once('class.Auth.php');
require_once('kcaptcha/kcaptcha.php');
$btpdc = new btpdWebControl();
?><html>
<head>
<link rel="stylesheet" href="style.css" type="text/css">
<script language="JavaScript" type="text/javascript" src="js.js"></script>
<script language="JavaScript" type="text/javascript" src="sorttable.js"></script>
</head>
<body>
<?php print $btpdc->refresh_selector(); ?>
<div id="topnav">
<ul>
<li><a href="index.php">Current torrent index</a></li>
<li><a href="index.php?action=log"">Last 10Kb of BTPD log file</a></li>
<li><?php print $btpdc->login_form() ?></li>
</ul>
</div>
<div id="login" class="login">
<Form action="index.php" method="post">
<input type="hidden" name="action" value="login" />
<table>
<tr><td>Login:</td><td><input type="text" name="login" /></td></tr>
<tr><td>Password:</td><td><input type="password" name="pass" /></td></tr>
<tr><td></td><td><input type="submit" value=" Login " class="submit" /></td></tr>
</table>
</form>
</div>							    		
<br clear=all>
<?php print $btpdc->result();?>
<div class="copyright"><a href="http://www.murmeldjur.se/btpd/">BTPD</a> <a href="http://code.google.com/p/php-btpd/">PHP Web control panel</a> v0.1 by Partizan </div>
<script type="text/javascript">
var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");
document.write(unescape("%3Cscript src='" + gaJsHost + "google-analytics.com/ga.js' type='text/javascript'%3E%3C/script%3E"));
</script>
<script type="text/javascript">
var pageTracker = _gat._getTracker("UA-72900-13");
pageTracker._initData();
pageTracker._trackPageview();
</script>
</body>
