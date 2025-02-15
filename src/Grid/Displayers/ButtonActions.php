<?php

namespace Dcat\Admin\Grid\Displayers;

class ButtonActions extends Actions
{
    /**
     * @return string
     */
    protected function getViewLabel()
    {
        $label = trans('admin.show');
        
        return '<span class="btn btn-sm btn-info btn-outline"  style="min-width: 65px;">' . $label .
            '</span>&nbsp&nbsp';
    }
    
    /**
     * @return string
     */
    protected function getEditLabel()
    {
        $label = trans('admin.edit');
        
        return '<span class="btn btn-sm btn-info btn-outline"  style="min-width: 65px;">' . $label .
            '</span>&nbsp&nbsp';
    }
    
    /**
     * @return string
     */
    protected function getQuickEditLabel()
    {
        $label  = trans('admin.edit');
        $label2 = trans('admin.quick_edit');
        
        return '<span class="btn btn-sm btn-info btn-outline" style="min-width: 65px;">' . $label . '</span>&nbsp&nbsp';
    }
    
    /**
     * @return string
     */
    protected function getDeleteLabel()
    {
        $label = trans('admin.delete');
        
        return '<span class="btn btn-sm btn-danger btn-outline" style="min-width: 65px;">' . $label .
            '</span>&nbsp&nbsp';
    }
}
