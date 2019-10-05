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
use DataFeedWatch\Connector\Model\Api\User;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;

/**
 * Class Button
 * @package DataFeedWatch\Connector\Controller\Adminhtml\System
 */
abstract class Button extends Action
{
    const ADMIN_RESOURCE = 'DataFeedWatch_Connector::config';

    /**
     * @var Data
     */
    public $dataHelper;

    /**
     * @var User
     */
    public $apiUser;

    /**
     * Button constructor.
     * @param Context $context
     * @param Data $dataHelper
     * @param User $apiUser
     */
    public function __construct(
        Context $context,
        Data $dataHelper,
        User $apiUser
    ) {
        $this->dataHelper     = $dataHelper;
        $this->apiUser        = $apiUser;
        parent::__construct($context);
    }
}
