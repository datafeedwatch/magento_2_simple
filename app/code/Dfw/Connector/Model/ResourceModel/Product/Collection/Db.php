<?php
/**
 * Created by Q-Solutions Studio
 * Date: 01.07.19
 *
 * @category    Dfw
 * @package     Dfw_Connector
 * @author      Maciej Buchert <maciej@qsolutionsstudio.com>
 */

namespace Dfw\Connector\Model\ResourceModel\Product\Collection;

use Dfw\Connector\Cron\FillUpdatedAtTable;
use Dfw\Connector\Helper\Registry;
use Magento\Catalog\Model\Indexer\Product\Flat\State;
use Magento\Catalog\Model\Product\OptionFactory;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Helper;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Model\ResourceModel\Url;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Customer\Api\GroupManagementInterface;
use Magento\Customer\Model\Session;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\EntityFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Module\Manager;
use Magento\Framework\Stdlib\DateTime;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\Validator\UniversalFactory;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Zend_Db_Expr;
use Zend_Db_Select;
use Zend_Db_Select_Exception;

/**
 * Class Db
 * @package Dfw\Connector\Model\ResourceModel\Product\Collection
 */
class Db extends Collection
{
    const INHERITED_STATUS_TABLE_ALIAS               = 'inherited_status';
    const INHERITED_STATUS_TABLE_ALIAS_DEFAULT_STORE = 'inherited_status_default_store';
    const ORIGINAL_STATUS_TABLE_ALIAS                = 'original_status';
    const ORIGINAL_STATUS_TABLE_ALIAS_DEFAULT_STORE  = 'status_default_store';
    const ORIGINAL_VISIBILITY_TABLE_ALIAS            = 'original_visibility';
    const VISIBILITY_TABLE_ALIAS_DEFAULT_STORE       = 'visibility_default_store';
    const MIXED_STATUS_COLUMN_ALIAS                  = 'filter_status';
    const PARENT_CONFIGURABLE_ATTRIBUTES_TABLE_ALIAS = 'parent_configurable_attributes';
    const PARENT_RELATIONS_TABLE_ALIAS               = 'parent_relation';
    const UPDATED_AT_TABLE_ALIAS                     = 'custom_updated_at';
    const CATALOGRULE_DATE_COLUMN_ALIAS              = 'rule_date';

    /**
     * @var array
     */
    public $optionsFilters;

    /**
     * @var string
     */
    public $filterStatusCondition;

    /**
     * @var string
     */
    public $ruleDateSelect;

    /**
     * @var Registry
     */
    public $registryHelper;

    /**
     * @var \Magento\Framework\Registry
     */
    public $registry;

    /**
     * @var CollectionFactory
     */
    public $productCollectionFactory;

    /**
     * @var Configurable
     */
    public $typeConfigurable;

    /**
     * @var FillUpdatedAtTable
     */
    public $cron;

    /**
     * Db constructor.
     * @param Registry $registryHelper
     * @param \Magento\Framework\Registry $registry
     * @param CollectionFactory $productCollectionFactory
     * @param Configurable $typeConfigurable
     * @param FillUpdatedAtTable $cron
     * @param \Magento\Framework\Data\Collection\EntityFactory $entityFactory
     * @param LoggerInterface $logger
     * @param FetchStrategyInterface $fetchStrategy
     * @param ManagerInterface $eventManager
     * @param Config $eavConfig
     * @param ResourceConnection $resource
     * @param EntityFactory $eavEntityFactory
     * @param Helper $resourceHelper
     * @param UniversalFactory $universalFactory
     * @param StoreManagerInterface $storeManager
     * @param Manager $moduleManager
     * @param State $catalogProductFlatState
     * @param ScopeConfigInterface $scopeConfig
     * @param OptionFactory $productOptionFactory
     * @param Url $catalogUrl
     * @param TimezoneInterface $localeDate
     * @param Session $customerSession
     * @param DateTime $dateTime
     * @param GroupManagementInterface $groupManagement
     * @param null $connection
     */
    public function __construct(
        Registry $registryHelper,
        \Magento\Framework\Registry $registry,
        CollectionFactory $productCollectionFactory,
        Configurable $typeConfigurable,
        FillUpdatedAtTable $cron,
        \Magento\Framework\Data\Collection\EntityFactory $entityFactory,
        LoggerInterface $logger,
        FetchStrategyInterface $fetchStrategy,
        ManagerInterface $eventManager,
        Config $eavConfig,
        ResourceConnection $resource,
        EntityFactory $eavEntityFactory,
        Helper $resourceHelper,
        UniversalFactory $universalFactory,
        StoreManagerInterface $storeManager,
        Manager $moduleManager,
        State $catalogProductFlatState,
        ScopeConfigInterface $scopeConfig,
        OptionFactory $productOptionFactory,
        Url $catalogUrl,
        TimezoneInterface $localeDate,
        Session $customerSession,
        DateTime $dateTime,
        GroupManagementInterface $groupManagement,
        $connection = null
    ) {

        $this->registryHelper           = $registryHelper;
        $this->registry                 = $registry;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->typeConfigurable         = $typeConfigurable;
        $this->cron                     = $cron;
        parent::__construct(
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $eavConfig,
            $resource,
            $eavEntityFactory,
            $resourceHelper,
            $universalFactory,
            $storeManager,
            $moduleManager,
            $catalogProductFlatState,
            $scopeConfig,
            $productOptionFactory,
            $catalogUrl,
            $localeDate,
            $customerSession,
            $dateTime,
            $groupManagement,
            $connection
        );
    }

