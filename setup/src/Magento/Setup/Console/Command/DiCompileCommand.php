<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Setup\Console\Command;

use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\DriverInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\App\DeploymentConfig;
use Magento\Setup\Model\ObjectManagerProvider;
use Magento\Setup\Module\Di\App\Task\Manager;
use Magento\Setup\Module\Di\App\Task\OperationFactory;
use Magento\Setup\Module\Di\App\Task\OperationException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command to run compile in single-tenant mode
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DiCompileCommand extends Command
{
    /** @var DeploymentConfig */
    private $deploymentConfig;

    /** @var ObjectManagerInterface */
    private $objectManager;

    /** @var Manager */
    private $taskManager;

    /** @var DirectoryList */
    private $directoryList;

    /** @var Filesystem */
    private $filesystem;

    /** @var array */
    private $excludedPathsList;

    /** @var DriverInterface */
    private $fileDriver;

    /**
     * Constructor
     *
     * @param DeploymentConfig $deploymentConfig
     * @param DirectoryList $directoryList
     * @param Manager $taskManager
     * @param ObjectManagerProvider $objectManagerProvider
     * @param Filesystem $filesystem
     * @param DriverInterface $fileDriver
     */
    public function __construct(
        DeploymentConfig $deploymentConfig,
        DirectoryList $directoryList,
        Manager $taskManager,
        ObjectManagerProvider $objectManagerProvider,
        Filesystem $filesystem,
        DriverInterface $fileDriver
    ) {
        $this->deploymentConfig = $deploymentConfig;
        $this->directoryList    = $directoryList;
        $this->objectManager    = $objectManagerProvider->get();
        $this->taskManager      = $taskManager;
        $this->filesystem       = $filesystem;
        parent::__construct();
        $this->fileDriver = $fileDriver;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('setup:di:compile')
            ->setDescription(
                'Generates DI configuration and all non-existing interceptors and factories'
            );
        parent::configure();
    }

    /**
     * Checks that application is installed and DI resources are cleared
     *
     * @return string[]
     */
    private function checkEnvironment()
    {
        $messages = [];
        if (!$this->deploymentConfig->isAvailable()) {
            $messages[] = 'You cannot run this command because the Magento application is not installed.';
        }

        /**
         * By the time the command is able to execute, the Object Management configuration is already contaminated
         * by old config info, and it's too late to just clear the files in code.
         *
         * TODO: reconfigure OM in runtime so DI resources can be cleared after command launches
         *
         */
        $path = $this->directoryList->getPath(DirectoryList::DI);
        if ($this->fileDriver->isExists($path)) {
            $messages[] = "DI configuration must be cleared before running compiler. Please delete '$path'.";
        }

        return $messages;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $errors = $this->checkEnvironment();
        if ($errors) {
            foreach ($errors as $line) {
                $output->writeln($line);
            }
            return;
        }

        $appCodePath = $this->directoryList->getPath(DirectoryList::MODULES);
        $libraryPath = $this->directoryList->getPath(DirectoryList::LIB_INTERNAL);
        $generationPath = $this->directoryList->getPath(DirectoryList::GENERATION);

        $this->objectManager->get('Magento\Framework\App\Cache')->clean();
        $compiledPathsList = [
            'application' => $appCodePath,
            'library' => $libraryPath . '/Magento/Framework',
            'generated_helpers' => $generationPath
        ];
        $this->excludedPathsList = [
            'application' => '#^' . $appCodePath . '/[\\w]+/[\\w]+/Test#',
            'framework' => '#^' . $libraryPath . '/[\\w]+/[\\w]+/([\\w]+/)?Test#'
        ];
        $dataAttributesIncludePattern = [
            'extension_attributes' => '/\/etc\/([a-zA-Z_]*\/extension_attributes|extension_attributes)\.xml$/'
        ];
        $this->configureObjectManager($output);

        $operations = [
            OperationFactory::REPOSITORY_GENERATOR => [
                'path' => $compiledPathsList['application'],
                'filePatterns' => ['di' => '/\/etc\/([a-zA-Z_]*\/di|di)\.xml$/']
            ],
            OperationFactory::DATA_ATTRIBUTES_GENERATOR => [
                'path' => $compiledPathsList['application'],
                'filePatterns' => $dataAttributesIncludePattern
            ],
            OperationFactory::APPLICATION_CODE_GENERATOR => [
                $compiledPathsList['application'],
                $compiledPathsList['library'],
                $compiledPathsList['generated_helpers'],
            ],
            OperationFactory::INTERCEPTION => [
                    'intercepted_paths' => [
                        $compiledPathsList['application'],
                        $compiledPathsList['library'],
                        $compiledPathsList['generated_helpers'],
                    ],
                    'path_to_store' => $compiledPathsList['generated_helpers'],
            ],
            OperationFactory::AREA_CONFIG_GENERATOR => [
                $compiledPathsList['application'],
                $compiledPathsList['library'],
                $compiledPathsList['generated_helpers'],
            ],
            OperationFactory::INTERCEPTION_CACHE => [
                $compiledPathsList['application'],
                $compiledPathsList['library'],
                $compiledPathsList['generated_helpers'],
            ]
        ];

        try {
            $this->cleanupFilesystem(
                [
                    DirectoryList::CACHE,
                    DirectoryList::GENERATION,
                    DirectoryList::DI,
                ]
            );
            foreach ($operations as $operationCode => $arguments) {
                $this->taskManager->addOperation(
                    $operationCode,
                    $arguments
                );
            }
            $this->taskManager->process();
            $output->writeln('<info>Generated code and dependency injection configuration successfully.</info>');
        } catch (OperationException $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
        }
    }

    /**
     * Delete directories by their code from "var" directory
     *
     * @param array $directoryCodeList
     * @return void
     */
    private function cleanupFilesystem($directoryCodeList)
    {
        foreach ($directoryCodeList as $code) {
            $this->filesystem->getDirectoryWrite($code)->delete();
        }
    }

    /**
     * Configure Object Manager
     *
     * @param OutputInterface $output
     * @return void
     */
    private function configureObjectManager(OutputInterface $output)
    {
        $this->objectManager->configure(
            [
                'preferences' => [
                    'Magento\Setup\Module\Di\Compiler\Config\WriterInterface' =>
                        'Magento\Setup\Module\Di\Compiler\Config\Writer\Filesystem',
                ],
                'Magento\Setup\Module\Di\Compiler\Config\ModificationChain' => [
                    'arguments' => [
                        'modificationsList' => [
                            'BackslashTrim' =>
                                ['instance' => 'Magento\Setup\Module\Di\Compiler\Config\Chain\BackslashTrim'],
                            'PreferencesResolving' =>
                                ['instance' => 'Magento\Setup\Module\Di\Compiler\Config\Chain\PreferencesResolving'],
                            'InterceptorSubstitution' =>
                                ['instance' => 'Magento\Setup\Module\Di\Compiler\Config\Chain\InterceptorSubstitution'],
                            'InterceptionPreferencesResolving' =>
                                ['instance' => 'Magento\Setup\Module\Di\Compiler\Config\Chain\PreferencesResolving'],
                            'ArgumentsSerialization' =>
                                ['instance' => 'Magento\Setup\Module\Di\Compiler\Config\Chain\ArgumentsSerialization'],
                        ]
                    ]
                ],
                'Magento\Setup\Module\Di\Code\Generator\PluginList' => [
                    'arguments' => [
                        'cache' => [
                            'instance' => 'Magento\Framework\App\Interception\Cache\CompiledConfig'
                        ]
                    ]
                ],
                'Magento\Setup\Module\Di\Code\Reader\ClassesScanner' => [
                    'arguments' => [
                        'excludePatterns' => $this->excludedPathsList
                    ]
                ],
                'Magento\Setup\Module\Di\Compiler\Log\Writer\Console' => [
                    'arguments' => [
                        'output' => $output,
                    ]
                ],
            ]
        );
    }
}
