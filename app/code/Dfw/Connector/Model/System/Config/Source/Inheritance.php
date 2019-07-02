<?php
/**
 * Created by Q-Solutions Studio
 * Date: 01.07.19
 *
 * @category    Dfw
 * @package     Dfw_Connector
 * @author      Maciej Buchert <maciej@qsolutionsstudio.com>
 */

namespace Dfw\Connector\Model\System\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class Inheritance
 * @package Dfw\Connector\Model\System\Config\Source
 */
class Inheritance implements OptionSourceInterface
{
    const CHILD_OPTION_ID                = 1;
    const CHILD_OPTION_LABEL             = 'Child';
    const PARENT_OPTION_ID               = 2;
    const PARENT_OPTION_LABEL            = 'Parent';
    const CHILD_THEN_PARENT_OPTION_ID    = 3;
    const CHILD_THEN_PARENT_OPTION_LABEL = 'Child Then Parent';

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::CHILD_OPTION_ID,
                'label' => __('Child'),
            ],
            [
                'value' => self::PARENT_OPTION_ID,
                'label' => __('Parent'),
            ],
            [
                'value' => self::CHILD_THEN_PARENT_OPTION_ID,
                'label' => __('Child Then Parent'),
            ],
        ];
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            self::CHILD_OPTION_ID             => __('Child'),
            self::PARENT_OPTION_ID            => __('Parent'),
            self::CHILD_THEN_PARENT_OPTION_ID => __('Child Then Parent'),
        ];
    }
}
