<?php

final class AssertionFailedException extends RuntimeException
{
    public string $expected;

    public string $actual;

    public function __construct(string $expected, string $actual, string $message = '')
    {
        parent::__construct($message !== '' ? $message : 'assertEquals() failed');

        $this->expected = $expected;
        $this->actual = $actual;
    }
}
