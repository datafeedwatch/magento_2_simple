<?php
/**
 * Created by Q-Solutions Studio
 * Date: 01.07.19
 *
 * @category    Dfw
 * @package     Dfw_Connector
 * @author      Maciej Buchert <maciej@qsolutionsstudio.com>
 */

namespace Dfw\Connector\Model;

use Dfw\Connector\Helper\Registry;
use Dfw\Connector\Model\ResourceModel\Product\Collection;
use Dfw\Connector\Model\System\Config\Source\Inheritance;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\ProductLinkExtensionFactory;
use Magento\Catalog\Api\Data\ProductLinkInterfaceFactory;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Catalog\Helper\Data;
use Magento\Catalog\Model\Indexer\Product\Eav\Processor;
use Magento\Catalog\Model\Product as coreProduct;
use Magento\Catalog\Model\Product\Attribute\Backend\Media\EntryConverterPool;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Image\CacheFactory;
use Magento\Catalog\Model\Product\Link;
use Magento\Catalog\Model\Product\LinkTypeProvider;
use Magento\Catalog\Model\Product\Media\Config;
use Magento\Catalog\Model\Product\OptionFactory;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product\Url;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ProductLink\CollectionProvider;
use Magento\CatalogInventory\Api\Data\StockItemInterfaceFactory;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Eav\Model\Attribute;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\Data\CollectionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Framework\Model\Context;
use Magento\Framework\Module\Manager;
use Magento\Framework\Phrase;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class Product
 * @package Dfw\Connector\Model
 */
class Product extends coreProduct
{

    /**
     * @var array
     */
    public $importData = [];

    /**
     * @var \Dfw\Connector\Helper\Data
     */
    public $dataHelper;

    /**
     * @var Registry
     */
    public $registryHelper;

    /**
     * @var PriceCurrencyInterface
     */
    public $priceCurrency;

    /**
     * @var Data
     */
    public $catalogHelper;

    /**
     * @var TimezoneInterface
     */
    public $timezone;

