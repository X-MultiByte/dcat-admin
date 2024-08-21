<?php

namespace Dcat\Admin\Support;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Filesystem\Filesystem;

final class Configuration
{
    const STUB_PATH = __DIR__.'/stubs/config.stub';
    
    /**
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected Filesystem $filesystem;
    
    /**
     * @var array
     */
    protected array $configs = [];
    
    /**
     * @var string
     */
    protected string $name;
    
    /**
     * @var string
     */
    protected string $path;
    
    /**
     * @param  string  $name
     * @param  \Illuminate\Filesystem\Filesystem  $filesystem
     */
    public function __construct(string $name, Filesystem $filesystem)
    {
        $this->name       = $name;
        $this->filesystem = $filesystem;
        $this->path       = config_path(sprintf("%s.php", $this->name));
        
        if ($this->exists()) {
            $this->configs = config($this->name);
        }
    }
    
    /**
     * Check the configuration file exists.
     *
     * @return bool
     */
    public function exists(): bool
    {
        return $this->filesystem->exists($this->path);
    }
    
    /**
     * Open the configuration.
     *
     * @param  string  $name
     *
     * @return static
     */
    public static function open(string $name)
    {
        return new static($name, new Filesystem);
    }
    
    /**
     * Append to configuration.
     *
     * @param  string  $key
     * @param $value
     *
     * @return $this
     */
    public function append(string $key, $value)
    {
        if (is_null($this->get($key))) {
            $this->set($key, $value);
        }
        
        return $this;
    }
    
    /**
     * Return config values.
     *
     * @param $key
     * @param $default
     *
     * @return array|\ArrayAccess|mixed
     */
    public function get($key = null, $default = null)
    {
        if ($key === null) {
            return $this->all();
        }
        
        return Arr::get($this->configs, $key, $default);
    }
    
    /**
     * Return all configurations.
     *
     * @return array
     */
    public function all(): array
    {
        return $this->configs;
    }
    
    /**
     * Set config key and value.
     *
     * @param  array|string  $key
     * @param $value
     *
     * @return $this
     */
    public function set(array | string $key, $value = null)
    {
        if (is_array($key)) {
            $this->configs = $key;
        } else {
            Arr::set($this->configs, $key, $value);
        }
        
        return $this;
    }
    
    /**
     * Remove the configuration by the given key.
     *
     * @param $key
     *
     * @return void
     */
    public function delete($key): void
    {
        Arr::forget($this->configs, $key);
    }
    
    /**
     * Check the given key exists.
     *
     * @param $key
     *
     * @return bool
     */
    public function has($key): bool
    {
        return Arr::has($this->configs, $key);
    }
    
    /**
     * Save the configuration to a file.
     *
     * @return void
     */
    public function save()
    {
        $exported = var_export($this->configs, true);
        
        $this->filesystem
            ->put(
                $this->path,
                '<?php'.
                PHP_EOL.
                PHP_EOL.
                'return '.$this->format($exported).';'
            );
    }
    
    /**
     * Format the content to regular configuration.
     *
     * @param  string  $content
     *
     * @return string
     */
    private function format(string $content): string
    {
        return Str::of($content)
                  ->replace('array (', '[')
                  ->replace(')', ']')
                  ->replace("=> \n", '=> ')
                  ->replaceMatches('/[0-9]+ =>/', '')
                  ->replaceMatches('/=>\s+\[/', '=> [');
    }
}