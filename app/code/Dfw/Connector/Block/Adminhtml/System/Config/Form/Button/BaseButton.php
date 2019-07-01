<?php
/**
 * Created by Q-Solutions Studio
 * Date: 27.02.18
 *
 * @category    DataFeedWatch
 * @package     DataFeedWatch_Connector
 * @author      Lukasz Owczarczuk <lukasz@qsolutionsstudio.com>
 */

namespace Dfw\Connector\Block\Adminhtml\System\Config\Form\Button;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Backend\Block\Template\Context;
use Dfw\Connector\Helper\Data as DataHelper;

/**
 * Class BaseButton
 * @package Dfw\Connector\Block\Adminhtml\System\Config\Form\Button
 */
abstract class BaseButton extends Field implements ButtonInterface
{
    /**
     * @var DataHelper
     */
    private $dataHelper;

    /**
     * BaseButton constructor.
     * @param Context $context
     * @param DataHelper $dataHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        DataHelper $dataHelper,
        array $data = []
    ) {
        $this->dataHelper = $dataHelper;
        parent::__construct($context, $data);
    }

    /**
     * @param AbstractElement $element
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function _getElementHtml(AbstractElement $element)
    {
        return !$element instanceof AbstractElement ? parent::_getElementHtml($element): $this->getLayout()
                    ->createBlock(\Magento\Backend\Block\Widget\Button::class)
                    ->setType('button')
                    ->setClass('scalable')
                    ->setLabel($this->getButtonLabel())
                    ->setOnClick($this->getButtonOnClick())
                    ->toHtml();
    }
}
