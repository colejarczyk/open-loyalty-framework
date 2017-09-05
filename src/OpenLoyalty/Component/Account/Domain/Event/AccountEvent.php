<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Component\Account\Domain\Event;

use Broadway\Serializer\SerializableInterface;
use OpenLoyalty\Component\Account\Domain\AccountId;

/**
 * Class AccountEvent.
 */
abstract class AccountEvent implements SerializableInterface
{
    /**
     * @var AccountId
     */
    protected $accountId;

    /**
     * AccountEvent constructor.
     *
     * @param AccountId $accountId
     */
    public function __construct(AccountId $accountId)
    {
        $this->accountId = $accountId;
    }

    /**
     * @return AccountId
     */
    public function getAccountId()
    {
        return $this->accountId;
    }

    public function serialize()
    {
        return ['accountId' => (string) $this->accountId];
    }
}
