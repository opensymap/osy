<?php
require '../../autoload.php';
require 'Lib/Installer.php';

use Installer\Lib\Installer;

$installer = new Installer();
$installer->run();
