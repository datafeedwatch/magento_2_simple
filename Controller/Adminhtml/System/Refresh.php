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

use DataFeedWatch\Connector\Model\Api\User as ApiUser;
use Exception;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;

/**
 * Class Refresh
 * @package DataFeedWatch\Connector\Controller\Adminhtml\System
 */
class Refresh extends Button
{
    /**
     * @return ResponseInterface|ResultInterface
     */
    public function execute()
    {
        try {
            $apiUser = $this->apiUser;
            $apiUser->loadDfwUser();
            $apiUser->createDfwUser();

            $this->getMessageManager()->addSuccessMessage(__('%1 user has been refreshed', ApiUser::USER_NAME));

            return $this->getResponse()->setRedirect($this->_redirect->getRefererUrl());
        } catch (Exception $e) {
            $this->getMessageManager()->addErrorMessage($e->getMessage());

            return $this->getResponse()->setRedirect($this->_redirect->getRefererUrl());
        }
    }
}