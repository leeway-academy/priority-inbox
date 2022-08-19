<?php

namespace PriorityInbox;

class LabelFilter extends EmailFilter
{
    private Label $label;

    /**
     * @param Label $label
     */
    public function __construct(Label $label)
    {
        $this->label = $label;
    }

    /**
     * @return array<string, array<string>>
     */
    public function getExpression(): array
    {
        return ['labelIds' => [$this->getLabelId()]];
    }

    /**
     * @return string
     */
    private function getLabelId(): string
    {
        return $this->label->id();
    }

    public function isValid(Email $email): bool
    {
        return $email->isLabeled($this->getLabelId());
    }
}