    /**
     * Product constructor.
     * @param Context $context
     * @param \Magento\Framework\Registry $registry
     * @param ExtensionAttributesFactory $extensionFactory
     * @param AttributeValueFactory $customAttributeFactory
     * @param StoreManagerInterface $storeManager
     * @param ProductAttributeRepositoryInterface $metadataService
     * @param Url $url
     * @param Link $productLink
     * @param coreProduct\Configuration\Item\OptionFactory $itemOptionFactory
     * @param StockItemInterfaceFactory $stockItemFactory
     * @param OptionFactory $catalogProductOptionFactory
     * @param Visibility $catalogProductVisibility
     * @param Status $catalogProductStatus
     * @param Config $catalogProductMediaConfig
     * @param Type $catalogProductType
     * @param Manager $moduleManager
     * @param \Magento\Catalog\Helper\Product $catalogProduct
     * @param \Magento\Catalog\Model\ResourceModel\Product $resource
     * @param Collection $resourceCollection
     * @param CollectionFactory $collectionFactory
     * @param Filesystem $filesystem
     * @param IndexerRegistry $indexerRegistry
     * @param \Magento\Catalog\Model\Indexer\Product\Flat\Processor $productFlatIndexerProcessor
     * @param \Magento\Catalog\Model\Indexer\Product\Price\Processor $productPriceIndexerProcessor
     * @param Processor $productEavIndexerProcessor
     * @param CategoryRepositoryInterface $categoryRepository
     * @param CacheFactory $imageCacheFactory
     * @param CollectionProvider $entityCollectionProvider
     * @param LinkTypeProvider $linkTypeProvider
     * @param ProductLinkInterfaceFactory $productLinkFactory
     * @param ProductLinkExtensionFactory $productLinkExtensionFactory
     * @param EntryConverterPool $mediaGalleryEntryConverterPool
     * @param DataObjectHelper $dataObjectHelper
     * @param JoinProcessorInterface $joinProcessor
     * @param \Dfw\Connector\Helper\Data $dataHelper
     * @param TimezoneInterface $timezone
     * @param Registry $registryHelper
     * @param PriceCurrencyInterface $priceCurrency
     * @param Data $catalogHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        \Magento\Framework\Registry $registry,
        ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $customAttributeFactory,
        StoreManagerInterface $storeManager,
        ProductAttributeRepositoryInterface $metadataService,
        Url $url,
        Link $productLink,
        \Magento\Catalog\Model\Product\Configuration\Item\OptionFactory $itemOptionFactory,
        StockItemInterfaceFactory $stockItemFactory,
        OptionFactory $catalogProductOptionFactory,
        Visibility $catalogProductVisibility,
        Status $catalogProductStatus,
        Config $catalogProductMediaConfig,
        Type $catalogProductType,
        Manager $moduleManager,
        \Magento\Catalog\Helper\Product $catalogProduct,
        \Magento\Catalog\Model\ResourceModel\Product $resource,
        Collection $resourceCollection,
        CollectionFactory $collectionFactory,
        Filesystem $filesystem,
        IndexerRegistry $indexerRegistry,
        \Magento\Catalog\Model\Indexer\Product\Flat\Processor $productFlatIndexerProcessor,
        \Magento\Catalog\Model\Indexer\Product\Price\Processor $productPriceIndexerProcessor,
        Processor $productEavIndexerProcessor,
        CategoryRepositoryInterface $categoryRepository,
        CacheFactory $imageCacheFactory,
        CollectionProvider $entityCollectionProvider,
        LinkTypeProvider $linkTypeProvider,
        ProductLinkInterfaceFactory $productLinkFactory,
        ProductLinkExtensionFactory $productLinkExtensionFactory,
        EntryConverterPool $mediaGalleryEntryConverterPool,
        DataObjectHelper $dataObjectHelper,
        JoinProcessorInterface $joinProcessor,
        \Dfw\Connector\Helper\Data $dataHelper,
        TimezoneInterface $timezone,
        Registry $registryHelper,
        PriceCurrencyInterface $priceCurrency,
        Data $catalogHelper,
        array $data = []
    ) {
        $this->dataHelper       = $dataHelper;
        $this->timezone         = $timezone;
        $this->registryHelper   = $registryHelper;
        $this->priceCurrency    = $priceCurrency;
        $this->catalogHelper    = $catalogHelper;
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $storeManager,
            $metadataService,
            $url,
            $productLink,
            $itemOptionFactory,
            $stockItemFactory,
            $catalogProductOptionFactory,
            $catalogProductVisibility,
            $catalogProductStatus,
            $catalogProductMediaConfig,
            $catalogProductType,
            $moduleManager,
            $catalogProduct,
            $resource,
            $resourceCollection,
            $collectionFactory,
            $filesystem,
            $indexerRegistry,
            $productFlatIndexerProcessor,
            $productPriceIndexerProcessor,
            $productEavIndexerProcessor,
            $categoryRepository,
            $imageCacheFactory,
            $entityCollectionProvider,
            $linkTypeProvider,
            $productLinkFactory,
            $productLinkExtensionFactory,
            $mediaGalleryEntryConverterPool,
            $dataObjectHelper,
            $joinProcessor,
            $data
        );
    }

    public function _construct()
    {
        $this->_init(\Magento\Catalog\Model\ResourceModel\Product::class);
    }

    /**
     * @return array
     * @throws LocalizedException
     */
    public function getDataToImport()
    {
        /** @var Product $parent */
        $parent = $this->getParent();
        if ($this->registryHelper->isStatusAttributeInheritable()) {
            $this->setStatus($this->getFilterStatus());
        }
        $date = $this->getRuleDate();
        $this->setUpdatedAt($this->timezone->convertConfigTimeToUtc($date, 'Y-m-d H:i:s'));
        $this->fillAllAttributesData();
        $this->importData['product_id']                 = $this->getId();
        $this->importData['sku']                        = $this->getSku();
        $this->importData['product_type']               = $this->getTypeId();
        $this->importData['quantity']                   = (int) $this->getQty();
        $this->importData['currency_code']              = $this->getStore()->getCurrentCurrencyCode();
        $this->importData['base_price']                 = $this->getImportFinalPrice(false);
        $this->importData['base_price_with_tax']        = $this->getImportPrice(true);
        $this->importData['price']                      = $this->getImportPrice(false);
        $this->importData['price_with_tax']             = $this->getImportFinalPrice(true);
        $this->importData['special_price']              = $this->getImportSpecialPrice(false);
        $this->importData['special_price_with_tax']     = $this->getImportSpecialPrice(true);
        $this->importData['special_from_date']          = $this->getSpecialFromDate();
        $this->importData['special_to_date']            = $this->getSpecialToDate();
        $this->importData['image_url']                  = $this->getBaseImageUrl();
        $this->importData['product_url']                = $this->getProductUrl();
        $this->importData['product_url_rewritten']      = $this->getProductUrl();
        $this->importData['is_in_stock']                = (int) $this->getQuantityAndStockStatus()['is_in_stock'];
        $this->getCategoryPathToImport();
        $this->setDataToImport($this->getCategoriesNameToImport(false));

        if (!empty($parent)) {
            $this->importData['parent_id']                      = $parent->getId();
            $this->importData['parent_sku']                     = $parent->getSku();
            $this->importData['parent_base_price']              = $parent->getImportPrice(false);
            $this->importData['parent_base_price_with_tax']     = $parent->getImportPrice(true);
            $this->importData['parent_price']                   = $parent->getImportFinalPrice(false);
            $this->importData['parent_price_with_tax']          = $parent->getImportFinalPrice(true);
            $this->importData['parent_special_price']           = $parent->getImportSpecialPrice(false);
            $this->importData['parent_special_price_with_tax']  = $parent->getImportSpecialPrice(true);
            $this->importData['parent_special_from_date']       = $parent->getSpecialFromDate();
            $this->importData['parent_special_to_date']         = $parent->getSpecialToDate();
            $this->importData['parent_url']                     = $parent->getProductUrl();

            if ($this->dataHelper->isProductUrlInherited()) {
                $this->importData['product_url'] = $this->importData['parent_url'];
            }
            $this->setDataToImport($parent->getCategoriesNameToImport(true));
            if ($parent->isConfigurable()) {
                $this->importData['variant_spac_price']             = $this->getVariantSpacPrice(false);
                $this->importData['variant_spac_price_with_tax']    = $this->getVariantSpacPrice(true);
                $this->importData['variant_name']                   = $this->getName();
                $this->getDfwDefaultVariant();
            }
        }

        $this->getExcludedImages();
        $this->setDataToImport($this->getAdditionalImages($this->importData['image_url'], false));
        if (!empty($parent)) {
            if ($this->dataHelper->isImageUrlInherited()) {
                $this->importData['image_url'] = $parent->getBaseImageUrl();
            }
            $this->setDataToImport($parent->getAdditionalImages($this->importData['image_url'], true));
        }

        return $this->importData;
    }

