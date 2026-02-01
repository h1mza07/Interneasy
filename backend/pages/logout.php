<?php
// logout.php
session_start();
session_destroy();
header('Location: /internEasy/frontend/login.html');  // CHANGEMENT ICI
exit();
?>