<?php
/**
 * Created by Q-Solutions Studio
 * Date: 01.07.19
 *
 * @category    Dfw
 * @package     Dfw_Connector
 * @author      Maciej Buchert <maciej@qsolutionsstudio.com>
 */

namespace Dfw\Connector\Observer;

use Dfw\Connector\Helper\Data as DataHelper;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

/**
 * Class ChangeProductUpdatedAtPlugin
 * @package Dfw\Connector\Observer
 */
class ChangeProductUpdatedAtPlugin implements ObserverInterface
{
    /**
     * @var DataHelper
     */
    public $dataHelper;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    public $resource;

    /**
     * ChangeProductUpdatedAtPlugin constructor.
     * @param DataHelper $dataHelper
     * @param \Magento\Framework\App\ResourceConnection $resource
     */
    public function __construct(
        DataHelper $dataHelper,
        \Magento\Framework\App\ResourceConnection $resource
    ) {

        $this->dataHelper = $dataHelper;
        $this->resource   = $resource;
    }

    /**
     * @param EventObserver $observer
     */
    public function execute(EventObserver $observer)
    {
        $date = gmdate('Y-m-d H:i:s');
        /** @var \Magento\Catalog\Model\Product $product */
        $product = $observer->getProduct();
        if ('configurable' === $product->getTypeId()) {
            /** @var \Magento\ConfigurableProduct\Model\Product\Type\Configurable $typeInstance */
            $typeInstance = $product->getTypeInstance();
            $childIds     = $typeInstance->getChildrenIds($product->getId());
            $childIds     = array_key_exists(0, $childIds) ? $childIds[0] : $childIds;
            $childIds     = !empty($childIds) ? array_values($childIds) : [];
            if (!empty($childIds)) {
                $childIds   = implode(',', $childIds);
                $connection = $this->resource->getConnection();
                $table      = $this->resource->getTableName('catalog_product_entity');
                $query      = "update {$table} set updated_at = '{$date}' where entity_id in ($childIds)";
                $connection->query($query);
            }
        }
        $product->setData('updated_at', $date);
    }
}
