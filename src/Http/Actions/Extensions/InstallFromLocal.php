<?php

namespace Dcat\Admin\Http\Actions\Extensions;

use Dcat\Admin\Grid\Tools\AbstractTool;
use Dcat\Admin\Http\Forms\InstallFromLocal as InstallFromLocalForm;
use Dcat\Admin\Widgets\Modal;

class InstallFromLocal extends AbstractTool
{
    protected $style = 'btn btn-primary';
    
    public function html()
    {
        return Modal::make()
                    ->xl()
                    ->title($title = trans('admin.install_from_upload'))
                    ->body(InstallFromLocalForm::make())
                    ->button("<button class='btn btn-info'><i class=\"feather icon-folder\"></i> &nbsp;{$title}</button> &nbsp;");
    }
}
