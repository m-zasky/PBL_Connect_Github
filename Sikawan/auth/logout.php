<?php
/**
 * auth/logout.php
 * Menghapus seluruh data session (login + keranjang) lalu kembali ke halaman login.
 */
require_once __DIR__ . '/../includes/helpers.php';

$_SESSION = [];
session_destroy();
redirect('auth/login.php');
