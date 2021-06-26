<?php

namespace App\Consumers;

abstract class Consumer
{
    abstract function process();
}
