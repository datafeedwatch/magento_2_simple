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

use Magento\Backend\App\Action;

abstract class Button extends Action
{
    const ADMIN_RESOURCE = 'Dfw_Connector::config';

    /**
     * @var \Dfw\Connector\Helper\Data
     */
    public $dataHelper;

    /**
     * @var \Dfw\Connector\Model\Api\User
     */
    public $apiUser;

    /**
     * Button constructor.
     * @param Action\Context $context
     * @param \Dfw\Connector\Helper\Data $dataHelper
     * @param \Dfw\Connector\Model\Api\User $apiUser
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Dfw\Connector\Helper\Data $dataHelper,
        \Dfw\Connector\Model\Api\User $apiUser
    ) {
        $this->dataHelper     = $dataHelper;
        $this->apiUser        = $apiUser;
        parent::__construct($context);
    }
}
