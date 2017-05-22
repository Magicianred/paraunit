<?php

namespace Tests\Stub;

use Symfony\Component\Console\Output\Output;

/**
 * Class UnformattedOutputStub
 * @package Tests\Stub
 */
class UnformattedOutputStub extends Output
{
    /** @var string */
    private $buffer;

    public function __construct()
    {
        parent::__construct(null, false);
        $this->buffer = '';
    }

    public function getOutput(): string
    {
        return $this->buffer;
    }

    /**
     * {@inheritdoc}
     */
    protected function doWrite($message, $newline)
    {
        $this->buffer .= $message;

        if ($newline) {
            $this->buffer .= "\n";
        }
    }
}
