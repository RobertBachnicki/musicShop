<?php

namespace FSVendor\WPDesk\Nps;

/**
 * Handles feedback option.
 */
class FeedbackOption implements \JsonSerializable
{
    /**
     * @var string
     */
    private $id;
    /**
     * @var string
     */
    private $label;
    /**
     * @var string
     */
    private $additional_question;
    /**
     * @var bool
     */
    private $required_additional_question = \false;
    /**
     * @param string $label
     * @param string $additional_question
     */
    public function __construct(string $label, string $additional_question = '')
    {
        $this->label = $label;
        $this->additional_question = $additional_question;
    }
    /**
     * @param string $id
     *
     * @return self
     */
    public function set_id(string $id) : self
    {
        $this->id = $id;
        return $this;
    }
    /**
     * @return string
     */
    public function get_label() : string
    {
        return $this->label;
    }
    /**
     * @return string
     */
    public function get_additional_question() : string
    {
        return $this->additional_question;
    }
    /**
     * @return $this
     */
    public function set_required_additional_question() : self
    {
        $this->required_additional_question = \true;
        return $this;
    }
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return ['id' => $this->id, 'label' => $this->label, 'additional_question' => $this->additional_question, 'required_additional_question' => $this->required_additional_question];
    }
}
