<?php

namespace Dcat\Admin\Http\Displayers\Extensions;

use Dcat\Admin\Admin;
use Illuminate\Support\Str;
use Dcat\Admin\Grid\Displayers\AbstractDisplayer;
use Dcat\Admin\Http\Actions\Extensions\Disable;
use Dcat\Admin\Http\Actions\Extensions\Enable;
use Dcat\Admin\Http\Actions\Extensions\Uninstall;
use function Laravel\Prompts\text;

class Name extends AbstractDisplayer
{
    use ActionResolvable;
    
    public function display()
    {
        Admin::script("$('[data-toggle=\"popover\"]').popover();");
        
        return Admin::view('admin::grid.displayer.extensions.name', [
            'value'           => $this->value,
            'row'             => $this->row,
            'enableAction'    => $this->resolveAction(Enable::class),
            'disableAction'   => $this->resolveAction(Disable::class),
            'uninstallAction' => $this->resolveAction(Uninstall::class),
            'linkIcon'        => 'icon-link',
            'version'         => $this->getVersion(),
            'installed'       => $this->installed(),
            'packageName'     => $this->getPackageName(),
            'enabled'         => $this->getEnabled(),
        ]);
    }
    
    protected function getVersion()
    {
        return $this->row->version;
    }
    
    protected function installed(): bool
    {
        return $this->getVersion() != 0;
    }
    
    protected function getPackageName()
    {
        return Str::of($this->row->name)->afterLast('.')->title();
    }
    
    protected function getEnabled()
    {
        return $this->row->enabled ?? false;
    }
}
