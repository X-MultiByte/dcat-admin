<?php

namespace Dcat\Admin\Exception;

class UndefinedConfigNameException extends AdminException
{
    public function __construct(
        string $message = 'constant "CONFIG_NAME" must be defined.',
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}