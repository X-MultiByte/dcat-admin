<?php

namespace Dcat\Admin\Configuration\Form;

use Illuminate\Support\Str;

class AdminConfiguration extends Configuration
{
    /**
     * Configuration name.
     */
    public const CONFIG_NAME = 'admin';
    
    /**
     * Configuration Form
     *
     * @return void
     */
    public function form(): void
    {
        //$this->action(admin_url('/configuration'));
        //$this->hidden('config_name')->value($this->getConfigName());
        $this->tab('System', function () {
            
            $this->text('name', 'Name');
            $this->text('title', 'Title');
            $this->text('directory', 'Directory');
            $this->text('exception_handler', 'Exception Handler');
            $this->radio('https', 'Https')
                 ->options([
                     1 => 'Enable',
                     0 => 'Disable',
                 ])
                 ->saving(fn($value) => (bool) $value);
            
            $this->radio('enable_default_breadcrumb', 'Breadcrumb')
                 ->options([
                     1 => 'Enable',
                     0 => 'Disable',
                 ])->saving(fn($value) => (bool) $value);
            
            $this->radio('helpers.enable', 'Helpers')
                 ->options([
                     1 => 'Enable',
                     0 => 'Disable',
                 ])->saving(fn($value) => (bool) $value);
            
            
            $this->textarea('logo', 'Logo')->rows(2);
            $this->textarea('logo-mini', 'Logo Minimize')->rows(2);
            $this->text('favicon', 'Favicon');
            $this->text('default_avatar', 'Default Avatar');
            $this->text('assets_server', 'Assets Server');
            
            // Upload
            $this->divider('Upload');
            $this->select('upload.disk', 'Disk')->options($this->getFileSystemDisks());
            $this->keyValue('upload.directory', 'Directory');
        }, true, 'admin-system');
        
        
        // Route
        $this->tab('Route', function () {
            $this->text('route.domain', 'domain');
            $this->text('route.prefix', 'prefix');
            $this->text('route.namespace', 'namespace');
            $this->list('route.middleware', 'middleware');
            $this->radio('route.enable_session_middleware', 'Session Middleware')
                 ->options([
                     1 => 'Enable',
                     0 => 'Disable',
                 ])->saving(fn($value) => (bool) $value);
            
        }, false, 'admin-route');
        
        
        // Auth
        $this->tab('Auth', function () {
            $this->radio('auth.enable', 'Enable')
                 ->options([
                     1 => 'Enable',
                     0 => 'Disable',
                 ])->saving(fn($value) => (bool) $value);
            
            $this->radio('auth.remember', 'Remember')
                 ->options([
                     1 => 'Enable',
                     0 => 'Disable',
                 ])->saving(fn($value) => (bool) $value);
            
            $this->radio('auth.enable_session_middleware', 'Session Middleware')
                 ->options([
                     1 => 'Enable',
                     0 => 'Disable',
                 ])->saving(fn($value) => (bool) $value);
            
            $this->text('auth.controller', 'Controller');
            $this->list('auth.except', 'Except');
            
        }, false, 'admin-auth');
        
        // Grid
        $this->tab('Grid', function () {
            $this->text('grid.grid_action_class', 'Grid Action')
                 ->help('The global Grid action display class', 'feather icon-info');
            $this->text('grid.batch_action_class', 'Batch Action');
            $this->text('grid.paginator_class', 'Paginator');
            $this->text('grid.column_selector.store', 'Column Selector');
            $this->text('grid.column_selector.store_params.driver', 'Column Selector WebDriver');
            
            $this->keyValue('grid.actions', 'Action')
                 ->setKeyLabel('Action')
                 ->setValueLabel('Class');
        }, false, 'admin-grid');
        // Form
        $this->tab('Form', function () {
            $this->radio('form.enable_default_icon', 'Icon')
                 ->options([
                     1 => 'Enable',
                     0 => 'Disable',
                 ])->saving(fn($value) => (bool) $value);
            
            $this->radio('form.enable_default_placeholder', 'Placeholder')
                 ->options([
                     1 => 'Enable',
                     0 => 'Disable',
                 ])->saving(fn($value) => (bool) $value);
        });
        
        //Permission
        $this->tab('Permission', function () {
            $this->radio('permission.enable', 'Enable')->options([
                1 => 'Enable',
                0 => 'Disable',
            ])->saving(fn($value) => (bool) $value);
            
            $this->list('permission.except', 'Except');
            
        }, false, 'admin-permission');
        // Menu
        $this->tab('Menu', function () {
            $this->radio('menu.cache.enable', 'Cache')->options([
                1 => 'Enable',
                0 => 'Disable',
            ])->saving(fn($value) => (bool) $value)
                 ->when(1, function () {
                     $this->select('menu.cache.store', 'Cache   Driver')
                          ->options($this->getCacheDrivers());
                 });
            
            $this->radio('menu.bind_permission', 'Bind Permission')
                 ->options([
                     1 => 'Enable',
                     0 => 'Disable',
                 ])->saving(fn($value) => (bool) $value);
            
            $this->radio('menu.role_bind_menu', 'Bind Role')
                 ->options([
                     1 => 'Enable',
                     0 => 'Disable',
                 ])->saving(fn($value) => (bool) $value);
            
            $this->radio('menu.permission_bind_menu', 'Permission Bind Menu')
                 ->options([
                     1 => 'Enable',
                     0 => 'Disable',
                 ])->saving(fn($value) => (bool) $value);
            
            $this->icon('menu.default_icon', 'Default Icon');
            
        }, false, 'admin-menu');
        // Database
        $this->tab('Database', function () {
            $this->select('database.connection', 'Connection')
                 ->options($this->getDatabaseConnections());
            
            $this->keyValue('database', 'Table & Model');
            
            
        }, false, 'admin-database');
        // Layout
        $this->tab('Layout', function () {
            $this->radio('layout.color', 'Layout')
                 ->options([
                     'default'    => 'Default',
                     'blue'       => 'Blue',
                     'blue-light' => 'Blue Light',
                     'green'      => 'Green',
                 ]);
            
            $this->text('layout.body_class', 'Body Class');
            $this->select('layout.sidebar_style', 'Sidebar Style')
                 ->options([
                     'primary' => 'Primary',
                     'light'   => 'Light',
                     'dark'    => 'Dark',
                 ]);
            
            $this->select('layout.navbar_color', 'Navbar Color')
                 ->options([
                     'bg-primary' => 'Primary',
                     'bg-info'    => 'Info',
                     'bg-warning' => 'Warning',
                     'bg-success' => 'Success',
                     'bg-danger'  => 'Danger',
                     'bg-dark'    => 'Dark',
                 ]);
            
            $this->radio('layout.horizontal_menu', 'Horizontal')
                 ->options([
                     1 => 'Enable',
                     0 => 'Disable',
                 ])->saving(fn($value) => (bool) $value);
            
            $this->radio('layout.sidebar_collapsed', 'Sidebar Collapsed')
                 ->options([
                     1 => 'Enable',
                     0 => 'Disable',
                 ])->saving(fn($value) => (bool) $value);
            
            $this->radio('layout.dark_mode_switch', 'Dark Mode Switch')
                 ->options([
                     1 => 'Enable',
                     0 => 'Disable',
                 ])->saving(fn($value) => (bool) $value);
        }, false, 'admin-layout');
        // Extension
        $this->tab('Extension', function () {
            
            $this->text('extension.dir', 'Directory');
            
            $this->divider('Default');
            
            $this->list('extension.default.keywords', 'Keywords');
            
            $this->array('extension.default.authors', 'Authors', function ($array) {
                $array->text('name', 'Name');
                $array->text('email', 'Email');
                $array->text('homepage', 'Homepage')
                      ->help("URL to the author's website.");
                $array->text('role', 'Role')
                      ->help("The author's role in the project (e.g. developer or translator)");
            });
            
            
        }, false, 'admin-extension');
    }
    
    /**
     * Return cache drivers.
     *
     * @return string[]
     */
    protected function getCacheDrivers(): array
    {
        $configs       = array_keys(config('cache.stores'));
        $cache_drivers = [];
        
        foreach ($configs as $config) {
            $cache_drivers[$config] = Str::title($config);
        }
        
        return $cache_drivers;
    }
    
    /**
     * Return filesystem disks.
     *
     * @return array
     */
    protected function getFileSystemDisks(): array
    {
        $configs = array_keys(config('filesystems.disks'));
        $disks   = [];
        
        foreach ($configs as $config) {
            $disks[$config] = Str::title($config);
        }
        
        return $disks;
    }
    
    /**
     * Return database connections.
     *
     * @return array
     */
    protected function getDatabaseConnections(): array
    {
        $configs     = array_keys(config('database.connections'));
        $connections = [];
        
        foreach ($configs as $config) {
            $connections[$config] = $config;
        }
        
        return $connections;
    }
    
}
