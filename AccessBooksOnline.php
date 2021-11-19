<?php


# Autoload classes and files
require(__DIR__ . "/Access.php");


// Register Hooks
$wgHooks['UserGetRights'][] = 'AccessBooksOnline\Access::onUserGetRights';
