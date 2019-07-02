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
 * Interface ButtonInterface
 * @package Dfw\Connector\Block\Adminhtml\System\Config\Form\Button
 */
interface ButtonInterface
{
    /**
     * @return string
     */
    public function getButtonLabel();

    /**
     * @return string
     */
    public function getButtonOnClick();
}
