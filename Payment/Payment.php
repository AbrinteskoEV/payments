<?php
namespace Payment;

interface Payment
{
    public function sendMoney(int $sender, int $receiver, int $value): bool;
}