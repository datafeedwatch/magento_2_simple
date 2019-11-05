<?php
/**
 * Created by Q-Solutions Studio
 * Date: 01.07.19
 *
 * @category    Dfw
 * @package     DataFeedWatch_Connector
 * @author      Maciej Buchert <maciej@qsolutionsstudio.com>
 */

namespace DataFeedWatch\Connector\Controller\Adminhtml\System;

use DataFeedWatch\Connector\Helper\Data;
use Exception;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;

/**
 * Class Open
 * @package DataFeedWatch\Connector\Controller\Adminhtml\System
 */
class Open extends Button
{
    /**
     * @return ResponseInterface|ResultInterface
     */
    public function execute()
    {
        try {
            $apiUser = $this->apiUser;

            if (!$apiUser->loadDfwUser()->isEmpty()) {
                $apiUrl = ($this->dataHelper->getConfig(DATA::TEST_API_STATUS_XML_PATH)) ?
                    $this->dataHelper->getConfig(DATA::TEST_API_URL_XML_PATH) : Data::MY_DATA_FEED_WATCH_URL;

                return $this->getResponse()->setRedirect($apiUrl);
            }

            $apiUser->createDfwUser();
            return $this->getResponse()->setRedirect($apiUser->getRegisterUrl());
        } catch (Exception $e) {
            $this->getMessageManager()->addErrorMessage($e->getMessage());

            return $this->getResponse()->setRedirect($this->_redirect->getRefererUrl());
        }
    }
}
