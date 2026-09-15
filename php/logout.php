<?php

// Clear the login session and return to the login page.

session_start();

session_unset();

session_destroy();

header("Location: ../login.html");

exit;

?>