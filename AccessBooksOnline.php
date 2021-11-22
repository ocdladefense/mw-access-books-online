<?php

# OCDLA "Books Online" subscription product id.
$wgBooksOnlineProductIds = array("01t0a000004Ov7LAAS");


# Autoload classes and files
require(__DIR__ . "/Access.php");


// Register Hooks
$wgHooks['UserGetRights'][] = 'AccessBooksOnline\Access::onUserGetRights';
