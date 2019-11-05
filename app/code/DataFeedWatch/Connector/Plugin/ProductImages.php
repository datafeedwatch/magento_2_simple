<?php
/**
 * Created by Q-Solutions Studio
 * Date: 17.10.2019
 *
 * @category    DataFeedWatch
 * @package     DataFeedWatch_Connector
 * @author      Wojciech M. Wnuk <wojtek@qsolutionsstudio.com>
 */

namespace DataFeedWatch\Connector\Plugin;

use Magento\Catalog\Api\Data\ProductExtensionFactory;
use Magento\Catalog\Model\ProductRepository;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Framework\Api\SearchResults;
use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\StoreManagerInterface;

class ProductImages
{
    /**
     * @var ProductExtensionFactory
     */
    protected $extensionFactory;

    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * ParentIds constructor.
     * @param ProductExtensionFactory $extensionFactory
     * @param ResourceConnection $resourceConnection
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ProductExtensionFactory $extensionFactory,
        ResourceConnection $resourceConnection,
        StoreManagerInterface $storeManager
    ) {
        $this->extensionFactory = $extensionFactory;
        $this->resourceConnection = $resourceConnection;
        $this->storeManager = $storeManager;
    }

    /**
     * @param ProductRepository $subject
     * @param Product $product
     * @return Product
     */
    public function afterGet(
        ProductRepository $subject,
        Product $product
    ) {
        return $this->setExtensionAttribute($product);
    }

    /**
     * @param ProductRepository $subject
     * @param SearchResults $searchResults
     * @return SearchResults
     */
    public function afterGetList(
        ProductRepository $subject,
        SearchResults $searchResults
    ) {
        $products = $searchResults->getItems();

        /** @var Product $product */
        foreach ($products as $product) {
            $this->setExtensionAttribute($product);
        }
        return $searchResults;
    }

    /**
     * @param Product $product
     * @return Product
     */
    protected function setExtensionAttribute(Product $product)
    {
        if ($product->getTypeId() == Type::TYPE_SIMPLE) {
            $extensionAttributes = $product->getExtensionAttributes();
            $extensionAttributes = $extensionAttributes ?? $this->extensionFactory->create();
            $extensionAttributes->setProductImages($this->getProductImages($product));
            $product->setExtensionAttributes($extensionAttributes);
        }
        return $product;
    }

    /**
     * @param Product $productSku
     * @return array
     */
    protected function getProductImages(Product $product)
    {
        $images = $product->getMediaGalleryImages();
        $imagesData = [];

        foreach ($images as $image) {
            $imagesData[] = $image->getData('url');
        }

        return $imagesData;
    }
}
