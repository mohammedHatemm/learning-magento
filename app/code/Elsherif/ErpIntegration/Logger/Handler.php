<?php
/**
 * Copyright © Elsherif. All rights reserved.
 * Custom Log Handler for ERP Integration
 */

namespace Elsherif\ErpIntegration\Logger;

use Magento\Framework\Logger\Handler\Base;
use Monolog\Logger;

class Handler extends Base
{
    /**
     * Logging level
     * @var int
     */
    protected $loggerType = Logger::INFO;

    /**
     * File name
     * @var string
     */
    protected $fileName = '/var/log/erp_integration.log';
}
