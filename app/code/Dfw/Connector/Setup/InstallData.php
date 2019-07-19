<?php
/**
 * Created by Q-Solutions Studio
 * Date: 19.07.19
 *
 * @category    Dfw
 * @package     Dfw_Connector
 * @author      Maciej Buchert <maciej@qsolutionsstudio.com>
 */

namespace Dfw\Connector\Setup;

use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Integration\Model\ConfigBasedIntegrationManager;

/**
 * Class InstallData
 * @package Dfw\Connector\Setup
 */
class InstallData implements InstallDataInterface
{
    /**
     * @var ConfigBasedIntegrationManager
     */
    private $integrationManager;

    /**
     * InstallData constructor.
     * @param ConfigBasedIntegrationManager $integrationManager
     */
    public function __construct(ConfigBasedIntegrationManager $integrationManager)
    {
        $this->integrationManager = $integrationManager;
    }

    /**
     * {@inheritdoc}
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $this->integrationManager->processIntegrationConfig(['dfwIntegration']);
    }
}
