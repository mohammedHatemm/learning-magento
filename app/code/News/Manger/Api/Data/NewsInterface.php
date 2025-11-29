<?php

namespace News\Manger\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

interface NewsInterface extends ExtensibleDataInterface
{
  const NEWS_ID = 'news_id';
  const TITLE = 'news_title';
  const CONTENT = 'news_content';
  const STATUS = 'news_status';
  const CREATED_AT = 'created_at';
  const UPDATED_AT = 'updated_at';
  const CATEGORY_IDS = 'category_ids';

  /**
   * Get news ID
   * @return int|null
   */
  public function getNewsId();

  /**
   * Set news ID
   * @param int $newsId
   * @return $this
   */
  public function setNewsId($id);

  /**
   * Get news title
   * @return string|null
   */
  public function getNewsTitle();

  /**
   * Set news title
   * @param string $title
   * @return $this
   */
  public function setNewsTitle($title);

  /**
   * Get news content
   * @return string|null
   */
  public function getNewsContent();

  /**
   * Set news content
   * @param string $content
   * @return $this
   */
  public function setNewsContent($content);

  /**
   * Get news status
   * @return int|null
   */
  public function getNewsStatus();

  /**
   * Set news status
   * @param int $status
   * @return $this
   */
  public function setNewsStatus($status);

  /**
   * Get created at
   * @return string|null
   */
  public function getCreatedAt();

  /**
   * Set created at
   * @param string $createdAt
   * @return $this
   */
  public function setCreatedAt($createdAt);

  /**
   * Get updated at
   * @return string|null
   */
  public function getUpdatedAt();

  /**
   * Set updated at
   * @param string $updatedAt
   * @return $this
   */
  public function setUpdatedAt($updatedAt);

  /**
   * Get category IDs
   * @return int[]|null
   */
  public function getCategoryIds();

  /**
   * Set category IDs
   * @param int[] $categoryIds
   * @return $this
   */
  public function setCategoryIds(array $categoryIds);
}
