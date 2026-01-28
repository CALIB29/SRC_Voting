<?php
// Regular user logout
session_start();
session_unset();
session_destroy();
header("Location: /src_votingsystem/admin/login.php");
exit;