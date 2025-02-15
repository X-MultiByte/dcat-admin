<?php

namespace Dcat\Admin\Http\Actions\Extensions;

use Dcat\Admin\Admin;
use Dcat\Admin\Grid\RowAction;

class Update extends RowAction
{
    public function title()
    {
        $title = ( $this->row->version === '0' ) ? trans('admin.install') : trans('admin.update');
        
        return "<span class=\"btn btn-success btn-sm  btn-action\">{$title}</span>";
    }
    
    public function handle()
    {
        $manager = Admin::extension()->updateManager()->update($this->getKey());
        
        return $this
            ->response()
            ->success(implode('<br>', $manager->notes))
            ->timeout(3)
            ->refresh();
    }
}
