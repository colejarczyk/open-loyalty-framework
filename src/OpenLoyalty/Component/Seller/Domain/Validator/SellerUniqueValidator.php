<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Component\Seller\Domain\Validator;

use Broadway\ReadModel\RepositoryInterface;
use OpenLoyalty\Component\Seller\Domain\Exception\EmailAlreadyExistsException;
use OpenLoyalty\Component\Seller\Domain\ReadModel\SellerDetails;
use OpenLoyalty\Component\Seller\Domain\SellerId;

/**
 * Class SellerUniqueValidator.
 */
class SellerUniqueValidator
{
    /**
     * @var RepositoryInterface
     */
    protected $sellerDetailsRepository;

    /**
     * CustomerUniqueValidator constructor.
     *
     * @param RepositoryInterface $customerDetailsRepository
     */
    public function __construct(RepositoryInterface $customerDetailsRepository)
    {
        $this->sellerDetailsRepository = $customerDetailsRepository;
    }

    public function validateEmailUnique($email, SellerId $sellerId = null)
    {
        $sellers = $this->sellerDetailsRepository->findBy(['email' => $email]);
        if ($sellerId) {
            /** @var SellerDetails $seller */
            foreach ($sellers as $key => $seller) {
                if ($seller->getId() == $sellerId->__toString()) {
                    unset($sellers[$key]);
                }
            }
        }

        if (count($sellers) > 0) {
            throw new EmailAlreadyExistsException();
        }
    }
}
