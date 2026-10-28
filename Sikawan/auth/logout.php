<?php
/**
 * auth/logout.php
 * Menghapus session login lalu mengarahkan kembali ke halaman login.
 */
require_once __DIR__ . '/../includes/helpers.php';

$_SESSION = [];
session_destroy();
redirect('auth/login.php');
