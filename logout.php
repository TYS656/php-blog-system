<?php
session_start();
$_SESSION = [];
session_destroy();
header("Location: home.php", true, 302);
exit;