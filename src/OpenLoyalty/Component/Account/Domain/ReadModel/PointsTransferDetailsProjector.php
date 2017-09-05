<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Component\Account\Domain\ReadModel;

use Broadway\ReadModel\Projector;
use Broadway\ReadModel\RepositoryInterface;
use OpenLoyalty\Component\Account\Domain\Account;
use OpenLoyalty\Component\Account\Domain\AccountId;
use OpenLoyalty\Component\Account\Domain\Event\PointsTransferHasBeenCanceled;
use OpenLoyalty\Component\Account\Domain\Event\PointsTransferHasBeenExpired;
use OpenLoyalty\Component\Account\Domain\Event\PointsWereAdded;
use OpenLoyalty\Component\Account\Domain\Event\PointsWereSpent;
use OpenLoyalty\Component\Account\Domain\Exception\CannotBeCanceledException;
use OpenLoyalty\Component\Account\Domain\Exception\CannotBeExpiredException;
use OpenLoyalty\Component\Account\Domain\PointsTransferId;
use OpenLoyalty\Component\Customer\Domain\ReadModel\CustomerDetails;
use OpenLoyalty\Component\Pos\Domain\Pos;
use OpenLoyalty\Component\Pos\Domain\PosId;
use OpenLoyalty\Component\Pos\Domain\PosRepository;
use OpenLoyalty\Component\Transaction\Domain\ReadModel\TransactionDetails;
use OpenLoyalty\Component\Transaction\Domain\ReadModel\TransactionDetailsRepository;

/**
 * Class PointsTransferDetailsProjector.
 */
class PointsTransferDetailsProjector extends Projector
{
    /**
     * @var RepositoryInterface
     */
    private $repository;

    /**
     * @var RepositoryInterface
     */
    private $accountRepository;

    /**
     * @var RepositoryInterface
     */
    private $customerRepository;

    /**
     * @var TransactionDetailsRepository
     */
    private $transactionDetailsRepository;

    /**
     * @var PosRepository
     */
    private $posRepository;

    /**
     * PointsTransferDetailsProjector constructor.
     *
     * @param RepositoryInterface          $repository
     * @param RepositoryInterface          $accountRepository
     * @param RepositoryInterface          $customerRepository
     * @param TransactionDetailsRepository $transactionDetailsRepository
     * @param PosRepository                $posRepository
     */
    public function __construct(
        RepositoryInterface $repository,
        RepositoryInterface $accountRepository,
        RepositoryInterface $customerRepository,
        TransactionDetailsRepository $transactionDetailsRepository,
        PosRepository $posRepository
    ) {
        $this->repository = $repository;
        $this->accountRepository = $accountRepository;
        $this->customerRepository = $customerRepository;
        $this->transactionDetailsRepository = $transactionDetailsRepository;
        $this->posRepository = $posRepository;
    }

    protected function applyPointsWereAdded(PointsWereAdded $event)
    {
        $transfer = $event->getPointsTransfer();
        $id = $transfer->getId();
        /** @var PointsTransferDetails $readModel */
        $readModel = $this->getReadModel($id, $event->getAccountId());
        $readModel->setValue($transfer->getValue());
        $readModel->setCreatedAt($transfer->getCreatedAt());
        $readModel->setState($transfer->isCanceled() ? PointsTransferDetails::STATE_CANCELED : ($transfer->isExpired() ? PointsTransferDetails::STATE_EXPIRED : PointsTransferDetails::STATE_ACTIVE));
        $readModel->setType(PointsTransferDetails::TYPE_ADDING);
        $readModel->setTransactionId($transfer->getTransactionId());
        $readModel->setIssuer($transfer->getIssuer());
        if ($transfer->getTransactionId()) {
            $transaction = $this->transactionDetailsRepository->find($transfer->getTransactionId()->__toString());
            if ($transaction instanceof TransactionDetails && $transaction->getPosId()) {
                $pos = $this->posRepository->byId(new PosId($transaction->getPosId()->__toString()));
                if ($pos instanceof Pos) {
                    $readModel->setPosIdentifier($pos->getIdentifier());
                }
            }
        }
        $readModel->setComment($transfer->getComment());
        $this->repository->save($readModel);
    }

    protected function applyPointsWereSpent(PointsWereSpent $event)
    {
        $transfer = $event->getPointsTransfer();
        $id = $transfer->getId();
        /** @var PointsTransferDetails $readModel */
        $readModel = $this->getReadModel($id, $event->getAccountId());
        $readModel->setValue($transfer->getValue());
        $readModel->setCreatedAt($transfer->getCreatedAt());
        $readModel->setState($transfer->isCanceled() ? PointsTransferDetails::STATE_CANCELED : PointsTransferDetails::STATE_ACTIVE);
        $readModel->setType(PointsTransferDetails::TYPE_SPENDING);
        $readModel->setComment($transfer->getComment());
        $readModel->setIssuer($transfer->getIssuer());
        $this->repository->save($readModel);
    }

    protected function applyPointsTransferHasBeenCanceled(PointsTransferHasBeenCanceled $event)
    {
        $id = $event->getPointsTransferId();
        /** @var PointsTransferDetails $readModel */
        $readModel = $this->getReadModel($id, $event->getAccountId());
        if ($readModel->getType() !== PointsTransferDetails::TYPE_ADDING) {
            throw new CannotBeCanceledException();
        }
        $readModel->setState(PointsTransferDetails::STATE_CANCELED);
        $this->repository->save($readModel);
    }

    protected function applyPointsTransferHasBeenExpired(PointsTransferHasBeenExpired $event)
    {
        $id = $event->getPointsTransferId();
        /** @var PointsTransferDetails $readModel */
        $readModel = $this->getReadModel($id, $event->getAccountId());
        if ($readModel->getType() != PointsTransferDetails::TYPE_ADDING) {
            throw new CannotBeExpiredException();
        }
        $readModel->setState(PointsTransferDetails::STATE_EXPIRED);
        $this->repository->save($readModel);
    }

    /**
     * @param PointsTransferId $pointsTransferId
     * @param AccountId        $accountId
     *
     * @return PointsTransferDetails
     */
    private function getReadModel(PointsTransferId $pointsTransferId, AccountId $accountId)
    {
        $readModel = $this->repository->find($pointsTransferId->__toString());

        if (null === $readModel) {
            /** @var Account $account */
            $account = $this->accountRepository->find($accountId->__toString());
            /** @var CustomerDetails $customer */
            $customer = $this->customerRepository->find($account->getCustomerId()->__toString());
            $readModel = new PointsTransferDetails($pointsTransferId, $account->getCustomerId(), $accountId);
            $readModel->setCustomerEmail($customer->getEmail());
            $readModel->setCustomerFirstName($customer->getFirstName());
            $readModel->setCustomerLastName($customer->getLastName());
            $readModel->setCustomerPhone($customer->getPhone());
            $readModel->setCustomerLoyaltyCardNumber($customer->getLoyaltyCardNumber());
        }

        return $readModel;
    }
}
