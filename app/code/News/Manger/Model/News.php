<?php

namespace News\Manger\Model;

use News\Manger\Api\Data\NewsInterface;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

class News extends AbstractModel implements NewsInterface, IdentityInterface
{
  const CACHE_TAG = 'news_manger_news';

  protected $_eventPrefix = 'news';
  protected $_eventObject = 'news';
  protected $_idFieldName = self::NEWS_ID;

  /**
   * @var ScopeConfigInterface
   */
  protected $_scopeConfig;

  /**
   * Constructor
   *
   * @param \Magento\Framework\Model\Context $context
   * @param \Magento\Framework\Registry $registry
   * @param ScopeConfigInterface $scopeConfig
   * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
   * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
   * @param array $data
   */
  public function __construct(
    \Magento\Framework\Model\Context $context,
    \Magento\Framework\Registry $registry,
    ScopeConfigInterface $scopeConfig,
    \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
    \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
    array $data = []
  ) {
    $this->_scopeConfig = $scopeConfig;
    parent::__construct($context, $registry, $resource, $resourceCollection, $data);
  }

  /**
   * Initialize resource model
   *
   * @return void
   */
  protected function _construct()
  {
    $this->_init('News\Manger\Model\ResourceModel\News');
  }

  /**
   * Get identities
   *
   * @return array
   */
  public function getIdentities()
  {
    return [self::CACHE_TAG . '_' . $this->getId()];
  }

  /**
   * Get default values
   *
   * @return array
   */
  public function getDefaultValues()
  {
    $values = [];
    $values['created_at'] = date('Y-m-d H:i:s');
    $values['news_status'] = 1;
    return $values;
  }

  // NewsInterface implementation
  public function getNewsId()
  {
    return $this->getData(self::NEWS_ID);
  }

  public function setNewsId($id)
  {
    return $this->setData(self::NEWS_ID, $id);
  }

  public function getNewsTitle()
  {
    return $this->getData(self::TITLE);
  }

  public function setNewsTitle($title)
  {
    return $this->setData(self::TITLE, $title);
  }

  public function getNewsContent()
  {
    return $this->getData(self::CONTENT);
  }

  public function setNewsContent($content)
  {
    return $this->setData(self::CONTENT, $content);
  }

  public function getNewsStatus()
  {
    return $this->getData(self::STATUS);
  }

  public function setNewsStatus($status)
  {
    return $this->setData(self::STATUS, $status);
  }

  public function getCreatedAt()
  {
    return $this->getData(self::CREATED_AT);
  }

  public function setCreatedAt($createdAt)
  {
    return $this->setData(self::CREATED_AT, $createdAt);
  }

  public function getUpdatedAt()
  {
    return $this->getData(self::UPDATED_AT);
  }

  public function setUpdatedAt($updatedAt)
  {
    return $this->setData(self::UPDATED_AT, $updatedAt);
  }

  public function getCategoryIds()
  {
    // Get from resource model if not loaded
    if (!$this->hasData(self::CATEGORY_IDS) && $this->getId()) {
      $categoryIds = $this->getResource()->getCategoryIds($this->getId());
      $this->setData(self::CATEGORY_IDS, $categoryIds);
    }

    $categoryIds = $this->getData(self::CATEGORY_IDS);
    return is_array($categoryIds) ? $categoryIds : [];
  }

  public function setCategoryIds($categoryIds)
  {
    if (is_array($categoryIds)) {
      $this->setData(self::CATEGORY_IDS, $categoryIds);
    } else {
      $this->setData(self::CATEGORY_IDS, []);
    }
    return $this;
  }

  /**
   * Validate the news data before save
   *
   * @return bool
   * @throws \Magento\Framework\Exception\LocalizedException
   */
  public function validate()
  {
    if (empty($this->getNewsTitle())) {
      throw new \Magento\Framework\Exception\LocalizedException(
        __('News title is required.')
      );
    }

    if (empty($this->getNewsContent())) {
      throw new \Magento\Framework\Exception\LocalizedException(
        __('News content is required.')
      );
    }

    if (!is_numeric($this->getNewsStatus())) {
      throw new \Magento\Framework\Exception\LocalizedException(
        __('Invalid news status.')
      );
    }

    return true;
  }

  /**
   * Processing object before save data
   *
   * @return $this
   */
  public function beforeSave()
  {
    // Set default status if not provided
    if ($this->getNewsStatus() === null) {
      $this->setNewsStatus(1);
    }

    return parent::beforeSave();
  }

  /**
   * Processing object after load data
   *
   * @return $this
   */
  protected function _afterLoad()
  {
    parent::_afterLoad();

    // Load category IDs
    if ($this->getId()) {
      $categoryIds = $this->getResource()->getCategoryIds($this->getId());
      $this->setData(self::CATEGORY_IDS, $categoryIds);
    }

    return $this;
  }
}
