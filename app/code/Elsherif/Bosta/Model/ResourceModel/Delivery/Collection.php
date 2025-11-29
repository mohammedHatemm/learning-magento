<?php

declare(strict_types=1);

namespace Elsherif\Bosta\Model\ResourceModel\Delivery;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'entity_id';

    protected function _construct()
    {
        $this->_init(
            \Elsherif\Bosta\Model\Delivery::class,
            \Elsherif\Bosta\Model\ResourceModel\Delivery::class
        );
    }
}
