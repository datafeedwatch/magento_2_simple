<?php

namespace Dfw\Connector\Plugin;

use Magento\Catalog\Api\Data\ProductExtensionFactory;
use Magento\Catalog\Model\ProductRepository;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type;
use Magento\Framework\Api\SearchResults;
use Magento\Framework\App\ResourceConnection;

/**
 * Class ParentIds
 * @package Dfw\Connector\Plugin
 */
class ParentIds
{
    const RELATIONS_TABLE = "catalog_product_relation";

    /**
     * @var ProductExtensionFactory
     */
    protected $extensionFactory;

    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;

    /**
     * ParentIds constructor.
     * @param ProductExtensionFactory $extensionFactory
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        ProductExtensionFactory $extensionFactory,
        ResourceConnection $resourceConnection
    ) {
        $this->extensionFactory = $extensionFactory;
        $this->resourceConnection = $resourceConnection;
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
        if($product->getTypeId() == Type::TYPE_SIMPLE) {
            $extensionAttributes = $product->getExtensionAttributes();
            $extensionAttributes = $extensionAttributes ?? $this->extensionFactory->create();
            $extensionAttributes->setParentIds(
                $this->getParentIds($product->getId())
            );
            $product->setExtensionAttributes($extensionAttributes);
        }
        return $product;
    }

    /**
     * @param integer $productId
     * @return array
     */
    protected function getParentIds($productId)
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName(self::RELATIONS_TABLE);

        $query = sprintf("SELECT parent_id FROM %s WHERE child_id = %s", $tableName, $productId);

        return $connection->fetchCol($query);
    }
}