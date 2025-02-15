<?php

namespace Dcat\Admin\Exception;

class ConfigurationFileNotExistsException extends AdminException
{
    public function __construct($file = null)
    {
        if (is_null($file)) {
            parent::__construct('Configuration file not exists.');
        } else {
            parent::__construct("The configuration '{$file}' does not exist.");
        }
    }
}