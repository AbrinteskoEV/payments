<?php

namespace Payment;

use AllowDynamicProperties;
use \Payment\OperationRepository;

#[AllowDynamicProperties] class UserMoneyRepository extends Repository implements Payment
{
    private OperationRepository $operations;

    /**
     * @param $file
     * @param OperationRepository $operations
     */
    public function __construct($file, OperationRepository $operations)
    {
        $this->file = $file;
        $this->operations = $operations;
        parent::__construct($file);
    }

    /**
     * @param $user
     * @return mixed
     */
    public function getMoneyValue($user): mixed
    {
        return $this->getData($user);
    }

    /**
     * @param int $sender
     * @param int $receiver
     * @param int $value
     * @return bool
     */
    public function sendMoney(int $sender, int $receiver, int $value): bool
    {
        $from_money = $this->getMoneyValue($sender);
        if ($from_money < $value) {
            return false;
        }
        $money_left = $from_money - $value;
        $this->setMoneyValue($sender, $money_left);

        $to_money = $this->getMoneyValue($receiver);
        $this->setMoneyValue($receiver, $to_money + $value);
        $this->operations->log([
            'from' => $sender,
            'to' => $receiver,
            'sum' => $value,
            'type' => PaymentTypeDictionary::SEND,
            'status' => PaymentStatusDictionary::SUCCESS,
        ]);

        return true;
    }

    /**
     * @param int $user
     * @param int $value
     *
     * @return bool
     */
    public function incomeMoney(int $user, int $value): bool
    {
        $to_money = $this->getMoneyValue($user);
        $this->setMoneyValue($user, $to_money + $value);
        $this->operations->log([
            'from' => null,
            'to' => $user,
            'sum' => $value,
            'type' => PaymentTypeDictionary::INCOMING,
            'status' => PaymentStatusDictionary::SUCCESS,
        ]);

        return true;
    }

    /**
     * @param int $user
     * @param int $value
     *
     * @return bool
     */
    public function outcomeMoney(int $user, int $value): bool
    {
        $from_money = $this->getMoneyValue($user);
        if ($from_money < $value) {
            return false;
        }
        $money_left = $from_money - $value;
        $this->setMoneyValue($user, $money_left);

        $this->operations->log([
            'from' => $user,
            'to' => null,
            'sum' => $value,
            'type' => PaymentTypeDictionary::OUTCOMING,
            'status' => PaymentStatusDictionary::SUCCESS,
        ]);

        return true;
    }

    /**
     * @param $user
     * @param $value
     * @return void
     */
    public function setMoneyValue($user, $value): void
    {
        $this->saveData($user, $value);
    }
}