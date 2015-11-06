<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogImportExport\Model\Import\Product;

use Magento\CatalogImportExport\Model\Import\Product;
use Magento\CatalogImportExport\Model\Import\Product\Validator\AbstractImportValidator;

class Validator extends AbstractImportValidator implements RowValidatorInterface
{
    /**
     * @var RowValidatorInterface[]|AbstractImportValidator[]
     */
    protected $validators = [];

    /**
     * @var  \Magento\Framework\Stdlib\String
     */
    protected $string;

    /**
     * @var array
     */
    protected $uniqueAttributes;

    /**
     * @var array
     */
    protected $rowData;

    /**
     * @param \Magento\Framework\Stdlib\String $string
     * @param array $validators
     */
    public function __construct(
        \Magento\Framework\Stdlib\String $string,
        $validators = []
    ) {
        $this->validators = $validators;
        $this->string = $string;
    }

    /**
     * @param mixed $attrCode
     * @param string $type
     * @return bool
     */
    protected function textValidation($attrCode, $type)
    {
        $val = $this->string->cleanString($this->rowData[$attrCode]);
        if ($type == 'text') {
            $valid = $this->string->strlen($val) < Product::DB_MAX_TEXT_LENGTH;
        } else {
            $valid = $this->string->strlen($val) < Product::DB_MAX_VARCHAR_LENGTH;
        }
        if (!$valid) {
            $this->_addMessages([RowValidatorInterface::ERROR_EXCEEDED_MAX_LENGTH]);
        }
        return $valid;
    }

    /**
     * @param mixed $attrCode
     * @param string $type
     * @return bool
     */
    protected function numericValidation($attrCode, $type)
    {
        $val = trim($this->rowData[$attrCode]);
        if ($type == 'int') {
            $valid = (string)(int)$val === $val;
        } else {
            $valid = is_numeric($val);
        }
        if (!$valid) {
            $this->_addMessages(
                [
                    sprintf(
                        $this->context->retrieveMessageTemplate(RowValidatorInterface::ERROR_INVALID_ATTRIBUTE_TYPE),
                        $attrCode,
                        $type
                    )
                ]
            );
        }
        return $valid;
    }

    /**
     * @param string $attrCode
     * @param array $attrParams
     * @param array $rowData
     * @return bool
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function isAttributeValid($attrCode, array $attrParams, array $rowData)
    {
        $this->rowData = $rowData;
        if (isset($rowData['product_type']) && !empty($attrParams['apply_to'])
            && !in_array($rowData['product_type'], $attrParams['apply_to'])
        ) {
            return false;
        }
        if ($attrCode == Product::COL_SKU || $attrParams['is_required']
            && ($this->context->getBehavior() == \Magento\ImportExport\Model\Import::BEHAVIOR_REPLACE
                || ($this->context->getBehavior() == \Magento\ImportExport\Model\Import::BEHAVIOR_APPEND
                    && !isset($this->context->getOldSku()[$rowData[$attrCode]])))
        ) {
            if (!isset($rowData[$attrCode]) || !strlen(trim($rowData[$attrCode]))) {
                $valid = false;
                $this->_addMessages(
                    [
                        sprintf(
                            $this->context->retrieveMessageTemplate(
                                RowValidatorInterface::ERROR_VALUE_IS_REQUIRED
                            ),
                            $attrCode
                        )
                    ]
                );
                return $valid;
            }
        }
        if (!strlen(trim($rowData[$attrCode]))) {
            return true;
        }
        switch ($attrParams['type']) {
            case 'varchar':
            case 'text':
                $valid = $this->textValidation($attrCode, $attrParams['type']);
                break;
            case 'decimal':
            case 'int':
                $valid = $this->numericValidation($attrCode, $attrParams['type']);
                break;
            case 'select':
            case 'boolean':
            case 'multiselect':
                $values = explode(Product::PSEUDO_MULTI_LINE_SEPARATOR, $rowData[$attrCode]);
                $valid = true;
                foreach ($values as $value) {
                    $valid = $valid && isset($attrParams['options'][strtolower($value)]);
                }
                if (!$valid) {
                    $this->_addMessages(
                        [
                            sprintf(
                                $this->context->retrieveMessageTemplate(
                                    RowValidatorInterface::ERROR_INVALID_ATTRIBUTE_OPTION
                                ),
                                $attrCode
                            )
                        ]
                    );
                }
                break;
            case 'datetime':
                $val = trim($rowData[$attrCode]);
                $valid = strtotime($val) !== false;
                if (!$valid) {
                    $this->_addMessages([RowValidatorInterface::ERROR_INVALID_ATTRIBUTE_TYPE]);
                }
                break;
            default:
                $valid = true;
                break;
        }
        if ($valid && !empty($attrParams['is_unique'])) {
            if (isset($this->uniqueAttributes[$attrCode][$rowData[$attrCode]])
                && ($this->uniqueAttributes[$attrCode][$rowData[$attrCode]] != $rowData[Product::COL_SKU])) {
                $this->_addMessages([RowValidatorInterface::ERROR_DUPLICATE_UNIQUE_ATTRIBUTE]);
                return false;
            }
            $this->uniqueAttributes[$attrCode][$rowData[$attrCode]] = $rowData[Product::COL_SKU];
        }
        return (bool)$valid;
    }

    /**
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    protected function isValidAttributes()
    {
        $this->_clearMessages();
        if (!isset($this->rowData['product_type'])) {
            return false;
        }
        $entityTypeModel = $this->context->retrieveProductTypeByName($this->rowData['product_type']);
        if ($entityTypeModel) {
            foreach ($this->rowData as $attrCode => $attrValue) {
                $attrParams = $entityTypeModel->retrieveAttributeFromCache($attrCode);
                if ($attrParams) {
                    $this->isAttributeValid($attrCode, $attrParams, $this->rowData);
                }
            }
            if ($this->getMessages()) {
                return false;
            }
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isValid($value)
    {
        $this->rowData = $value;
        $this->_clearMessages();
        $returnValue = $this->isValidAttributes();
        foreach ($this->validators as $validator) {
            if (!$validator->isValid($value)) {
                $returnValue = false;
                $this->_addMessages($validator->getMessages());
            }
        }
        return $returnValue;
    }

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        foreach ($this->validators as $validator) {
            $validator->setContext($this->getContext())->init();
        }
    }
}
