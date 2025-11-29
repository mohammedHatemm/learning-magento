<?php

namespace News\Manger\Ui\Component\Options;

use Magento\Framework\Data\OptionSourceInterface;

class CategoryStatus implements OptionSourceInterface
{
  /**
   * Get options for select field
   *
   * @return array
   */
  public function toOptionArray()
  {
    return [
      ['value' => 1, 'label' => __('Enabled')],
      ['value' => 0, 'label' => __('Disabled')],
    ];
  }
}
