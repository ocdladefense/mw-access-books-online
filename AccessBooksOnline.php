<?php



# Autoload classes and files

$wgAutoloadClasses["AccessBooksOnline\Access"] = __DIR__ . '/Access.php';
// Register Hooks
$wgHooks['UserGetRights'][] = 'AccessBooksOnline\Access::onUserGetRights';