    /**
     * @return bool
     */
    public function isConfigurable()
    {
        return $this->getTypeId() === Configurable::TYPE_CODE;
    }

    /**
     * @return $this
     * @throws LocalizedException
     */
    public function fillAllAttributesData()
    {
        $productAttributes = array_keys($this->getAttributes());
        $attributeCollection = $this->_registry->registry(Registry::ALL_IMPORTABLE_ATTRIBUTES_KEY);
        /** @var Attribute $attribute */
        foreach ($attributeCollection as $attribute) {
            $attributeCode = $attribute->getAttributeCode();
            if (empty($attributeCode) || !in_array($attributeCode, $productAttributes)) {
                continue;
            }
            if ('status' === $attributeCode) {
                $this->importData[$attributeCode] = $this->getStatus() == 1 ? 'Enabled' : 'Disabled';
                continue;
            }
            if ($attribute->usesSource()) {
                $value = $attribute->getSource()->getOptionText($this->getData($attributeCode));
            } else {
                $value = $attribute->getFrontend()->getValue($this);
            }
            if ($value instanceof Phrase) {
                $value = $value->getText();
            } elseif ($value === false) {
                $value = '';
            }

            $this->importData[$attributeCode] = $value;
        }

        return $this;
    }

    /**
     * @param bool $withTax
     * @return float
     */
    public function getImportFinalPrice($withTax = false)
    {
        $price = round($this->priceCurrency->convert($this->getFinalPrice()), 2);
        return $this->catalogHelper->getTaxPrice($this, $price, $withTax);
    }

    /**
     * @param bool $withTax
     * @return float
     */
    public function getImportPrice($withTax = false)
    {
        $price = round($this->priceCurrency->convert($this->getPrice()), 2);
        return $this->catalogHelper->getTaxPrice($this, $price, $withTax);
    }

    /**
     * @param bool $withTax
     * @return float
     */
    public function getImportSpecialPrice($withTax = false)
    {
        return $this->catalogHelper->getTaxPrice($this, $this->getSpecialPrice(), $withTax);
    }

    /**
     * @return string|null
     */
    public function getBaseImageUrl()
    {
        $this->load('image');
        $image = $this->getImage();
        if ($image !== 'no_selection' && !empty($image)) {
            return $this->getMediaConfig()->getMediaUrl($image);
        }

        return null;
    }

    /**
     * @return $this
     */
    public function getCategoryPathToImport()
    {
        $index = '';
        $categoriesCollection = $this->_registry->registry(Registry::ALL_CATEGORIES_ARRAY_KEY);
        foreach ($this->getCategoryCollection()->addNameToResult() as $category) {
            $categoryName = [];
            $path = $category->getPath();
            foreach (explode('/', $path) as $categoryId) {
                if (isset($categoriesCollection[$categoryId])) {
                    $categoryName[] = $categoriesCollection[$categoryId]->getName();
                }
            }
            if (!empty($categoryName)) {
                $key = 'category_path' . $index;
                $this->importData[$key] = implode(' > ', $categoryName);
                $index++;
            }
        }

        return $this;
    }

