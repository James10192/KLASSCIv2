<?php
// This file displays detailed PHP configuration information
// It's useful for debugging PHP-related issues

// Set headers to prevent caching
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

// Display PHP information
phpinfo();
?> 