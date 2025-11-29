<?php

namespace News\Manger\Block\Adminhtml\News;

use Magento\Backend\Block\Widget\Form\Container;

class Edit extends Container
{
  protected function _construct()
  {
    $this->_objectId = 'news_id';
    $this->_blockGroup = 'News_Manger';
    $this->_controller = 'adminhtml_news';

    parent::_construct();

    $this->buttonList->update('save', 'label', __('Save News'));
    $this->buttonList->update('delete', 'label', __('Delete News'));
  }
}
