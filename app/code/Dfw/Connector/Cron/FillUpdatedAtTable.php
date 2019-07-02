<?php
/**
 * Created by Q-Solutions Studio
 * Date: 01.07.19
 *
 * @category    Dfw
 * @package     Dfw_Connector
 * @author      Maciej Buchert <maciej@qsolutionsstudio.com>
 */

namespace Dfw\Connector\Cron;

use Dfw\Connector\Helper\Data as DataHelper;
use Magento\Customer\Model\GroupManagement;
use Magento\Framework\App\ResourceConnection;

/**
 * Class FillUpdatedAtTable
 * @package Dfw\Connector\Cron
 */
class FillUpdatedAtTable
{
    const CATALOGRULE_DATE_TABLE_ALIAS = 'catalogrule_product_price_date';

    /**
     * @var DataHelper
     */
    private $dataHelper;

    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * FillUpdatedAtTable constructor.
     * @param DataHelper $dataHelper
     * @param ResourceConnection $resource
     */
    public function __construct(
        DataHelper $dataHelper,
        ResourceConnection $resource
    ) {
        $this->dataHelper = $dataHelper;
        $this->resource   = $resource;
    }

    /**
     * @return $this
     */
    public function execute()
    {
        $date            = date('Y-m-d H:i:s');
        $lastPriceId     = $this->dataHelper->getLastCatalogRulePriceId();
        $writeConnection = $this->resource->getConnection(ResourceConnection::DEFAULT_CONNECTION);
        $select          = $this->resource->getConnection()->select();
        
        $select->from(
            [
                self::CATALOGRULE_DATE_TABLE_ALIAS => $this->resource->getTableName('catalogrule_product_price'),
            ]
        );
        
        if (!empty($lastPriceId)) {
            $select->where('rule_product_price_id > ?', $lastPriceId);
        }
        $select->where('customer_group_id = ?', GroupManagement::NOT_LOGGED_IN_ID);
        $select->where('rule_date <= ?', $date);
        
        $priceData = $select->query()->fetchAll();
        if (count($priceData) < 1) {
            return $this;
        }
        
        $updatedDataTable = $this->resource->getTableName('datafeedwatch_updated_products');
        foreach ($priceData as $data) {
            $insertedData = [
                'dfw_prod_id' => $data['product_id'],
                'updated_at'  => $date,
            ];
            $writeConnection->insertOnDuplicate($updatedDataTable, $insertedData, ['updated_at']);
        }
        
        if (!empty($priceData)) {
            $data = end($priceData);
            $this->dataHelper->setLastCatalogRulePriceId($data['rule_product_price_id']);
        }
        
        return $this;
    }
}