    /**
     * @param bool $isParent
     * @return array
     */
    public function getCategoriesNameToImport($isParent = false)
    {
        $index = '';
        $names = [];
        foreach ($this->getCategoryCollection()->addNameToResult() as $category) {
            $key            = $isParent ? 'category_parent_name' : 'category_name';
            $key            .= $index++;
            $names[$key]    = $category->getName();
        }

        return $names;
    }

    /**
     * @param array $data
     */
    public function setDataToImport($data)
    {
        foreach ($data as $key => $value) {
            $this->importData[$key] = $value;
        }
    }

    /**
     * @param bool $withTax
     * @return float
     */
    public function getVariantSpacPrice($withTax = false)
    {

        $finalPrice = $this->getPriceInfo()->getPrice('final_price')->getAmount()->getBaseAmount();

        return $this->catalogHelper->getTaxPrice($this, $finalPrice, $withTax);
    }

    /**
     * @return $this
     */
    public function getDfwDefaultVariant()
    {
        $parent = $this->getParent();
        if (empty($parent)) {
            return $this;
        }

        $superAttributes = $this->_registry->registry(Registry::ALL_SUPER_ATTRIBUTES_KEY);
        $parentSuperAttributes                      = $parent->getData('super_attribute_ids');
        $parentSuperAttributes                      = explode(',', $parentSuperAttributes);
        $this->importData['dfw_default_variant']    = 1;
        foreach ($parentSuperAttributes as $superAttributeId) {
            if (!isset($superAttributes[$superAttributeId])) {
                continue;
            }
            $superAttribute = $superAttributes[$superAttributeId];
            $defaultValue   = $superAttribute->getDefaultValue();
            if (!empty($defaultValue) && $defaultValue !== $this->getData($superAttribute->getAttributeCode())) {
                $this->importData['dfw_default_variant'] = 0;

                return $this;
            }
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function getExcludedImages()
    {
        $this->load('media_gallery');
        $gallery    = $this->getMediaGallery('images');
        $index      = 1;
        foreach ($gallery as $image) {
            if ($image['disabled']) {
                $imageUrl               = $this->getMediaConfig()->getMediaUrl($image['file']);
                $key                    = 'image_url_excluded' . $index;
                $this->importData[$key] = $imageUrl;
                $index++;
            }
        }

        return $this;
    }

    /**
     * @param null $importedBaseImage
     * @param bool $isParent
     * @return array
     */
    public function getAdditionalImages($importedBaseImage = null, $isParent = false)
    {
        if (empty($importedBaseImage)) {
            $importedBaseImage = $this->getBaseImageUrl();
        }
        $this->load('media_gallery');
        $gallery            = $this->getMediaGalleryImages();

        $index              = 1;
        $additionalImages   = [];
        foreach ($gallery as $image) {
            $imageUrl = $image->getUrl();
            if ($imageUrl !== $importedBaseImage && $imageUrl !== 'no_selection' && !empty($imageUrl)) {
                $key                    = $isParent ? 'parent_additional_image_url' : 'product_additional_image_url';
                $key                    .= $index++;
                $additionalImages[$key] = $imageUrl;
            }
        }

        return $additionalImages;
    }

    /**
     * @return $this
     */
    public function getParentAttributes()
    {
        $parent = $this->getParent();
        if (empty($parent)) {
            return $this;
        }
        $allAttributes = $this->_registry->registry(Registry::ALL_ATTRIBUTE_COLLECTION_KEY);
        foreach ($allAttributes as $attribute) {
            $attributeCode = $attribute->getAttributeCode();
            switch ($attribute->getInheritance()) {
                case (string) Inheritance::CHILD_THEN_PARENT_OPTION_ID:
                    $productData = $this->getData($attributeCode);
                    if (empty($productData) || $this->shouldChangeVisibilityForProduct($attribute)) {
                        $parentData = $parent->getData($attributeCode);
                        $this->setData($attributeCode, $parentData);
                    }
                    break;
                case (string) Inheritance::PARENT_OPTION_ID:
                    $parentData = $parent->getData($attributeCode);
                    $this->setData($attributeCode, $parentData);
                    break;
            }
        }

        return $this;
    }

    /**
     * @param $attribute
     * @return bool
     */
    public function shouldChangeVisibilityForProduct($attribute)
    {
        $attributeCode = $attribute->getAttributeCode();

        return $attributeCode === 'visibility'
               && (int)$this->getData($attributeCode) === Visibility::VISIBILITY_NOT_VISIBLE;
    }
}
