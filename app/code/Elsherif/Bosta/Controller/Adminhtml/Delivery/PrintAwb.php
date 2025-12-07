<?php

declare(strict_types=1);

namespace Elsherif\Bosta\Controller\Adminhtml\Delivery;

use Elsherif\Bosta\Helper\Data as BostaHelper;
use Elsherif\Bosta\Model\DeliveryFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\RawFactory;

class PrintAwb extends Action
{
    private DeliveryFactory $deliveryFactory;
    private BostaHelper $bostaHelper;
    private RawFactory $resultRawFactory;

    public function __construct(
        Context         $context,
        DeliveryFactory $deliveryFactory,
        BostaHelper     $bostaHelper,
        RawFactory      $resultRawFactory
    )
    {
        parent::__construct($context);
        $this->deliveryFactory = $deliveryFactory;
        $this->bostaHelper = $bostaHelper;
        $this->resultRawFactory = $resultRawFactory;
    }

    public function execute()
    {
        $deliveryId = (int)$this->getRequest()->getParam('delivery_id');

        if (!$deliveryId) {
            $this->messageManager->addErrorMessage(__('Delivery ID is required.'));
            return $this->_redirect('sales/order/index');
        }

        try {
            $delivery = $this->deliveryFactory->create()->load($deliveryId);

            if (!$delivery->getId()) {
                $this->messageManager->addErrorMessage(__('Delivery not found.'));
                return $this->_redirect('sales/order/index');
            }

            $trackingNumber = $delivery->getTrackingNumber();
            if (!$trackingNumber) {
                $this->messageManager->addErrorMessage(__('Tracking number not found.'));
                return $this->_redirect('sales/order/index');
            }

            // Generate AWB PDF from Bosta API
            $response = $this->bostaHelper->generateMassAwb(
                $trackingNumber,
                'A4',
                'ar'
            );

            if (!$response['success']) {
                $errorMessage = $response['message'] ?? __('Failed to generate AWB from Bosta API.');
                $this->messageManager->addErrorMessage($errorMessage);
                return $this->_redirect('sales/order/view', ['order_id' => $delivery->getOrderId()]);
            }

            // CRITICAL FIX: Decode Base64 PDF data
            $base64Data = $response['data'];
            $pdfData = base64_decode($base64Data);

            // Validate decoded PDF
            if (empty($pdfData) || substr($pdfData, 0, 4) !== '%PDF') {
                $this->messageManager->addErrorMessage(__('Invalid PDF data received from Bosta.'));
                return $this->_redirect('sales/order/view', ['order_id' => $delivery->getOrderId()]);
            }

            // Clear output buffer
            if (ob_get_level()) {
                ob_end_clean();
            }

            // Return PDF to browser
            $resultRaw = $this->resultRawFactory->create();
            $resultRaw->setHttpResponseCode(200);
            $resultRaw->setHeader('Content-Type', 'application/pdf', true);
            $resultRaw->setHeader('Content-Length', (string)strlen($pdfData), true);
            $resultRaw->setHeader('Content-Disposition', 'inline; filename="awb_' . $trackingNumber . '.pdf"', true);
            $resultRaw->setHeader('Cache-Control', 'private, max-age=0, must-revalidate', true);
            $resultRaw->setHeader('Pragma', 'public', true);

            $resultRaw->setContents($pdfData);

            return $resultRaw;

        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(
                __('Error generating AWB: %1', $e->getMessage())
            );
            return $this->_redirect('sales/order/index');
        }
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magento_Sales::actions_view');
    }
}
