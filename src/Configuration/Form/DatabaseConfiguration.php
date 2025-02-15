<?php

namespace Dcat\Admin\Configuration\Form;

use Dcat\Admin\Form\Row;

class DatabaseConfiguration extends Configuration
{
    //use Configurable;
    
    /**
     * Configuration name.
     */
    const CONFIG_NAME = 'database';
    
    
    public function form()
    {
        $this->row(function (Row $form) {
            $form->select('default', 'Default')->options($this->getDatabaseConnections());
            $form->text('text', 'Text');
            $form->password('mysql.password', 'Password');
        });
    }
    
    /**
     * Return database connections.
     *
     * @return array
     */
    protected function getDatabaseConnections()
    {
        $configs     = array_keys(config('database.connections'));
        $connections = [];
        foreach ($configs as $config) {
            $connections[$config] = $config;
        }
        
        return $connections;
    }
}