    /**
     * @return $this
     */
    public function buildFilterStatusCondition()
    {
        $childString     = 'IFNULL(%1$s.value, %3$s.value)';
        $parentString    = 'IFNULL(%2$s.value, %4$s.value)';
        if ($this->registryHelper->isStatusAttributeInheritable()) {
            $inheritString = "IFNULL({$childString}, {$parentString})";
                $string = 'IF(IFNULL(%5$s.value, %6$s.value) = ' . Visibility::VISIBILITY_NOT_VISIBLE
                                          . ', ' . $parentString . ', ' . $inheritString . ')';
        } else {
            $string = $childString;
        }
        $this->filterStatusCondition = sprintf(
            $string,
            self::ORIGINAL_STATUS_TABLE_ALIAS,
            self::INHERITED_STATUS_TABLE_ALIAS,
            self::ORIGINAL_STATUS_TABLE_ALIAS_DEFAULT_STORE,
            self::INHERITED_STATUS_TABLE_ALIAS_DEFAULT_STORE,
            self::ORIGINAL_VISIBILITY_TABLE_ALIAS,
            self::VISIBILITY_TABLE_ALIAS_DEFAULT_STORE
        );
        return $this;
    }

    /**
     * @param string $tableAlias
     * @param string $storeId
     * @return $this
     * @throws Zend_Db_Select_Exception
     */
    public function joinVisibilityTable($tableAlias = self::VISIBILITY_TABLE_ALIAS_DEFAULT_STORE, $storeId = '0')
    {
        if ($this->isTableAliasAdded($tableAlias)) {
            return $this;
        }

        $this->getSelect()->joinLeft(
            [$tableAlias => $this->getVisibilityTable()],
            $this->getJoinVisibilityTableStatement($tableAlias, $storeId),
            ['value']
        );

        return $this;
    }

    /**
     * @return $this
     */
    public function joinRelationTable()
    {
        $this->getSelect()->columns(['parent_id' =>  $this->getParentIdSubselect()]);

        return $this;
    }

    /**
     * @param $tableAlias
     * @return bool
     * @throws Zend_Db_Select_Exception
     */
    public function isTableAliasAdded($tableAlias)
    {
        $tables         = $this->getSelect()->getPart(Zend_Db_Select::FROM);
        $currentAliases = array_keys($tables);

        return in_array($tableAlias, $currentAliases);
    }

    /**
     * @return mixed
     */
    public function getVisibilityTable()
    {
        return $this->registry->registry(Registry::DFW_STATUS_ATTRIBUTE_KEY)
                              ->getBackend()->getTable();
    }

    /**
     * @param string $tableAlias
     * @param string $storeId
     * @return string
     */
    public function getJoinVisibilityTableStatement($tableAlias, $storeId)
    {
        return sprintf(
            '%1$s.entity_id = e.entity_id and %2$s',
            $tableAlias,
            $this->getJoinVisibilityAttributeStatement($tableAlias, $storeId)
        );
    }

    /**
     * @param $tableAlias
     * @param string $storeId
     * @return string
     */
    public function getJoinVisibilityAttributeStatement($tableAlias, $storeId = '0')
    {
        $visibilityAttribute = $this->registry->registry(Registry::DFW_VISIBILITY_ATTRIBUTE_KEY);
        return sprintf(
            '%1$s.attribute_id = %2$s and %1$s.store_id = %3$s',
            $tableAlias,
            $visibilityAttribute->getId(),
            $storeId
        );
    }

