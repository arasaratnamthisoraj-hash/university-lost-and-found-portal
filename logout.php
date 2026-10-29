<?php
require __DIR__ . '/includes/bootstrap.php';
allow_methods(['POST']); csrf();
$_SESSION = []; session_regenerate_id(true);
$_SESSION['flash'] = 'You have signed out.'; redirect('index.php');
