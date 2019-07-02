<?php
/**
 * Created by Q-Solutions Studio
 * Date: 01.07.19
 *
 * @category    Dfw
 * @package     Dfw_Connector
 * @author      Maciej Buchert <maciej@qsolutionsstudio.com>
 */

namespace Dfw\Connector\Api;

use Magento\Framework\Exception\LocalizedException;

/**
 * Interface ConnectorInterface
 * @package Dfw\Connector\Api
 */
interface ConnectorInterface
{
    /**
     * Retrieve extension version
     *
     * @api
     * @return float
     */
    public function version();

    /**
     * Retrieve datetime in GMT
     *
     * @api
     * @return int
     */
    public function gmtOffset();

    /**
     * Retrieve stores
     *
     * @api
     * @return string[]
     */
    public function stores();

    /**
     * Retrieve products
     *
     * @api
     *
     * @param string $store = null
     * @param string[] $type = string[]
     * @param string $status = null
     * @param int $perPage = 100
     * @param int $page = 1
     *
     * @return string[]
     */
    public function products($store = null, $type = [], $status = null, $perPage = 100, $page = 1);

    /**
     * Retrieve product count
     *
     * @api
     *
     * @param string $store = null
     * @param string[] $type = string[]
     * @param string $status = null
     * @param int $perPage = 100
     * @param int $page = 1
     *
     * @return int
     */
    public function productCount($store = null, $type = [], $status = null, $perPage = 100, $page = 1);

    /**
     * Retrieve products based on last update
     *
     * @api
     *
     * @param string $store = null
     * @param string[] $type = string[]
     * @param string $status = null
     * @param string $timezone = null
     * @param string $fromDate = null
     * @param int $perPage = 100
     * @param int $page = 1
     *
     * @return string[]
     */
    public function updatedProducts(
        $store = null,
        $type = [],
        $status = null,
        $timezone = null,
        $fromDate = null,
        $perPage = 100,
        $page = 1
    );

    /**
     * Retrieve product count based on last update
     *
     * @api
     *
     * @param string $store = null
     * @param string[] $type = string[]
     * @param string $status = null
     * @param string $timezone = null
     * @param string $fromDate = null
     * @param int $perPage = 100
     * @param int $page = 1
     *
     * @return int
     */
    public function updatedProductCount(
        $store = null,
        $type = [],
        $status = null,
        $timezone = null,
        $fromDate = null,
        $perPage = 100,
        $page = 1
    );

    /**
     * Retrieve Product Ids
     *
     * @api
     *
     * @param string $store = null
     * @param string[] $type = string[]
     * @param string $status = null
     * @param string $timezone = null
     * @param string $fromDate = null
     * @param int $perPage = 100
     * @param int $page = 1
     *
     * @return string[]
     */
    public function productIds(
        $store = null,
        $type = [],
        $status = null,
        $timezone = null,
        $fromDate = null,
        $perPage = 100,
        $page = 1
    );

    /**
     * revoke DFW admin user
     *
     * @api
     *
     * @param string $token = null
     * @return string
     * @throws LocalizedException
     */
    public function revokeAccessToken($token = null);
}
