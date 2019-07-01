<?php
/**
 * Created by Q-Solutions Studio
 * Date: 22.08.16
 *
 * @category    DataFeedWatch
 * @package     DataFeedWatch_Connector
 * @author      Lukasz Owczarczuk <lukasz@qsolutionsstudio.com>
 */

namespace Dfw\Connector\Helper;

use DataFeedWatch\Connector\Model\System\Config\Source\Inheritance as InheritanceSource;
use Magento\Framework\App\Helper\AbstractHelper;

class Data extends AbstractHelper
{
    const MY_DATA_FEED_WATCH_URL               = 'https://my.datafeedwatch.com/';
    const RUN_CRON_INSTALLER                   = 'datafeedwatch_connector/general/run_cron_installer';
    const PRODUCT_URL_CUSTOM_INHERITANCE_XPATH = 'datafeedwatch_connector/custom_inheritance/product_url';
    const IMAGE_URL_CUSTOM_INHERITANCE_XPATH   = 'datafeedwatch_connector/custom_inheritance/image_url';
    const LAST_CATALOGRULE_PRICE_ID_XPATH      = 'datafeedwatch_connector/custom_inheritance/last_catalogrule_price_id';
    const LAST_INHERITANCE_UPDATE_XPATH        = 'datafeedwatch_connector/custom_inheritance/last_inheritance_update';
    
    /** @var \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory */
    private $collectionFactory;
    
    /** @var \Magento\Config\Model\ResourceModel\Config */
    private $resourceConfig;
    
    /** @var \Magento\Framework\App\Config\ReinitableConfigInterface */
    private $appConfig;
    
    /** @var \Magento\Framework\App\Cache\Frontend\Pool */
    private $cacheFrontendPool;
    
    /**
     * @param \Magento\Framework\App\Helper\Context                                    $context
     * @param \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory $collectionFactory
     * @param \Magento\Config\Model\ResourceModel\Config                               $resourceConfig
     * @param \Magento\Framework\App\Config\ReinitableConfigInterface                  $appConfig
     * @param \Magento\Framework\App\Cache\Frontend\Pool                               $cacheFrontendPool
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory $collectionFactory,
        \Magento\Config\Model\ResourceModel\Config $resourceConfig,
        \Magento\Framework\App\Config\ReinitableConfigInterface $appConfig,
        \Magento\Framework\App\Cache\Frontend\Pool $cacheFrontendPool
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->resourceConfig    = $resourceConfig;
        $this->appConfig         = $appConfig;
        $this->cacheFrontendPool = $cacheFrontendPool;
        parent::__construct($context);
    }

    /**
     * @return bool
     */
    public function isProductUrlInherited()
    {
        return $this->scopeConfig->isSetFlag(self::PRODUCT_URL_CUSTOM_INHERITANCE_XPATH);
    }

    /**
     * @return bool
     */
    public function isImageUrlInherited()
    {
        return $this->scopeConfig->isSetFlag(self::IMAGE_URL_CUSTOM_INHERITANCE_XPATH);
    }

    /**
     * @return string
     */
    public function getLastCatalogRulePriceId()
    {
        return $this->scopeConfig->getValue(self::LAST_CATALOGRULE_PRICE_ID_XPATH);
    }

    /**
     * @param string|int $id
     */
    public function setLastCatalogRulePriceId($id)
    {
        $this->resourceConfig->saveConfig(self::LAST_CATALOGRULE_PRICE_ID_XPATH, $id, 'default', 0);
    }

    /**
     * @return string
     */
    public function getLastInheritanceUpdateDate()
    {
        return $this->scopeConfig->getValue(self::LAST_INHERITANCE_UPDATE_XPATH);
    }

    public function updateLastInheritanceUpdateDate()
    {
        $this->setLastInheritanceUpdateDate(date('Y-m-d H:i:s'));
    }

    /**
     * @param string $date
     */
    public function setLastInheritanceUpdateDate($date)
    {
        $this->resourceConfig->saveConfig(self::LAST_INHERITANCE_UPDATE_XPATH, $date, 'default', 0);
        $this->appConfig->reinit();
    }
    
