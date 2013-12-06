<?php
$app = require __DIR__ . '/../application.php';
$calls = $app['db']->findAll("call");
$app->run();