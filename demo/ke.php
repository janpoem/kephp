<?php
/**
 * kephp cli entry file.
 */

require 'bootstrap.php';

\Ke\Cli\Console::getConsole()->seekCommand()->execute();
