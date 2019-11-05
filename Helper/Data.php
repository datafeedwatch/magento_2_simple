<?php
/**
 * Created by Q-Solutions Studio
 * Date: 01.07.19
 *
 * @category    DataFeedWatch
 * @package     DataFeedWatch_Connector
 * @author      Maciej Buchert <maciej@qsolutionsstudio.com>
 */

namespace DataFeedWatch\Connector\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

/**
 * Class Data
 * @package DataFeedWatch\Connector\Helper
 */
class Data extends AbstractHelper
{
    const MY_DATA_FEED_WATCH_URL = 'https://my.datafeedwatch.com/';
    const VERSION_PARAMETER_XML_PATH = "dfw_connector/general/version";
    const TEST_API_STATUS_XML_PATH = 'dfw_connector/general/test_mode';
    const TEST_API_URL_XML_PATH = 'dfw_connector/general/test_api_url';

    /**
     * @param string $path
     * @return mixed
     */
    public function getConfig($path)
    {
        return $this->scopeConfig->getValue($path);
    }
}