    /**
     * @return $this
     */
    public function addRuleDate()
    {
        /** @var FillUpdatedAtTable $cron */
        $cron = $this->cron;
        $cron->execute();

        $condition = $this->getUpdatedAtCondition();
        $select    = $this->_resource->getConnection()->select();
        $select->from(
            [self::UPDATED_AT_TABLE_ALIAS => $this->_resource->getTableName('datafeedwatch_updated_products')],
            [sprintf('COALESCE(%1$s.updated_at, 0)', self::UPDATED_AT_TABLE_ALIAS)]
        );
        $select->where($condition);
        $select->limit(1);

        $this->ruleDateSelect = sprintf(
            'GREATEST(IFNULL((%s), 0), COALESCE(%2$s.updated_at, 0))',
            $select->__toString(),
            self::MAIN_TABLE_ALIAS
        );
        $this->getSelect()->columns([self::CATALOGRULE_DATE_COLUMN_ALIAS => new Zend_Db_Expr($this->ruleDateSelect)]);

        return $this;
    }

    /**
     * @return string
     */
    public function getUpdatedAtCondition()
    {
        $condition = '(parent_id IS NOT NULL
        AND %1$s.dfw_prod_id IN (parent_id)
        OR %1$s.dfw_prod_id = %2$s.entity_id)';
        $condition = sprintf(
            $condition,
            self::UPDATED_AT_TABLE_ALIAS,
            self::MAIN_TABLE_ALIAS
        );

        return $condition;
    }

    /**
     * @return string
     */
    public function getParentIdSubselect()
    {
        return '(select GROUP_CONCAT(DISTINCT parent_id) from ' . $this->getTable('catalog_product_relation')
               . ' where child_id = e.entity_id group by child_id)';
    }

    /**
     * @return $this
     * @throws LocalizedException
     */
    public function joinQty()
    {
        $this->joinTable(
            $this->_resource->getTableName('cataloginventory_stock_status'),
            'product_id=entity_id',
            [
                'qty' => 'qty',
                'stock_status' => 'stock_status',
            ],
            '{{table}}.stock_id=1 and {{table}}.website_id = 0',
            'left'
        );

        return $this;
    }

    /**
     * @param string $tableAlias
     * @param string $storeId
     * @return $this
     * @throws Zend_Db_Select_Exception
     */
    public function joinInheritedStatusTable($tableAlias = self::INHERITED_STATUS_TABLE_ALIAS, $storeId = '0')
    {
        if ($this->isTableAliasAdded($tableAlias)) {
            return $this;
        }

        $this->getSelect()->joinLeft(
            [$tableAlias => $this->getStatusTable()],
            $this->getJoinInheritedStatusTableStatement($tableAlias, $storeId),
            [self::MIXED_STATUS_COLUMN_ALIAS => $this->filterStatusCondition]
        );

        return $this;
    }

    /**
     * @return mixed
     */
    public function getStatusTable()
    {
        return $this->registry->registry(Registry::DFW_STATUS_ATTRIBUTE_KEY)->getBackend()->getTable();
    }

    /**
     * @param string $tableAlias
     * @param string $storeId
     * @return string
     */
    public function getJoinInheritedStatusTableStatement($tableAlias, $storeId)
    {
        return sprintf(
            '%1$s.entity_id IN (' . $this->getParentIdSubselect() . ') and %2$s',
            $tableAlias,
            $this->getJoinStatusAttributeStatement($tableAlias, $storeId)
        );
    }

    /**
     * @param $tableAlias
     * @param string $storeId
     * @return string
     */
    public function getJoinStatusAttributeStatement($tableAlias, $storeId = '0')
    {
        $statusAttribute = $this->registry->registry(Registry::DFW_STATUS_ATTRIBUTE_KEY);
        return sprintf(
            '%1$s.attribute_id = %2$s and %1$s.store_id = %3$s',
            $tableAlias,
            $statusAttribute->getId(),
            $storeId
        );
    }

    /**
     * @param string $tableAlias
     * @param string $storeId
     * @return $this
     * @throws Zend_Db_Select_Exception
     */
    public function joinOriginalStatusTable($tableAlias = self::ORIGINAL_STATUS_TABLE_ALIAS, $storeId = '0')
    {
        if ($this->isTableAliasAdded($tableAlias)) {
            return $this;
        }

        $this->getSelect()->joinLeft(
            [$tableAlias => $this->getStatusTable()],
            $this->getJoinOriginalStatusTableStatement($tableAlias, $storeId),
            [self::MIXED_STATUS_COLUMN_ALIAS => $this->filterStatusCondition]
        );

        return $this;
    }

    /**
     * @param string $tableAlias
     * @param string $storeId
     * @return string
     */
    public function getJoinOriginalStatusTableStatement($tableAlias, $storeId)
    {
        return sprintf(
            '%1$s.entity_id = e.entity_id and %2$s',
            $tableAlias,
            $this->getJoinStatusAttributeStatement($tableAlias, $storeId)
        );
    }
}
