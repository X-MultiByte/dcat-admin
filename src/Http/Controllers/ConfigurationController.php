<?php

namespace Dcat\Admin\Http\Controllers;

use Dcat\Admin\Admin;
use Dcat\Admin\Http\JsonResponse;
use Dcat\Admin\Widgets\Tab;
use Illuminate\Http\Request;
use Dcat\Admin\Layout\Content;
use App\Http\Controllers\Controller;
use Dcat\Admin\Configuration\Form\AdminConfiguration;
use Dcat\Admin\Configuration\Form\DatabaseConfiguration;

class ConfigurationController extends Controller
{
    /**
     * Configuration.
     *
     * @param  \Dcat\Admin\Layout\Content  $content
     *
     * @return \Dcat\Admin\Layout\Content
     */
    public function index(Content $content)
    {
        $tab = Tab::make();
        $tab->add('Admin', new AdminConfiguration(), true);
        $tab->add('Database', new DatabaseConfiguration(), false, 'database');
        $tab->withCard();
        
        return $content
            ->header('Configuration')
            ->description(' ')
            ->body($tab);
    }
    
    /**
     * Update Configuration.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return \Dcat\Admin\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $data = $request->all();
        dump($data);
        
        return $this->response()->success('Configuration has been saved.');
    }
    
    /**
     * Return JSON response.
     *
     * @return \Dcat\Admin\Http\JsonResponse
     */
    public function response(): JsonResponse
    {
        return Admin::json();
    }
}