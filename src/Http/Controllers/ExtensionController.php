<?php

namespace Dcat\Admin\Http\Controllers;

use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Layout\Content;
use Dcat\Admin\Support\Helper;
use Illuminate\Routing\Controller;
use Dcat\Admin\Support\StringOutput;
use Illuminate\Support\Facades\Artisan;
use Dcat\Admin\Http\Displayers\Extensions;
use Dcat\Admin\Http\Repositories\Extension;
use Dcat\Admin\Http\Actions\Extensions\InstallFromLocal;

class ExtensionController extends Controller
{
    use HasResourceActions;
    
    public function index( Content $content )
    {
        return $content
            ->title(admin_trans_label('Extensions'))
            ->description(trans('admin.list'))
            ->body($this->grid());
    }
    
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return new Grid(new Extension(), function ( Grid $grid )
        {
            $grid->tableCollapse(false);
            
            // Row numbers.
            $grid->number()->width('5%');
            $grid->column('logo', __('admin.logo'))
                 ->width('10%')
                 ->image('', 50, 50);
            // Name
            $grid->column('name', __('admin.extension'))
                 ->width('35%')
                 ->displayUsing(Extensions\Name::class);
            
            // Description
            $grid->column('description', __('admin.action'))->width('45%')
                 ->displayUsing(Extensions\Description::class);
            
            /*
            $grid->column('authors')
                 ->display(function ( $authors ) {
                     if ( ! $authors ) {
                         return;
                     }
                     $str = '';
                     foreach ( $authors as &$author ) {
                         $str .= "<span class='text-80'>{$author['name']}</span>" . PHP_EOL;
                         
                         if ( Arr::has($author, 'email') ) {
                             
                             $str .= '<code>' . $author['email'] . '</code>';
                         }
                     }
                     
                     return $str;
                 });
            */
            $grid->allowColumnSelector();
            $grid->showColumnSelector();
            $grid->disablePagination();
            $grid->disableCreateButton();
            $grid->disableDeleteButton();
            $grid->disableBatchDelete();
            $grid->disableFilterButton();
            $grid->disableFilter();
            $grid->disableQuickEditButton();
            $grid->disableEditButton();
            //$grid->disableDeleteButton();
            $grid->disableViewButton();
            $grid->disableActions();
            
            $grid->tools([
                new InstallFromLocal(),
            ]);
            
            $grid->quickCreate(function ( Grid\Tools\QuickCreate $create )
            {
                $create->show();
                
                $create->text('name', 'dcat-admin/demo')
                       ->attribute('style', 'width:350px')
                       ->required();
                
                $create->text('namespace', 'DcatAdmin\\Demo')
                       ->width(6);
                
                $create->select('type', 'Select Type')
                       ->options([
                           1 => trans('admin.application'),
                           2 => trans('admin.theme'),
                       ])
                       ->width(3)
                       ->default(1)
                       ->required();
            });
        });
    }
    
    public function form()
    {
        $form = new Form(new Extension());
        
        $form->hidden('name')
             ->rules(function ()
             {
                 return [
                     'required',
                     function ( $attribute, $value, $fail )
                     {
                         if (! Helper::validateExtensionName($value))
                         {
                             return $fail(
                                 "[$value] is not a valid package name, please type a name like \"vendor/name\""
                             );
                         }
                     },
                 ];
             });
        $form->hidden('namespace')->rules([
            'required', function ( $attribute, $value, $fail )
            {
                if (! Helper::validateExtensionNamespace($value))
                {
                    return $fail("[$value] is not a valid Namespace, please type a name like \"Some\\Package\"");
                }
            },
        ]);
        $form->hidden('type')->rules([ 'required' ]);
        
        $self = $this;
        
        $form->saving(function ( Form $form ) use ( $self )
        {
            $package   = $form->name;
            $namespace = $form->namespace;
            $type      = $form->type;
            
            if ($package)
            {
                $results = $self->createExtension($package, $namespace, $type);
                
                return $form->response()->refresh()->timeout(5)->success($results);
            }
        });
        
        return $form;
    }
    
    /**
     * Create an extension.
     *
     * @param $package
     * @param $namespace
     * @param $type
     *
     * @return string
     */
    public function createExtension( $package, $namespace, $type )
    {
        $namespace = trim($namespace, '\\');
        
        $output = new StringOutput();
        
        Artisan::call('admin:ext-make', [
            'name'        => $package,
            '--namespace' => $namespace ?: 'default',
            '--theme'     => $type == 2,
        ], $output);
        
        return '<pre class="bg-transparent text-white">' . (string) $output->getContent() . '</pre>';
    }
}
