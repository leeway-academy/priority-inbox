<?php

namespace PriorityInbox;

class EmailUpdate
{
    /**
     * @var array<string>
     */
    private array $addLabelIds = [];

    /**
     * @var array<string>
     */
    private array $removeLabelIds = [];

    /**
     * @return array<string>
     */
    public function addLabelIds() : array
    {
        return $this->addLabelIds;
    }

    /**
     * @return array<string>
     */
    public function removeLabelIds() : array
    {
        return $this->removeLabelIds;
    }

    /**
     * @param Label $label
     * @return $this
     */
    public function addLabel(Label $label): self
    {
        $this->addLabelIds[] = $label->id();

        return $this;
    }

    /**
     * @param Label $label
     * @return $this
     */
    public function removeLabel(Label $label): self
    {
        $this->removeLabelIds[] = $label->id();

        return $this;
    }
}