<?php

declare(strict_types=1);

namespace Elsherif\Bosta\Controller\Api;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Elsherif\Bosta\Helper\Data as BostaHelper;

class Zones extends Action
{
    private JsonFactory $jsonFactory;
    private BostaHelper $bostaHelper;

    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        BostaHelper $bostaHelper
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->bostaHelper = $bostaHelper;
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();

        try {
            $cityName = $this->getRequest()->getParam('city');

            if (!$cityName) {
                return $result->setData([
                    'success' => false,
                    'error' => 'City parameter is required'
                ]);
            }

            $zones = $this->bostaHelper->getZonesForDropdown($cityName);

            return $result->setData([
                'success' => true,
                'zones' => $zones
            ]);
        } catch (\Exception $e) {
            return $result->setData([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
}
