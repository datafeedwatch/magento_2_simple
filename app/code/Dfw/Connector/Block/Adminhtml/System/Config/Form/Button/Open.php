<?php
/**
 * Created by Q-Solutions Studio
 * Date: 01.07.19
 *
 * @category    Dfw
 * @package     Dfw_Connector
 * @author      Maciej Buchert <maciej@qsolutionsstudio.com>
 */

namespace Dfw\Connector\Block\Adminhtml\System\Config\Form\Button;

/**
 * Class Open
 * @package Dfw\Connector\Block\Adminhtml\System\Config\Form\Button
 */
class Open extends BaseButton
{
    /**
     * @return string
     */
    public function getButtonLabel()
    {
        return __('Open');
    }

    /**
     * @return string
     */
    public function getButtonOnClick()
    {
        return sprintf("window.open('%s')", $this->getUrl('dfw/system/open'));
    }
}