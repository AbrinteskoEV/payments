<?php
require __DIR__ . '/vendor/autoload.php';

use Payment\OperationRepository;
use Payment\UserMoneyRepository;

const StorageDir = __DIR__ . DIRECTORY_SEPARATOR . "storage" . DIRECTORY_SEPARATOR;

const money_file = StorageDir . "users.json";
const money_log = StorageDir . "log.json";

$operations = new OperationRepository(money_log);
$user_money = new UserMoneyRepository(money_file, $operations);
$user_money->sendMoney(1, 2, 100);

