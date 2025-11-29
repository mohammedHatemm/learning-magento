<?php

declare(strict_types=1);

namespace Elsherif\Bosta\Model;

use Magento\Framework\Model\AbstractModel;

class Pickup extends AbstractModel
{
    protected function _construct()
    {
        $this->_init(\Elsherif\Bosta\Model\ResourceModel\Pickup::class);
    }

    public function getBostaPickupId(): ?string
    {
        return $this->getData('bosta_pickup_id');
    }

    public function setBostaPickupId(string $pickupId): self
    {
        return $this->setData('bosta_pickup_id', $pickupId);
    }

    public function getBusinessLocationId(): ?string
    {
        return $this->getData('business_location_id');
    }

    public function setBusinessLocationId(string $locationId): self
    {
        return $this->setData('business_location_id', $locationId);
    }

    public function getScheduledDate(): ?string
    {
        return $this->getData('scheduled_date');
    }

    public function setScheduledDate(string $date): self
    {
        return $this->setData('scheduled_date', $date);
    }

    public function getScheduledTimeSlot(): ?string
    {
        return $this->getData('scheduled_time_slot');
    }

    public function setScheduledTimeSlot(string $timeSlot): self
    {
        return $this->setData('scheduled_time_slot', $timeSlot);
    }

    public function getStatus(): ?string
    {
        return $this->getData('status');
    }

    public function setStatus(string $status): self
    {
        return $this->setData('status', $status);
    }

    public function getContactPersonName(): ?string
    {
        return $this->getData('contact_person_name');
    }

    public function setContactPersonName(string $name): self
    {
        return $this->setData('contact_person_name', $name);
    }

    public function getContactPersonPhone(): ?string
    {
        return $this->getData('contact_person_phone');
    }

    public function setContactPersonPhone(string $phone): self
    {
        return $this->setData('contact_person_phone', $phone);
    }

    public function getNotes(): ?string
    {
        return $this->getData('notes');
    }

    public function setNotes(?string $notes): self
    {
        return $this->setData('notes', $notes);
    }
}
