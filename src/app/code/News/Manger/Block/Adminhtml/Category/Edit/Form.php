<?php

namespace News\Manger\Block\Adminhtml\Category\Edit;

use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Framework\Data\Form as DataForm;

class Form extends Generic
{
  /**
   * @var \News\Manger\Model\ResourceModel\Category\CollectionFactory
   */
  protected $_categoryCollectionFactory;

  /**
   * @param \Magento\Backend\Block\Template\Context $context
   * @param \Magento\Framework\Registry $registry
   * @param \Magento\Framework\Data\FormFactory $formFactory
   * @param \News\Manger\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory
   * @param array $data
   */
  public function __construct(
    \Magento\Backend\Block\Template\Context $context,
    \Magento\Framework\Registry $registry,
    \Magento\Framework\Data\FormFactory $formFactory,
    \News\Manger\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory,
    array $data = []
  ) {
    $this->_categoryCollectionFactory = $categoryCollectionFactory;
    parent::__construct($context, $registry, $formFactory, $data);
  }

  /**
   * Prepare form
   *
   * @return $this
   */
  protected function _prepareForm()
  {
    $model = $this->_coreRegistry->registry('news_category');

    $form = $this->_formFactory->create(
      ['data' => [
        'id' => 'edit_form',
        'action' => $this->getData('action'),
        'method' => 'post',
        'enctype' => 'multipart/form-data'
      ]]
    );

    $fieldset = $form->addFieldset(
      'base_fieldset',
      ['legend' => __('Category Information'), 'class' => 'fieldset-wide']
    );

    if ($model && $model->getId()) {
      $fieldset->addField('category_id', 'hidden', ['name' => 'category_id']);
    }

    $fieldset->addField(
      'category_name',
      'text',
      [
        'name' => 'category_name',
        'label' => __('Category Name'),
        'title' => __('Category Name'),
        'required' => true,
        'class' => 'required-entry',
      ]
    );

    $fieldset->addField(
      'category_description',
      'textarea',
      [
        'name' => 'category_description',
        'label' => __('Description'),
        'title' => __('Description'),
        'required' => false,
      ]
    );

    $fieldset->addField(
      'category_status',
      'select',
      [
        'name' => 'category_status',
        'label' => __('Status'),
        'title' => __('Status'),
        'required' => true,
        'options' => ['1' => __('Enabled'), '0' => __('Disabled')],
      ]
    );

    $fieldset->addField(
      'sort_order',
      'text',
      [
        'name' => 'sort_order',
        'label' => __('Sort Order'),
        'title' => __('Sort Order'),
        'required' => false,
        'class' => 'validate-number',
        'note' => __('Lower numbers will appear first'),
      ]
    );

    // Get parent categories options
    $parentOptions = $this->getParentCategoriesOptions($model);

    $fieldset->addField(
      'parent_category_id',
      'select',
      [
        'name' => 'parent_category_id',
        'label' => __('Parent Category'),
        'title' => __('Parent Category'),
        'required' => false,
        'options' => $parentOptions,
        'note' => __('Select parent category to create hierarchy'),
      ]
    );

    // Set form values
    if ($model) {
      $data = $model->getData();

      // Set default sort order if not set
      if (!isset($data['sort_order']) || $data['sort_order'] === null) {
        $data['sort_order'] = 0;
      }

      $form->setValues($data);
    }

    $form->setUseContainer(true);
    $this->setForm($form);

    return parent::_prepareForm();
  }

  /**
   * Get parent categories options for dropdown
   *
   * @param \News\Manger\Model\Category|null $currentModel
   * @return array
   */
  protected function getParentCategoriesOptions($currentModel = null)
  {
    $collection = $this->_categoryCollectionFactory->create();

    $excludeId = null;
    if ($currentModel && $currentModel->getId()) {
      $excludeId = $currentModel->getId();
    }

    return $collection->getHierarchicalOptionsArray(true, $excludeId);
  }
}
