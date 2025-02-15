<?php

namespace Dcat\Admin\Http\Actions\Extensions;

use Dcat\Admin\Admin;
use Dcat\Admin\Grid\RowAction;

class Install extends RowAction
{
    public function confirm()
    {
        return [ trans('admin.confirm_uninstall'), $this->getKey() ];
    }
    
    public function title()
    {
        $label = trans('admin.uninstall');
        
        return '<span class="btn btn-danger btn-sm btn-action">' . $label . '</span>';
    }
    
    public function handle()
    {
        $manager = Admin::extension()
                        ->updateManager()
                        ->rollback($this->getKey());
        
        Admin::extension()->get($this->getKey())->uninstall();
        
        return $this
            ->response()
            ->success(implode('<br>', $manager->notes))
            ->timeout(3)
            ->refresh();
    }
}
