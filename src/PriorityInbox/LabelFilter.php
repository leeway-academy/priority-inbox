<?php

namespace PriorityInbox;

use JetBrains\PhpStorm\ArrayShape;

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

    #[ArrayShape(['labelIds' => "string[]"])]
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
}