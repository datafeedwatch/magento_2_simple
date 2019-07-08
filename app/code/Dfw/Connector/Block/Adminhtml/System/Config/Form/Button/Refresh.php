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
 * Class Refresh
 * @package Dfw\Connector\Block\Adminhtml\System\Config\Form\Button
 */
class Refresh extends BaseButton
{
    /**
     * @return string
     */
    public function getButtonLabel()
    {
        return __('Refresh');
    }

    /**
     * @return string
     */
    public function getButtonOnClick()
    {
        return sprintf("setLocation('%s')", $this->getUrl('dfw/system/refresh'));
    }
}
