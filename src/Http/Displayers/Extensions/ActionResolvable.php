<?php

namespace Dcat\Admin\Http\Displayers\Extensions;

trait ActionResolvable
{
    protected function resolveAction( $action )
    {
        $action = new $action();
        
        $action->setGrid($this->grid);
        $action->setColumn($this->column);
        $action->setRow($this->row);
        
        return $action->render();
    }
}