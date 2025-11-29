<?php

declare(strict_types=1);

namespace Elsherif\Bosta\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class TrackingEvent extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('bosta_tracking_event', 'entity_id');
    }
}
