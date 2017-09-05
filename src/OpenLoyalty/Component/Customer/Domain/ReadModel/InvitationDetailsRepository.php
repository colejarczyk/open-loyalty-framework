<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Component\Customer\Domain\ReadModel;

use Broadway\ReadModel\RepositoryInterface;
use OpenLoyalty\Component\Customer\Domain\CustomerId;

interface InvitationDetailsRepository extends RepositoryInterface
{
    public function findByParametersPaginated(array $params, $exact = true, $page = 1, $perPage = 10, $sortField = null, $direction = 'DESC');

    public function countTotal(array $params = [], $exact = true);

    public function findByToken($token);

    public function findOneByRecipientId(CustomerId $recipient);
}
