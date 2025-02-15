<?php

namespace Dcat\Admin\Http\Displayers\Extensions;

use Dcat\Admin\Admin;
use Dcat\Admin\Widgets\Modal;
use Dcat\Admin\Http\Actions\Extensions;
use Dcat\Admin\Grid\Displayers\AbstractDisplayer;

class Description extends AbstractDisplayer
{
    use ActionResolvable;
    
    public function display()
    {
        return Admin::view('admin::grid.displayer.extensions.description', [
            'value'           => $this->value,
            'row'             => $this->row,
            'settingAction'   => $this->resolveSettingForm(),
            'updateAction'    => $this->resolveAction(Extensions\Update::class),
            'enableAction'    => $this->resolveAction(Extensions\Enable::class),
            'disableAction'   => $this->resolveAction(Extensions\Disable::class),
            'uninstallAction' => $this->resolveAction(Extensions\Uninstall::class),
            'enabled'         => $this->row->enabled,
        ]);
    }
    
    protected function resolveSettingForm()
    {
        $extension = app('admin.extend')->get($this->getKey());
        
        if (! method_exists($extension, 'settingForm')) {
            return;
        }
        
        $label = '<span class="btn btn-primary btn-sm btn-outline btn-action">' . trans('admin.setting') . '</span>';
        
        return Modal::make()
                    ->xl()
                    ->title($this->getModalTitle($extension))
                    ->body($extension->settingForm())
                    ->button($label);
    }
    
    /**
     * @param $extension
     *
     * @return string
     */
    protected function getModalTitle( $extension )
    {
        return $extension->settingForm()->title()
            ?: ( trans('admin.setting') . ' - ' . str_replace('.', '/', $this->getKey()) );
    }
}
