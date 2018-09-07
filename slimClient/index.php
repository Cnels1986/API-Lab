<?php
require './vendor/autoload.php';
$app = (new nelson\apiClient\App())->get();
$app->run();
