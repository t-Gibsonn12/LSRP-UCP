<?php
require __DIR__ . '/app/bootstrap.php';

if (is_logged_in()) {
    redirect('dashboard.php');
}

redirect('login.php');
