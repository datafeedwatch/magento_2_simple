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

class ProductUrl
{
    const URL_REWRITE_TABLE = 'url_rewrite';

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
        if ($product->getVisibility() != Visibility::VISIBILITY_NOT_VISIBLE) {
            $extensionAttributes = $product->getExtensionAttributes();
            $extensionAttributes = $extensionAttributes ?? $this->extensionFactory->create();
            $extensionAttributes->setProductUrl($this->getUrl($product));
            $extensionAttributes->setAdditionalUrls($this->getAdditionalUrls($product));
            $product->setExtensionAttributes($extensionAttributes);
        }
        return $product;
    }

    /**
     * @param Product $productSku
     * @return string
     */
    protected function getUrl(Product $product)
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName(self::URL_REWRITE_TABLE);

        $query = sprintf(
            "SELECT `request_path` FROM `%s` WHERE `entity_id` = '%s' AND `store_id` = %s AND `entity_type` = 'product' AND `metadata` IS NULL",
            $tableName,
            $product->getId(),
            $product->getStoreId()
        );

        $productPath = $connection->fetchOne($query);

        return $productPath ? rtrim($this->storeManager->getStore($product->getStoreId())->getUrl($productPath), '/') : '';
    }

    /**
     * @param Product $productSku
     * @return array
     */
    protected function getAdditionalUrls(Product $product)
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName(self::URL_REWRITE_TABLE);

        $query = sprintf(
            "SELECT `request_path` FROM `%s` WHERE `entity_id` = '%s' AND `store_id` = %s AND `entity_type` = 'product' AND `metadata` IS NOT NULL",
            $tableName,
            $product->getId(),
            $product->getStoreId()
        );

        $store = $this->storeManager->getStore($product->getStoreId());

        return array_map(function ($path) use ($store) { return rtrim($store->getUrl($path), '/'); }, $connection->fetchCol($query));
    }
}
