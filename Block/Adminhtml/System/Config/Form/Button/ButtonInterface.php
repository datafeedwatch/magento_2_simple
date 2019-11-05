<?php
/**
 * Created by Q-Solutions Studio
 * Date: 01.07.19
 *
 * @category    DataFeedWatch
 * @package     DataFeedWatch_Connector
 * @author      Maciej Buchert <maciej@qsolutionsstudio.com>
 */

namespace DataFeedWatch\Connector\Block\Adminhtml\System\Config\Form\Button;

/**
 * Interface ButtonInterface
 * @package DataFeedWatch\Connector\Block\Adminhtml\System\Config\Form\Button
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
