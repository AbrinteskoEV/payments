<?php
namespace Payment;

use AllowDynamicProperties;

#[AllowDynamicProperties] class OperationRepository extends Repository implements LogOperation
{
    private UserMoneyRepository $userMoneyRepository;

    /**
     * @param $file
     * @param UserMoneyRepository $userMoneyRepository
     */
    public function __construct($file, UserMoneyRepository $userMoneyRepository)
    {
        $this->userMoneyRepository = $userMoneyRepository;
        $this->file = $file;
        parent::__construct($file);
    }

    /**
     * @param array $operation
     * @return void
     */
    public function log(array $operation): void
    {
        $id = $this->newLogId();
        $operation['id'] = $id;
        $operation['date'] = (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM);
        $this->addData($operation);

        if ($operation['to']) {
            $this->handlePendingTransactions($operation['to']);
        }
    }

    /**
     * @param string $date
     *
     * @return array
     *
     * @throws \Exception
     */
    public function getOperationsByDate(string $date): array
    {
        $result = [];

        $operationList = $this->readStorage();
        foreach($operationList as $operation) {
            $operationDate = (new \DateTimeImmutable($operation['date']))->format('y-m-d');
            if ($operationDate === $date) {
                $result[] = $operation;
            }
        }

        return $result;
    }

    /**
     * @param string $date
     *
     * @return int
     *
     * @throws \Exception
     */
    public function getOperationSumByDate(string $date): int
    {
        $result = 0;

        $operationList = $this->getOperationsByDate($date);
        foreach ($operationList as $operation) {
            $result += $operation['sum'];
        }

        return $result;
    }

    /**
     * @param int $operationId
     *
     * @return bool
     */
    public function revert(int $operationId): bool
    {
        $operation = $this->getData($operationId);

        if ($operation['type'] !== PaymentTypeDictionary::SEND) {
            return false;
        }

        $this->log([
            'from' => $operation['to'],
            'to' => $operation['from'],
            'sum' => $operation['sum'],
            'type' => PaymentTypeDictionary::SEND,
            'status' => PaymentStatusDictionary::PENDING,
        ]);

        $this->tryToProcess($operation);

        return true;
    }

    /**
     * @param int $userId
     *
     * @return void
     */
    private function handlePendingTransactions(int $userId): void
    {
        $pendingPaymentList = $this->findBy(['from' => $userId, 'status' => PaymentStatusDictionary::PENDING]);
        foreach ($pendingPaymentList as $payment) {
            $this->tryToProcess($payment);
        }
    }

    /**
     * @param array $operation
     *
     * @return bool
     */
    private function tryToProcess(array $operation): bool
    {
        $sender = $operation['from'];
        $receiver = $operation['to'];
        $value = $operation['sum'];

        $from_money = $this->userMoneyRepository->getMoneyValue($sender);
        if ($from_money < $value) {
            return false;
        }
        $money_left = $from_money - $value;
        $this->userMoneyRepository->setMoneyValue($sender, $money_left);

        $to_money = $this->userMoneyRepository->getMoneyValue($receiver);
        $this->userMoneyRepository->setMoneyValue($receiver, $to_money + $value);

        $this->confirm($operation['id']);

        return true;
    }

    /**
     * @param int $id
     *
     * @return bool
     */
    private function confirm(int $id): bool
    {
        $operation = $this->getData($id);
        $operation['status'] = PaymentStatusDictionary::SUCCESS;
        $this->saveData($id, $operation);

        return true;
    }

    private function newLogId(): int{
        $storage = $this->readStorage();
        $current_count = count($storage);
        return $current_count+1;
    }
}