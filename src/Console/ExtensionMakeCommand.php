<?php

namespace Dcat\Admin\Console;

use Illuminate\Support\Arr;
use Dcat\Admin\Support\Helper;
use PHPUnit\Event\Runtime\PHP;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Illuminate\Contracts\Filesystem\FileNotFoundException;

class ExtensionMakeCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'admin:ext-make 
    {name : The name of the extension. Eg: author-name/extension-name} 
    {--namespace= : The namespace of the extension.}
    {--theme}
    ';
    
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Build a dcat-admin extension';
    
    /**
     * @var string
     */
    protected $basePath = '';
    
    /**
     * @var Filesystem
     */
    protected $filesystem;
    
    /**
     * @var string
     */
    protected $namespace;
    
    /**
     * @var string
     */
    protected $className;
    
    /**
     * @var string
     */
    protected $extensionName;
    
    /**
     * @var string
     */
    protected $package;
    
    /**
     * @var string
     */
    protected $extensionDir;
    
    /**
     * @var array
     */
    protected $dirs;
    
    protected $themeDirs = [
        'updates',
        'resources/assets/css',
        'resources/views',
        'src',
    ];
    
    /**
     * @var array
     */
    protected $default;
    
    /**
     * @var string[]
     */
    protected $files;
    
    /**
     * @var string
     */
    protected $version;
    
    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(Filesystem $filesystem)
    {
        $this->filesystem   = $filesystem;
        $this->extensionDir = admin_extension_path();
        
        $this->loadExtensionDefault();
        
        if ( !file_exists($this->extensionDir)) {
            $this->makeDir();
        }
        
        $this->package       = str_replace('.', '/', $this->argument('name'));
        $this->extensionName = str_replace('/', '.', $this->package);
        
        $this->basePath = rtrim($this->extensionDir, '/').'/'.ltrim($this->package, '/');
        
        if (is_dir($this->basePath)) {
            return $this->error(sprintf('The extension [%s] already exists!', $this->package));
        }
        
        InputExtensionName :
        if ( !Helper::validateExtensionName($this->package)) {
            $this->package = $this->ask("[$this->package] is not a valid package name, please input a name like (<vendor>/<name>)");
            goto InputExtensionName;
        }
        
        $this->makeDirs();
        $this->makeFiles();
        
        $this->info("The extension scaffolding generated successfully. \r\n");
        $this->showTree();
    }
    
    /**
     * Show extension scaffolding with tree structure.
     */
    protected function showTree()
    {
        $tree = directory($this->extensionPath(), Helper::DIRECTORY_OUTPUT_TREE) ?? '';
        
        $this->info($tree);
    }
    
    /**
     * Make extension files.
     */
    protected function makeFiles()
    {
        $this->namespace = $this->getRootNameSpace();
        
        $this->className = $this->getClassName();
        
        // copy files
        $this->copyFiles();
        
        // make composer.json
        $this->makeFile(
            [
                '{package}'     => $this->package,
                '{alias}'       => '',
                '{namespace}'   => str_replace('\\', '\\\\', $this->namespace).'\\\\',
                '{className}'   => $this->className,
                '{description}' => $this->default('description'),
                '{version}'     => $this->version,
                '{license}'     => $this->default('license'),
                '{type}'        => $this->default('type'),
                '{keywords}'    => $this->getKeywords($this->default('keywords')),
                '{authors}'     => $this->getAuthors($this->default('authors')),
            ],
            $this->stubPath('composer.json'), 'composer.json'
        );
        
        // make setting.stub
        $this->makeFile(
            [
                '{namespace}' => $this->namespace,
            ],
            $this->stubPath('setting'), 'src/Setting.php'
        );
        
        if ($this->default('facade')) {
            // make facade
            $this->makeFile(
                [
                    '{namespace}'  => $this->namespace,
                    '{className}'  => $this->className,
                    '{facadeName}' => Helper::slug(basename($this->package)),
                ],
                $this->stubPath('facade'),
                "src/Facades/{$this->className}.php"
            );
        }
        
        if ($this->default('config')) {
            // make config
            $this->makeFile(
                ['{config_default}' => $this->default('config_default')],
                $this->stubPath('config'),
                'config/'.Helper::slug(basename($this->package)).'.php'
            );
        }
        
        $this->makeFile([
            '{namespace}' => $this->namespace,
            '{className}' => $this->className,
        ], $this->stubPath('base_class'), "src/{$this->className}.php");
        
        // make service provider
        $basePackage = Helper::slug(basename($this->package));
        $this->makeFile(
            [
                '{namespace}'     => $this->namespace,
                '{className}'     => $this->className,
                '{title}'         => Str::title($this->className),
                '{path}'          => $basePackage,
                '{basePackage}'   => $basePackage,
                '{property}'      => $this->makeProviderContent(),
                '{registerTheme}' => $this->makeRegisterThemeContent(),
            ],
            $this->stubPath('provider'), "src/{$this->className}ServiceProvider.php"
        );
        
        if ( !$this->option('theme')) {
            // make controller
            $this->makeFile(
                [
                    '{namespace}' => $this->namespace,
                    '{className}' => $this->className,
                    '{name}'      => $this->extensionName,
                ],
                $this->stubPath('controller'), "src/Http/Controllers/{$this->className}Controller.php"
            );
            
            // make index.blade.php
            $this->makeFile(['{name}' => $this->extensionName,], $this->stubPath('view'), 'resources/views/index.blade.php');
            
            // make routes
            $this->makeFile([
                '{namespace}' => $this->namespace,
                '{className}' => $this->className,
                '{path}'      => $basePackage,
            ], $this->stubPath('routes.stub'), 'routes/admin.php');
        }
    }
    
    /**
     * Return a provider property.
     *
     * @return string
     */
    protected function makeProviderContent()
    {
        $content = '';
        
        
        if ( !$this->option('theme')) {
            
            $content .= $this->makeMenu($this->default('menu'));
            $content .= <<<'TEXT'

    protected $js = [
        'js/index.js',
    ];
    
TEXT;
        
        
        } else {
            $content .= <<<'TEXT'
        
        return <<<'TEXT'
protected $type = self::TYPE_THEME;

TEXT;
        }
        
        return $content;
    }
    
    protected function makeRegisterThemeContent()
    {
        if ( !$this->option('theme')) {
            return;
        }
        
        return <<<'TEXT'
Admin::baseCss($this->formatAssetFiles($this->css));
TEXT;
    }
    
    protected function copyFiles()
    {
        $files     = $this->files;
        $formatted = [];
        
        if ($this->option('theme')) {
            Arr::forget($files, ['view.stub', 'js.stub']);
        }
        
        foreach ($files as $source => $destination) {
            
            if ($this->filesystem->missing($destination)) {
                $new_source             = $this->stubPath($source);
                $formatted[$new_source] = $destination;
            }
            
        }
        
        $this->files = $formatted;
        
        $this->copy($this->files);
    }
    
    /**
     * Get root namespace for this package.
     *
     * @return array|null|string
     */
    protected function getRootNameSpace()
    {
        [$vendor, $name] = explode('/', $this->package);
        
        $default = str_replace(['-'], '', Str::title($vendor).'\\'.Str::title($name));
        
        if ( !$namespace = $this->option('namespace')) {
            $namespace = $this->ask('Root namespace', $default);
        }
        
        return $namespace === 'default' ? $default : $namespace;
    }
    
    /**
     * Get extension class name.
     *
     * @return string
     */
    protected function getClassName()
    {
        return ucfirst(Str::camel(basename($this->package)));
    }
    
    /**
     * Create package dirs.
     */
    protected function makeDirs()
    {
        $this->makeDir($this->option('theme') ? $this->themeDirs : $this->dirs);
    }
    
    /**
     * Extension path.
     *
     * @param  string  $path
     *
     * @return string
     */
    protected function extensionPath($path = '')
    {
        $path = rtrim($path, '/');
        
        if (empty($path)) {
            return rtrim($this->basePath, '/');
        }
        
        return rtrim($this->basePath, '/').'/'.ltrim($path, '/');
    }
    
    /**
     * Put contents to file.
     *
     * @param  string  $to
     * @param  string  $content
     */
    protected function putFile($to, $content)
    {
        $to = $this->extensionPath($to);
        
        $this->filesystem->put($to, $content);
    }
    
    /**
     * Copy files to extension path.
     *
     * @param  string|array  $from
     * @param  string|null   $to
     */
    protected function copy($from, $to = null)
    {
        if (is_array($from) && is_null($to)) {
            foreach ($from as $key => $value) {
                $this->copy($key, $value);
            }
            
            return;
        }
        
        if ( !file_exists($from)) {
            return;
        }
        
        $to = $this->extensionPath($to);
        
        $this->filesystem->copy($from, $to);
    }
    
    /**
     * Make new directory.
     *
     * @param  array|string  $paths
     */
    protected function makeDir($paths = '')
    {
        foreach ((array) $paths as $path) {
            $path = $this->extensionPath($path);
            
            $this->filesystem->makeDirectory($path, 0755, true, true);
        }
    }
    
    /**
     * Retrieve default value or set default value.
     *
     * @param  null  $key
     * @param  null  $value
     *
     * @return array|\ArrayAccess|mixed|void
     */
    protected function default($key = null, $value = null)
    {
        if ($key === null && $value === null) {
            if (is_null($this->default)) {
                $this->default = config('admin.extension.default');
            }
            
            return $this->default;
        }
        
        if ($key && empty($value)) {
            return Arr::get($this->default, $key);
        }
        
        Arr::set($this->default, $key, $value);
    }
    
    /**
     * Return the stub path.
     *
     * @param  string  $name
     *
     * @return string
     */
    protected function stubPath(string $name)
    {
        $name = str_replace('.stub', '', $name);
        
        return __DIR__.'/stubs/extension/'.$name.'.stub';
    }
    
    /**
     * Get stub content.
     *
     * @param  string  $path
     *
     * @return null|string
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function getStub(string $path)
    {
        if ($this->filesystem->exists($path)) {
            return $this->filesystem->get($path);
        }
        
        return null;
    }
    
    /**
     * Load extension preset value.
     *
     * @return void
     */
    protected function loadExtensionDefault()
    {
        $this->default                       = config('admin.extension.default');
        $this->dirs                          = $this->default['dirs'] ?? [];
        $this->files                         = $this->default['files'] ?? [];
        $this->files[$this->default('logo')] = 'logo.png';
        $this->version                       = $this->default['version'] ?? '1.0.0';
        
        if ($this->default('config')) {
            $this->dirs[] = 'config';
        }
        
        if ($this->default('facade')) {
            $this->dirs[] = 'src/Facades';
        }
        
    }
    
    /**
     * Use stub to create files.
     *
     * @param  array   $replacements
     * @param  string  $stub_path
     * @param  string  $save_path
     *
     * @return void
     */
    protected function makeFile(array $replacements, string $stub_path, string $save_path)
    {
        $this->putFile($save_path, str_replace(
            array_keys($replacements),
            array_values($replacements),
            $this->getStub($stub_path)
        ));
    }
    
    /**
     * Get formatted authors.
     *
     * @param $authors
     *
     * @return string
     */
    protected function getAuthors($authors)
    {
        $content = '';
        
        if (is_array($authors) && !empty($authors)) {
            $entries = count($authors);
            $counter = 1;
            foreach ($authors as $author) {
                $content .= '{';
                $content .= sprintf("\"name\": \"%s\", ", $author['name']);
                $content .= sprintf("\"email\": \"%s\"", $author['email']);
                if (Arr::exists($author, 'homepage')) {
                    $content .= sprintf(", \"homepage\": \"%s\"", $author['homepage']);
                }
                if (Arr::exists($author, 'role')) {
                    $content .= sprintf(", \"role\": \"%s\"", $author['role']);
                }
                $content .= $counter === $entries ? '}' : '},'.PHP_EOL;
                $counter++;
            }
        }
        
        return $content;
    }
    
    /**
     * Get formated keywords.
     *
     * @param $keywords
     *
     * @return string
     */
    protected function getKeywords($keywords)
    {
        $content = '';
        
        if (is_array($keywords) && !empty($keywords)) {
            $entries = count($keywords);
            $counter = 1;
            foreach ($keywords as $keyword) {
                $content .= sprintf("\"%s\"", $keyword);
                $content .= $counter === $entries ? '' : ', ';
                $counter++;
            }
        }
        
        return $content;
    }
    
    protected function makeMenu($menu)
    {
        $content = '';
        
        if ($menu) {
            $content = <<<'TEXT'

    //protected $menu = [];

TEXT;
        }
        
        return $content;
    }
}
