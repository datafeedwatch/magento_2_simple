<?php
/**
 * Created by Q-Solutions Studio
 * Date: 01.07.19
 *
 * @category    Dfw
 * @package     Dfw_Connector
 * @author      Maciej Buchert <maciej@qsolutionsstudio.com>
 */

namespace Dfw\Connector\Setup;

use Dfw\Connector\Helper\Data as DataHelper;
use Magento\Catalog\Model\Product;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\PageCache\Model\Cache\Type as Cache;

/**
 * Class InstallData
 * @package Dfw\Connector\Setup
 */
class InstallData implements InstallDataInterface
{
    /**
     * @var DataHelper
     */
    private $dataHelper;

    /**
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * @var AttributeRepositoryInterface
     */
    private $attributeRepository;

    /**
     * @var ModuleDataSetupInterface
     */
    private $setup;

    /**
     * @var Cache
     */
    private $cache;

    /**
     * InstallData constructor.
     * @param DataHelper $dataHelper
     * @param EavSetupFactory $eavSetupFactory
     * @param AttributeRepositoryInterface $attributeRepository
     * @param Cache $cache
     */
    public function __construct(
        DataHelper $dataHelper,
        EavSetupFactory $eavSetupFactory,
        AttributeRepositoryInterface $attributeRepository,
        Cache $cache
    ) {
        $this->dataHelper          = $dataHelper;
        $this->eavSetupFactory     = $eavSetupFactory;
        $this->attributeRepository = $attributeRepository;
        $this->cache               = $cache;
    }

    /**
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Validate_Exception
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        if ($context instanceof ModuleContextInterface) {
            $this->setup = $setup;

            $this->installAttributes();
            $this->dataHelper->restoreOriginalAttributesConfig();
            $types = ['config', 'collections', 'eav', 'config_api', 'config_api2'];
            foreach ($types as $type) {
                $this->cache->clean(\Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG, [$type]);
            }
        }
    }

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Validate_Exception
     */
    public function installAttributes()
    {
        $this->installIgnoreDataFeedAttribute();
    }

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Validate_Exception
     */
    public function installIgnoreDataFeedAttribute()
    {
        $properties = [
            'type'                     => 'int',
            'label'                    => 'Ignore In DataFeedWatch',
            'input'                    => 'select',
            'source'                   => \Magento\Eav\Model\Entity\Attribute\Source\Boolean::class,
            'sort_order'               => 100,
            'global'                   => ScopedAttributeInterface::SCOPE_GLOBAL,
            'group'                    => 'General Information',
            'visible'                  => true,
            'required'                 => false,
            'visible_on_front'         => false,
            'is_html_allowed_on_front' => false,
            'is_configurable'          => false,
            'searchable'               => false,
            'filterable'               => false,
            'comparable'               => false,
            'unique'                   => false,
            'user_defined'             => true,
            'default'                  => 0,
            'is_user_defined'          => false,
            'used_in_product_listing'  => false,
        ];

        $this->createAttribute('ignore_datafeedwatch', $properties);
    }

    /**
     * @param $attributeCode
     * @param array $attributeProperties
     * @param string $entityType
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Validate_Exception
     */
    public function createAttribute($attributeCode, array $attributeProperties, $entityType = Product::ENTITY)
    {
        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->setup]);
        try {
            $this->attributeRepository->get(Product::ENTITY, $attributeCode);
        } catch (NoSuchEntityException $e) {
            $eavSetup->addAttribute(
                $entityType,
                $attributeCode,
                $attributeProperties
            );
            $this->cache->clean(\Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG, ['eav']);
        }
    }
}