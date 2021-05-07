<?php
declare(strict_types=1);

namespace App\Services\Report;


/**
 * Class ReportMessage
 * @package App\Services\Report
 */
class ReportMessage
{
    /**
     * new line symbol
     */
    const LINE_FEED = "\n";

    /**
     * space symbol
     */
    const SPACE = " ";

    /**
     * @var string
     */
    private $text = '';

    /**
     * @return string
     */
    public function getText(): string
    {
        return $this->text;
    }

    /**
     * go to new string
     *
     * @return $this
     */
    public function LF(): self
    {
        $this->text .= self::LINE_FEED;
        return $this;
    }

    /**
     * @param string $text
     * @return $this
     */
    public function addText(string $text): self
    {
        $this->text .= $text;
        return $this;
    }

    /**
     * add space
     *
     * @return $this
     */
    public function SPC(): self
    {
        $this->text .= self::SPACE;
        return $this;
    }


}