    /**
     * @return string
     */
    public function getDataFeedWatchUrl()
    {
        return self::MY_DATA_FEED_WATCH_URL;
    }
    
    public function restoreOriginalAttributesConfig()
    {
        $cannotConfigureImportField = [
            'name',
            'description',
            'short_description',
            'tax_class_id',
            'visibility',
            'status',
            'meta_title',
            'meta_keyword',
            'meta_description',
            'media_gallery',
            'gallery',
            'image',
            'small_image',
            'thumbnail',
            'price',
            'special_price',
            'special_from_date',
            'special_to_date',
            'sku',
            'updated_at',
            'ignore_datafeedwatch',
            'quantity_and_stock_status',
            'options_container',
            'sku_type',
            'weight_type',
            'price_type',
            'page_layout',
            'custom_layout_update',
            'custom_layout',
            'price_view',
            'swatch_image',
        ];
        
        $cannotConfigureInheritanceField = [
            'sku',
            'price',
            'special_price',
            'special_from_date',
            'special_to_date',
            'media_gallery',
            'gallery',
            'image',
            'small_image',
            'thumbnail',
            'updated_at',
            'ignore_datafeedwatch',
            'quantity_and_stock_status',
            'options_container',
            'sku_type',
            'weight_type',
            'price_type',
            'page_layout',
            'custom_layout_update',
            'custom_layout',
            'price_view',
            'swatch_image',
        ];

        $enableImport = [
            'name',
            'description',
            'short_description',
            'tax_class_id',
            'visibility',
            'status',
            'meta_title',
            'meta_keyword',
            'meta_description',
            'sku',
            'price',
            'special_price',
            'special_from_date',
            'special_to_date',
            'updated_at',
            'color',
            'size',
            'gender',
            'manufacturer',
            'material',
        ];

        $inheritanceData = [
            'status'                    => InheritanceSource::CHILD_THEN_PARENT_OPTION_ID,
            'updated_at'                => InheritanceSource::PARENT_OPTION_ID,
            'ignore_datafeedwatch'      => InheritanceSource::CHILD_OPTION_ID,
            'quantity_and_stock_status' => InheritanceSource::CHILD_OPTION_ID,
            'options_container'         => InheritanceSource::CHILD_OPTION_ID,
            'sku_type'                  => InheritanceSource::CHILD_OPTION_ID,
            'weight_type'               => InheritanceSource::CHILD_OPTION_ID,
            'price_type'                => InheritanceSource::CHILD_OPTION_ID,
            'page_layout'               => InheritanceSource::CHILD_OPTION_ID,
            'custom_layout_update'      => InheritanceSource::CHILD_OPTION_ID,
            'custom_layout'             => InheritanceSource::CHILD_OPTION_ID,
            'price_view'                => InheritanceSource::CHILD_OPTION_ID,
            'swatch_image'              => InheritanceSource::CHILD_OPTION_ID,
        ];

        /** @var \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection $catalogAttributes */
        $catalogAttributes = $this->collectionFactory->create();
        foreach ($catalogAttributes as $attribute) {
            $attribute->setData('can_configure_inheritance', null);
            $attribute->setData('inheritance', null);
            $attribute->setData('can_configure_import', null);
            $attribute->setData('import_to_dfw', null);
            $attribute->setData('force_save', true);
            $attributeCode = $attribute->getAttributeCode();
            $inheritance   = InheritanceSource::CHILD_OPTION_ID;
            if (array_key_exists($attributeCode, $inheritanceData)) {
                $inheritance = $inheritanceData[$attributeCode];
            }
            $attribute->setImportToDfw((int)in_array($attributeCode, $enableImport))
                      ->setCanConfigureImport((int)!in_array($attributeCode, $cannotConfigureImportField))
                      ->setCanConfigureInheritance((int)!in_array($attributeCode, $cannotConfigureInheritanceField))
                      ->setInheritance($inheritance);
        }
        $catalogAttributes->walk('save');
        
        $this->resourceConfig->saveConfig(self::PRODUCT_URL_CUSTOM_INHERITANCE_XPATH, 1, 'default', 0);
        $this->resourceConfig->saveConfig(self::IMAGE_URL_CUSTOM_INHERITANCE_XPATH, 0, 'default', 0);
    }
}
