<?php
require_once 'includes/functions.php';

// Session sudah dimulai di database.php
session_unset();
session_destroy();
redirect('index.php?page=login');
?>