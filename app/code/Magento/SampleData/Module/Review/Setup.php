<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SampleData\Module\Review;

use Magento\SampleData\Helper\PostInstaller;
use Magento\SampleData\Model\SetupInterface;

/**
 * Class Setup
 * Launches setup of sample data for Review module
 */
class Setup implements SetupInterface
{
    /**
     * @var Setup\Review
     */
    protected $reviewSetup;

    /**
     * @var PostInstaller
     */
    protected $postInstaller;

    /**
     * @param Setup\Review $reviewSetup
     * @param PostInstaller $postInstaller
     */
    public function __construct(
        Setup\Review $reviewSetup,
        PostInstaller $postInstaller
    ) {
        $this->reviewSetup = $reviewSetup;
        $this->postInstaller = $postInstaller;
    }

    /**
     * {@inheritdoc}
     */
    public function run()
    {
        $this->postInstaller->addSetupResource($this->reviewSetup);
    }
}
