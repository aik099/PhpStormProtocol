<?php

interface Disposable
{
    public function cleanup(): void;
}
