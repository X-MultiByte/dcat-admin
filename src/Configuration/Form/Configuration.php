<?php

namespace Dcat\Admin\Configuration\Form;

use Dcat\Admin\Exception;
use Illuminate\Support\Arr;
use Dcat\Admin\Widgets\Form;

class Configuration extends Form
{
    /**
     * @var array
     */
    protected $configs = [];
    
    /**
     * @var array
     */
    protected $input = [];
    
    /**
     * @var array
     */
    protected array $filters = [ '_token', '_form_', '_current_', '_payload_', '_file_del_', '_normal_', '_remove_', '__NESTED__', '_def_', ];
    
    /**
     * @param $data
     * @param $key
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \Throwable
     */
    public function __construct( $data = [], $key = null )
    {
        parent::__construct($data, $key);
        
        $this->initialize($data);
    }
    
    /**
     * Handle the form request.
     *
     * @param $input
     *
     * @return \Dcat\Admin\Http\JsonResponse
     */
    public function handle( $input )
    {
        $data = request()->all() ?? [];
        
        $data = $this->forget($data);
        $data = $this->filter($data);
        $data = $this->combine($data);
        $this->setInput($data);
        
        $this->update();
        
        return $this->response()
                    ->success('Processed successfully.')
                    ->refresh();
    }
    
    /**
     * Configuration setter.
     *
     * @param  array  $configs
     *
     * @return void
     */
    public function setConfigs( array $configs ): void
    {
        $this->configs = $configs;
        
        if (! empty($this->configs))
        {
            $this->fill($this->configs);
        }
    }
    
    /**
     * Initialize.
     *
     * @param  null  $data
     *
     * @return void
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \Throwable
     */
    protected function initialize( $data = null )
    {
        $configs = empty($data) ? $this->loadConfigurations() : Arr::wrap($data);
        $configs = $this->convertBooleanToInteger($configs);
        $this->setConfigs($configs);
    }
    
    /**
     * @return null|array|\Closure|mixed|object
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \Throwable
     */
    protected function loadConfigurations()
    {
        throw_unless(
            defined('static::CONFIG_NAME'),
            new Exception\UndefinedConfigNameException()
        );
        
        throw_unless(
            file_exists(config_path(sprintf("%s.php", static::CONFIG_NAME))),
            new Exception\ConfigurationFileNotExistsException(static::CONFIG_NAME)
        );
        
        return app('config')->get($this->getConfigName()) ?? [];
    }
    
    /**
     * Converts the boolean values in an array to integers.
     *
     * @param  array  $items
     *
     * @return array
     */
    public function convertBooleanToInteger( array $items )
    {
        return collect($items)->map(function ( $item, $key )
        {
            
            if (gettype($item) == 'boolean')
            {
                return (int) $item;
            }
            elseif (gettype($item) == 'array')
            {
                return $this->convertBooleanToInteger($item);
            }
            
            return $item;
        })->all();
    }
    
    
    /**
     * Converts the integer values in an array to booleans.
     *
     * @param  array  $items
     *
     * @return array
     */
    public function convertIntegerToBoolean( array $items )
    {
        return collect($items)->map(function ( $item, $key )
        {
            if (gettype($item) == 'array')
            {
                return $this->convertIntegerToBoolean($item);
            }
            
            if ($item === 1 || $item === 0)
            {
                return (bool) $item;
            }
            elseif ($item === '1' || $item === '0')
            {
                return (bool) $item;
            }
            
            return $item;
        })->all();
    }
    
    /**
     * @param $key
     * @param $value
     *
     * @return $this|array|\ArrayAccess|mixed
     */
    protected function input( $key = null, $value = null )
    {
        if (is_null($key) && is_null($value))
        {
            return $this->input;
        }
        
        if (is_array($key))
        {
            $this->setInput($key);
            
            return $this;
        }
        
        if ($key && $value)
        {
            $this->setInput($key, $value);
            
            return $this;
        }
        
        return $this->getInput($key);
        
    }
    
    /**
     * @param $key
     * @param $default
     *
     * @return array|\ArrayAccess|mixed
     */
    protected function getInput( $key = null, $default = null )
    {
        return is_null($key) ? $this->input : Arr::get($this->input, $key, $default);
    }
    
    /**
     * @param $key
     * @param $value
     *
     * @return void
     */
    protected function setInput( $key, $value = null )
    {
        if (is_array($key))
        {
            $this->input = $key;
        }
        else
        {
            $this->input[$key] = $value;
        }
        
    }
    
    public function combine( $item )
    {
        $combined = [];
        
        foreach ($item as $key => $value)
        {
            
            if (is_array($value))
            {
                $value = $this->combine($value);
                
                if (Arr::has($value, [ 'keys', 'values' ]))
                {
                    $value = $this->combination($value['keys'], $value['values']);
                }
            }
            
            $combined[$key] = $value['values'] ?? $value;
        }
        
        return $combined;
    }
    
    /**
     * Combine keys and values.
     *
     * @param  array  $keys
     * @param  array  $values
     *
     * @return array
     */
    public function combination( array $keys, array $values )
    {
        return collect($keys)->combine($values)->all();
    }
    
    /**
     * Determine the given key in filter.
     *
     * @param $key
     *
     * @return bool
     */
    public function inFilter( $key ): bool
    {
        return in_array($key, $this->filters);
    }
    
    /**
     * @param $item
     *
     * @return array
     */
    public function forget( $item )
    {
        $array = [];
        foreach ($item as $key => $value)
        {
            if (is_array($value))
            {
                $array[$key] = $this->forget($value);
                continue;
            }
            $array[$key] = $value;
            
            if (is_string($key))
            {
                
                if ($this->inFilter($key))
                {
                    Arr::forget($array, $key);
                }
                continue;
            }
            
            $array[$key] = $value;
        }
        
        return $array;
    }
    
    /**
     * Remove item exists on filter.
     *
     * @param  array  $item
     *
     * @return array
     */
    public function filter( array $item )
    {
        $filtered = [];
        
        foreach ($item as $key => $value)
        {
            if (is_array($value))
            {
                $filtered[$key] = $this->filter($value);
            }
            else
            {
                if (! $this->inFilter($key))
                {
                    $filtered[$key] = $value;
                }
            }
        }
        
        return $filtered;
    }
    
    /**
     * @return string
     */
    protected function getConfigName(): string
    {
        return (string) static::CONFIG_NAME;
    }
    
    protected function update()
    {
        $input  = Arr::dot($this->input());
        $config = Arr::dot($this->configs);
        
        $merged = array_merge($config, $input);
        dump(compact('merged'));
        
        
    }
    
}