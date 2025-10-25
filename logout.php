<?php
session_start();
// Destroy all session data
session_unset();
session_destroy();
session_start();
session_regenerate_id(true);
// Redirect to admin login page
header("Location: home.php");
exit();
?>
