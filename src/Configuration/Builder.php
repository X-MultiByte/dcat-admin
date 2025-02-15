<?php

namespace Dcat\Admin\Configuration;

use Illuminate\Contracts\Filesystem\Filesystem;
use Dcat\Admin\Contracts\Configuration\BuilderInterface;

abstract class Builder implements BuilderInterface
{
    public function file(Filesystem $file)
    {
        // TODO: Implement file() method.
    }
    
    public function build()
    {
        // TODO: Implement build() method.
    }
}