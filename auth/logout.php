<?php
session_start();
session_unset();
session_destroy();
header('Location: /gestao_academica/auth/login.php');
exit;
