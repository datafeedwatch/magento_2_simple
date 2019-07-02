<?php
/**
 * Created by Q-Solutions Studio
 * Date: 01.07.19
 *
 * @category    Dfw
 * @package     Dfw_Connector
 * @author      Maciej Buchert <maciej@qsolutionsstudio.com>
 */

namespace Dfw\Connector\Controller\Adminhtml\System;

use Exception;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;

/**
 * Class Open
 * @package Dfw\Connector\Controller\Adminhtml\System
 */
class Open extends Button implements CsrfAwareActionInterface
{
    /**
     * @return ResponseInterface|ResultInterface
     */
    public function execute()
    {
        try {
            $apiUser = $this->apiUser;

            if (!$apiUser->isObjectNew()) {
                return $this->getResponse()->setRedirect($this->dataHelper->getDataFeedWatchUrl());
            }

            $apiUser->createDfwUser();

            return $this->getResponse()->setRedirect($apiUser->getRegisterUrl());
        } catch (Exception $e) {
            $this->getMessageManager()->addErrorMessage($e->getMessage());

            return $this->getResponse()->setRedirect($this->_redirect->getRefererUrl());
        }
    }

    /**
     * @param RequestInterface $request
     * @return InvalidRequestException|null
     */
    public function createCsrfValidationException(RequestInterface $request): ? InvalidRequestException
    {
        return null;
    }

    /**
     * @param RequestInterface $request
     * @return bool|null
     */
    public function validateForCsrf(RequestInterface $request): ? bool
    {
        return true;
    }
}
