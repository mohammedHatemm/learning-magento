<?php

declare(strict_types=1);

use Magento\Framework\App\Bootstrap;
use Magento\Framework\App\Filesystem\DirectoryList;

try {
  // Bootstrap the Magento application
  require __DIR__ . '/../../../../../app/bootstrap.php';

  $params = $_SERVER;
  $bootstrap = Bootstrap::create(BP, $params);
  $objectManager = $bootstrap->getObjectManager();

  // Set area code
  $appState = $objectManager->get('Magento\Framework\App\State');
  $appState->setAreaCode('adminhtml');

  // Get logger
  $logger = $objectManager->get('Psr\Log\LoggerInterface');

  // Test category data
  $categoryData = [
    'category_name' => 'Test Category',
    'category_description' => 'Test Description',
    'category_status' => 1,
    'parent_ids' => []
  ];

  $logger->info('Starting category creation test', ['data' => $categoryData]);

  try {
    // Create category
    $category = $objectManager->create('News\\Manger\\Api\\Data\\CategoryInterfaceFactory')->create();
    $category->setData($categoryData);

    $logger->info('Category object created', ['category' => $category->getData()]);

    // Save category
    $repository = $objectManager->get('News\\Manger\\Api\\CategoryRepositoryInterface');
    $result = $repository->save($category);

    $logger->info('Category saved successfully', ['id' => $result->getCategoryId()]);
    echo "Category created successfully! ID: " . $result->getCategoryId() . "\n";
  } catch (\Exception $e) {
    $logger->error('Error saving category', [
      'message' => $e->getMessage(),
      'trace' => $e->getTraceAsString()
    ]);

    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " (Line: " . $e->getLine() . ")\n";
    echo "Stack Trace:\n" . $e->getTraceAsString() . "\n";
  }
} catch (\Exception $e) {
  echo "Bootstrap Error: " . $e->getMessage() . "\n";
  echo "File: " . $e->getFile() . " (Line: " . $e->getLine() . ")\n";
  echo "Stack Trace:\n" . $e->getTraceAsString() . "\n";
}
