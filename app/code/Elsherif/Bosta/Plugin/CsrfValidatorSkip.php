<?php

declare(strict_types=1);

namespace Elsherif\Bosta\Plugin;

use Magento\Framework\App\Request\CsrfValidator;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ActionInterface;

class CsrfValidatorSkip
{
    /**
     * Skip CSRF validation for Bosta webhook endpoint
     *
     * @param CsrfValidator $subject
     * @param \Closure $proceed
     * @param RequestInterface $request
     * @param ActionInterface $action
     * @return void
     */
    public function aroundValidate(
        CsrfValidator $subject,
        \Closure $proceed,
        RequestInterface $request,
        ActionInterface $action
    ): void {
        // Skip CSRF validation for Bosta webhook endpoint
        if ($request->getModuleName() === 'bosta' &&
            $request->getControllerName() === 'webhook' &&
            $request->getActionName() === 'update') {
            return;
        }

        $proceed($request, $action);
    }
}
