<?php
/**
 * Created by Q-Solutions Studio
 * Date: 02.07.19
 *
 * @category    Dfw
 * @package     Dfw_Connector
 * @author      Maciej Buchert <maciej@qsolutionsstudio.com>
 */

namespace Dfw\Connector\Model\ResourceModel\Product;

use Dfw\Connector\Model\ResourceModel\Product\Collection\Db;
use Magento\Catalog\Model\ResourceModel\Product;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Zend_Db_Select_Exception;

/**
 * Class Collection
 * @package Dfw\Connector\Model\ResourceModel\Product
 */
class Collection extends Db
{
    /**
     * @return bool
     */
    public function isEnabledFlat()
    {
        return false;
    }

    public function _construct()
    {
        $this->_init(
            \Dfw\Connector\Model\Product::class,
            Product::class
        );
        $this->_initTables();
    }

    /**
     * @param bool $joinLeft
     * @return $this|Db
     */
    public function _productLimitationPrice($joinLeft = true)
    {
        parent::_productLimitationPrice($joinLeft);
        return $this;
    }

    /**
     * @param $options
     * @return $this
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws Zend_Db_Select_Exception
     */
    public function applyFiltersOnCollection($options)
    {
        $this->optionsFilters = $options;

        $this->setFlag('has_stock_status_filter', true);
        $this->joinRelationTable();
        $this->applyStoreFilter();
        $this->registryHelper->initImportRegistry($this->getStoreId());
        $this->joinVisibilityTable(Db::VISIBILITY_TABLE_ALIAS_DEFAULT_STORE, '0');
        $this->joinVisibilityTable(Db::ORIGINAL_VISIBILITY_TABLE_ALIAS, $this->getStoreId());
        $this->addRuleDate();
        $this->joinQty();
        $this->addFinalPrice();
        $this->addUrlRewrite();
        $this->applyStatusFilter();
        $this->applyUpdatedAtFilter();
        $this->applyTypeFilter();
        $this->addAttributeToSelect('ignore_datafeedwatch');
        $this->addAttributeToFilter('ignore_datafeedwatch', [['null' => true], ['neq' => 1]], 'left');

        $this->setPage($this->optionsFilters['page'], $this->optionsFilters['per_page']);

        return $this;
    }

    /**
     * @return $this
     * @throws NoSuchEntityException
     */
    public function applyStoreFilter()
    {
        if (isset($this->optionsFilters['store'])) {
            $store          = $this->_storeManager->getStore($this->optionsFilters['store']);
            $StoreColumn    = sprintf('IFNULL(null, %s) as store_id', $store->getId());
            $this->setStoreId($store->getId());
            $this->addStoreFilter($store);
            $this->getSelect()->columns($StoreColumn);
        }

        return $this;
    }

    /**
     * @return $this
     * @throws Zend_Db_Select_Exception
     */
    public function applyStatusFilter()
    {
        if (!isset($this->optionsFilters['status'])) {
            return $this;
        }

        if ($this->registryHelper->isStatusAttributeInheritable()) {
            $this->buildFilterStatusCondition();
            $this->joinInheritedStatusTable(self::INHERITED_STATUS_TABLE_ALIAS, $this->getStoreId())
                ->joinInheritedStatusTable(self::INHERITED_STATUS_TABLE_ALIAS_DEFAULT_STORE, '0')
                ->joinOriginalStatusTable(self::ORIGINAL_STATUS_TABLE_ALIAS, $this->getStoreId())
                ->joinOriginalStatusTable(self::ORIGINAL_STATUS_TABLE_ALIAS_DEFAULT_STORE, '0');
            $this->getSelect()->where($this->filterStatusCondition . ' = ?', $this->optionsFilters['status']);
        } else {
            $this->addAttributeToFilter('status', $this->optionsFilters['status']);
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function applyTypeFilter()
    {
        if (isset($this->optionsFilters['type'])) {
            $this->addAttributeToFilter('type_id', ['in' => $this->optionsFilters['type']]);
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function applyUpdatedAtFilter()
    {
        if (!isset($this->optionsFilters['from_date'])) {
            return $this;
        }

        $this->getSelect()->where($this->ruleDateSelect . ' >= ?', $this->optionsFilters['from_date']);

        return $this;
    }

    /**
     * @return $this
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function applyInheritanceLogic()
    {
        $this->addParentData();
        foreach ($this->getItems() as $product) {
            $parent = $product->getParent();
            if (!empty($parent)) {
                $product->getParentAttributes();
            }
        }

        return $this;
    }

    /**
     * @return $this
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function addParentData()
    {
        $parentCollection = $this->getParentProductsCollection();
        $parentCollection = $parentCollection->getItems();
        foreach ($this->getItems() as $product) {
            $parentId = $product->getParentId();
            $parentId = explode(',', $parentId);
            if (is_array($parentId)) {
                $parentId = current($parentId);
            }
            $parentId = !is_numeric($parentId) ? 0 : (string)$parentId;

            if (empty($parentId) || !isset($parentCollection[$parentId])) {
                continue;
            }
            $product->setParent($parentCollection[$parentId]);
        }

        return $this;
    }

    /**
     * @return Collection
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getParentProductsCollection()
    {
        $parentCollection = clone $this;
        $parentCollection->_reset();
        $parentCollection->addAttributeToSelect('*')
            ->addUrlRewrite()
            ->joinQty()
            ->addFinalPrice();
        $store = $this->_storeManager->getStore($this->optionsFilters['store']);
        $StoreColumn    = sprintf('IFNULL(null, %s) as store_id', $store->getId());
        $parentCollection->setStoreId($store->getId());
        $parentCollection->addStoreFilter($store);
        $parentCollection->getSelect()->columns($StoreColumn);
        $parentCollection->getSelect()->joinLeft(
            [
                self::PARENT_CONFIGURABLE_ATTRIBUTES_TABLE_ALIAS =>
                    $this->_resource->getTableName('catalog_product_super_attribute'),
            ],
            sprintf('%s.product_id = e.entity_id', self::PARENT_CONFIGURABLE_ATTRIBUTES_TABLE_ALIAS),
            [
                'super_attribute_ids' =>
                    sprintf('GROUP_CONCAT(DISTINCT %s.attribute_id)', self::PARENT_CONFIGURABLE_ATTRIBUTES_TABLE_ALIAS),
            ]
        );

        $parentCollection->getSelect()->joinRight(
            [self::PARENT_RELATIONS_TABLE_ALIAS => $this->_resource->getTableName('catalog_product_relation')],
            sprintf('%s.parent_id = e.entity_id', self::PARENT_RELATIONS_TABLE_ALIAS),
            ['parent_id' => sprintf('%s.parent_id', self::PARENT_RELATIONS_TABLE_ALIAS)]
        )->group('e.entity_id');

        return $parentCollection;
    }
}