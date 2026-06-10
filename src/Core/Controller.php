<?php

declare(strict_types=1);

namespace App\Core;

abstract class Controller
{
    protected function body(): array
    {
        return Request::body();
    }
}
