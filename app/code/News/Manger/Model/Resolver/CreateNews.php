<?php

namespace News\Manger\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\Exception\LocalizedException;
use News\Manger\Api\NewsRepositoryInterface;
use News\Manger\Api\Data\NewsInterfaceFactory;
use Psr\Log\LoggerInterface;

class CreateNews implements ResolverInterface
{
  /**
   * @var NewsRepositoryInterface
   */
  private $newsRepository;

  /**
   * @var NewsInterfaceFactory
   */
  private $newsFactory;

  /**
   * @var LoggerInterface
   */
  private $logger;

  public function __construct(
    NewsRepositoryInterface $newsRepository,
    NewsInterfaceFactory $newsFactory,
    LoggerInterface $logger
  ) {
    $this->newsRepository = $newsRepository;
    $this->newsFactory = $newsFactory;
    $this->logger = $logger;
  }

  /**
   * @inheritdoc
   */
  public function resolve(
    Field $field,
    $context,
    ResolveInfo $info,
    array $value = null,
    array $args = null
  ) {
    // Validate input data existence
    if (!isset($args['input']) || !is_array($args['input'])) {
      throw new GraphQlInputException(__('Input data is required and must be an object'));
    }

    $input = $args['input'];

    // Log input for debugging
    $this->logger->info('CreateNews GraphQL Input: ' . json_encode($input));

    // Validate required fields
    $this->validateInput($input);

    try {
      // Create news data object
      $news = $this->newsFactory->create();

      // Set basic news data
      $news->setNewsTitle(trim($input['news_title']));
      $news->setNewsContent(trim($input['news_content']));
      $news->setNewsStatus($this->getNewsStatus($input));

      // Handle category IDs
      $categoryIds = $this->processCategoryIds($input);
      if (!empty($categoryIds)) {
        $news->setCategoryIds($categoryIds);
        $this->logger->info('Setting category IDs: ' . json_encode($categoryIds));
      }

      // Save news
      $savedNews = $this->newsRepository->save($news);

      $this->logger->info('News created successfully with ID: ' . $savedNews->getNewsId());

      // Return formatted response
      return $this->formatResponse($savedNews);
    } catch (LocalizedException $e) {
      $this->logger->error('LocalizedException in CreateNews: ' . $e->getMessage());
      throw new GraphQlInputException(__($e->getMessage()));
    } catch (\Exception $e) {
      $this->logger->error('Exception in CreateNews: ' . $e->getMessage());
      throw new GraphQlInputException(__('Could not create news: %1', $e->getMessage()));
    }
  }

  /**
   * Validate input data
   *
   * @param array $input
   * @throws GraphQlInputException
   */
  private function validateInput(array $input): void
  {
    $errors = [];

    // Check required fields
    if (empty($input['news_title']) || !is_string($input['news_title'])) {
      $errors[] = 'News title is required and must be a string';
    } elseif (strlen(trim($input['news_title'])) === 0) {
      $errors[] = 'News title cannot be empty';
    }

    if (empty($input['news_content']) || !is_string($input['news_content'])) {
      $errors[] = 'News content is required and must be a string';
    } elseif (strlen(trim($input['news_content'])) === 0) {
      $errors[] = 'News content cannot be empty';
    }

    // Validate news status if provided
    if (isset($input['news_status']) && !in_array($input['news_status'], [0, 1, '0', '1'], true)) {
      $errors[] = 'News status must be 0 (disabled) or 1 (enabled)';
    }

    // Validate category IDs if provided
    if (isset($input['category_ids'])) {
      $categoryValidation = $this->validateCategoryIds($input['category_ids']);
      if (!empty($categoryValidation)) {
        $errors[] = $categoryValidation;
      }
    }

    if (!empty($errors)) {
      throw new GraphQlInputException(__(implode('; ', $errors)));
    }
  }

  /**
   * Validate category IDs format
   *
   * @param mixed $categoryIds
   * @return string|null Error message or null if valid
   */
  private function validateCategoryIds($categoryIds): ?string
  {
    if ($categoryIds === null || $categoryIds === '') {
      return null; // It's optional
    }

    if (!is_array($categoryIds)) {
      return 'Category IDs must be an array';
    }

    if (empty($categoryIds)) {
      return null; // Empty array is valid
    }

    foreach ($categoryIds as $index => $categoryId) {
      if (!is_numeric($categoryId)) {
        return "Category ID at index {$index} must be numeric";
      }

      $categoryId = (int)$categoryId;
      if ($categoryId <= 0) {
        return "Category ID at index {$index} must be a positive integer";
      }
    }

    return null;
  }

  /**
   * Process and clean category IDs
   *
   * @param array $input
   * @return array
   */
  private function processCategoryIds(array $input): array
  {
    if (!isset($input['category_ids']) || $input['category_ids'] === null) {
      return [];
    }

    if (!is_array($input['category_ids'])) {
      $this->logger->warning('Category IDs is not an array, converting: ' . json_encode($input['category_ids']));
      return [];
    }

    if (empty($input['category_ids'])) {
      return [];
    }

    // Convert to integers and filter out invalid values
    $categoryIds = [];
    foreach ($input['category_ids'] as $categoryId) {
      if (is_numeric($categoryId)) {
        $categoryId = (int)$categoryId;
        if ($categoryId > 0) {
          $categoryIds[] = $categoryId;
        }
      }
    }

    // Remove duplicates
    $categoryIds = array_unique($categoryIds);

    // Re-index array
    $categoryIds = array_values($categoryIds);

    $this->logger->info('Processed category IDs: ' . json_encode($categoryIds));

    return $categoryIds;
  }

  /**
   * Get news status from input
   *
   * @param array $input
   * @return int
   */
  private function getNewsStatus(array $input): int
  {
    if (!isset($input['news_status'])) {
      return 1; // Default to enabled
    }

    return (int)$input['news_status'];
  }

  /**
   * Format the response
   *
   * @param \News\Manger\Api\Data\NewsInterface $savedNews
   * @return array
   */
  private function formatResponse($savedNews): array
  {
    $response = [
      'news_id' => (int)$savedNews->getNewsId(),
      'news_title' => $savedNews->getNewsTitle(),
      'news_content' => $savedNews->getNewsContent(),
      'news_status' => (int)$savedNews->getNewsStatus(),
      'created_at' => $savedNews->getCreatedAt(),
      'updated_at' => $savedNews->getUpdatedAt(),
      'category_ids' => $savedNews->getCategoryIds() ?: [],
      'model' => $savedNews
    ];

    $this->logger->info('CreateNews Response: ' . json_encode($response));

    return $response;
  }

  /**
   * Check if user has permission to create news (if needed)
   *
   * @param $context
   * @throws GraphQlAuthorizationException
   */
  private function checkPermissions($context): void
  {
    // Add authorization logic here if needed
    // For example, check if user is admin or has specific permissions

    /*
        if (!$this->isUserAuthorized($context)) {
            throw new GraphQlAuthorizationException(__('You do not have permission to create news'));
        }
        */
  }
}
