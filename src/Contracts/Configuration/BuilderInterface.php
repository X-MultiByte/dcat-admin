<?php

namespace Dcat\Admin\Contracts\Configuration;

interface BuilderInterface
{
    /**
     * Save configuration.
     */
    public function save();
    
    /**
     * Build configuration.
     */
    public function build();
}