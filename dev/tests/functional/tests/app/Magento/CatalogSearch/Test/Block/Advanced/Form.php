<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CatalogSearch\Test\Block\Advanced;

use Magento\Mtf\Block\Form as ParentForm;
use Magento\Mtf\Client\Element;
use Magento\Mtf\Client\Locator;
use Magento\Mtf\Fixture\FixtureInterface;
use Magento\Mtf\Client\Element\SimpleElement;

/**
 * Advanced search form.
 */
class Form extends ParentForm
{
    /**
     * Search button selector.
     *
     * @var string
     */
    protected $searchButtonSelector = '.action.search';

    /**
     * Field selector select tax class.
     *
     * @var string
     */
    protected $taxClassSelector = '#tax_class_id';

    /**
     * Field selector.
     *
     * @var string
     */
    protected $fieldSelector = './/div[label and div]';

    /**
     * Label element selector.
     *
     * @var string
     */
    protected $labelSelector = 'label';

    /**
     * Submit search form.
     *
     * @return void
     */
    public function submit()
    {
        $this->_rootElement->find($this->searchButtonSelector)->click();
    }

    /**
     * Fill the root form.
     *
     * @param FixtureInterface $fixture
     * @param SimpleElement|null $element
     * @return $this
     */
    public function fill(FixtureInterface $fixture, SimpleElement $element = null)
    {
        // Prepare price data
        $data = $fixture->getData();
        if (isset($data['price'])) {
            $data = array_merge($data, $data['price']);
            unset($data['price']);
        }

        // Mapping
        $mapping = $this->dataMapping($data);
        $this->_fill($mapping, $element);

        return $this;
    }

    /**
     * Fill form with custom fields.
     * (for End To End Tests)
     *
     * @param FixtureInterface $fixture
     * @param array $fields
     * @param SimpleElement $element
     */
    public function fillCustom(FixtureInterface $fixture, array $fields, SimpleElement $element = null)
    {
        $data = $fixture->getData('fields');
        $dataForMapping = array_intersect_key($data, array_flip($fields));
        $mapping = $this->dataMapping($dataForMapping);
        $this->_fill($mapping, $element);
    }

    /**
     * Get form fields.
     *
     * @return array
     */
    public function getFormLabels()
    {
        $labels = [];
        $elements = $this->_rootElement->getElements($this->fieldSelector, Locator::SELECTOR_XPATH);
        foreach ($elements as $element) {
            $labels[] = $element->find($this->labelSelector)->getText();
        }
        return $labels;
    }
}
