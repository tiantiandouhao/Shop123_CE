<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Payment\Gateway\Validator;

use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\ObjectManager\TMap;
use Magento\Framework\ObjectManager\TMapFactory;

class ValidatorPool implements \Magento\Payment\Gateway\Validator\ValidatorPoolInterface
{
    /**
     * @var ValidatorInterface[] | TMap
     */
    private $validators;

    /**
     * @param array $validators
     * @param TMapFactory $tmapFactory
     */
    public function __construct(
        array $validators,
        TMapFactory $tmapFactory
    ) {
        $this->validators = $tmapFactory->create(
            [
                'array' => $validators,
                'type' => 'Magento\Payment\Gateway\Validator\ValidatorInterface'
            ]
        );
    }

    /**
     * Returns configured validator
     *
     * @param string $code
     * @return ValidatorInterface
     * @throws NotFoundException
     */
    public function get($code)
    {
        if (!isset($this->validators[$code])) {
            throw new NotFoundException(__('Validator for field %1 does not exist.', $code));
        }

        return $this->validators[$code];
    }
}
