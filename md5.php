<?php
header('Content-type: text/html; charset=utf-8');
print 'MD5 of "' . $_POST['str'] . '" == ' . md5($_POST['str']) . '<br />';
print '<form Action="' . $_SERVER['PHP_SELF'] . '" method="post"><input type="text" name="str"><input type="submit" value="calc Md5 hash"></form>';
?>