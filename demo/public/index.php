<?php
/**
 * kephp web entry file.
 */

require '../bootstrap.php';

$web = new \Ke\Web\Web();
$web->dispatch();
