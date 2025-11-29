<?php

namespace News\Manger\Block\Adminhtml\Category;

use Magento\Backend\Block\Widget\Form\Container;

class Edit extends Container
{
  protected function _construct()
  {
    $this->_objectId = 'category_id';
    $this->_blockGroup = 'News_Manger';
    $this->_controller = 'adminhtml_category';

    parent::_construct();

    $this->buttonList->update('save', 'label', __('Save Category'));
    $this->buttonList->add(
      'saveandcontinue',
      [
        'label' => __('Save and Continue Edit'),
        'class' => 'save',
        'data_attribute' => [
          'mage-init' => [
            'button' => ['event' => 'saveAndContinueEdit', 'target' => '#edit_form'],
          ],
        ]
      ],
      -100
    );
  }
}
