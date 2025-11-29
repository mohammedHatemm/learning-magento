<?php

declare(strict_types=1);

namespace Elsherif\Bosta\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Pickup extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('bosta_pickup', 'entity_id');
    }
}
