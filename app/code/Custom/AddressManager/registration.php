<?php
/**
 * Custom Address Manager Module
 *
 * This module prevents duplicate customer addresses and ensures addresses
 * appear in the customer dashboard by setting proper default flags.
 */

use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(
    ComponentRegistrar::MODULE,
    'Custom_AddressManager',
    __DIR__
);
