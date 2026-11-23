<?php
require_once("auth_config.php");

$auth->logout();
header('Location: login.php?message=logged_out');
exit();
?>