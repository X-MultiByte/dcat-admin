<?php

namespace Dcat\Admin\Http\Actions\Extensions;

use Dcat\Admin\Admin;
use Dcat\Admin\Grid\RowAction;

class Disable extends RowAction
{
    public function title()
    {
        return '<span class="btn btn-secondary btn-sm btn-outline btn-action">' . trans('admin.disable') . '</span>';
    }
    
    public function handle()
    {
        Admin::extension()->enable($this->getKey(), false);
        
        return $this
            ->response()
            ->success(trans('admin.update_succeeded'))
            ->timeout(3)
            ->refresh();
    }
}
