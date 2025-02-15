<?php

namespace Dcat\Admin\Http\Actions\Extensions;

use Dcat\Admin\Admin;
use Dcat\Admin\Grid\RowAction;

class Enable extends RowAction
{
    public function title()
    {
        return '<span class="btn btn-success btn-sm btn-outline btn-action">' . trans('admin.enable') . '</span>';
    }
    
    public function handle()
    {
        Admin::extension()->enable($this->getKey());
        
        Admin::js('console.log("enabled");');
        
        return $this
            ->response()
            ->success(trans('admin.update_succeeded'))
            ->timeout(3)
            ->refresh();
    }
}
