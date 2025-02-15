<?php

namespace Dcat\Admin\Console;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Dcat\Admin\Support\Helper;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class ExtensionMakeCommand extends Command
{
    /**
     * Stub Directory.
     *
     * @const string
     */
    public const STUB_DIR = __DIR__ . '/stubs/extension';
    
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
    
    protected $rootNamespace;
    
    protected $extensionNamespace;
    
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
     * @var string
     */
    protected $serviceProviderName;
    
    /**
     * @var string
     */
    protected $serviceProviderWithNamespace;
    
    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle( Filesystem $filesystem )
    {
        $this->filesystem   = $filesystem;
        $this->extensionDir = admin_extension_path();
        $this->loadExtensionDefault();
        
        if (! file_exists($this->extensionDir)) {
            $this->makeDir();
        }
        
        $this->package       = str_replace('.', '/', $this->argument('name'));
        $this->extensionName = str_replace('/', '.', $this->package);
        $this->rootNamespace = $this->getRootNameSpace();
        $this->basePath      = rtrim($this->extensionDir, '/') . '/' . ltrim($this->package, '/');
        
        if (is_dir($this->basePath)) {
            $this->error(sprintf('The extension [%s] already exists!', $this->package));
            
            return;
        }
        
        InputExtensionName :
        if (! Helper::validateExtensionName($this->package)) {
            $this->package = $this->ask("[$this->package] is not a valid package name, please input a name like (<vendor>/<name>)");
            goto InputExtensionName;
        }
        
        $this->makeDirs();
        $this->makeFiles();
        
        $this->info("The extension scaffolding generated successfully. \r\n");
        $this->showTree();
    }
    
    /**
     * Load extension preset value.
     *
     * @return void
     */
    protected function loadExtensionDefault()
    {
        $this->default                       = $this->getDefaults();
        $this->dirs                          = $this->getDirs();
        $this->files                         = $this->getFiles();
        $this->files[$this->default('logo')] = 'logo.png';
        $this->version                       = $this->default['version'] ?? '1.0.0';
        $this->rootNamespace                 = $this->default['namespace'] ?? null;
        $this->extensionNamespace            = $this->default['namespace'] ?? null;
    }
    
    /**
     * Get the default values.
     *
     * @return array
     */
    protected function getDefaults(): array
    {
        return config('admin.extension.default', []);
    }
    
    /**
     * Gets the extension dirs.
     *
     * @return array
     */
    protected function getDirs(): array
    {
        return config('admin.extension.default.dirs', []);
    }
    
    /**
     * Gets the extension files.
     *
     * @return array
     */
    protected function getFiles(): array
    {
        return config('admin.extension.default.files', []);
    }
    
    /**
     * Retrieve default value or set default value.
     *
     * @param  null  $key
     * @param  null  $value
     *
     * @return array|\ArrayAccess|mixed|void
     */
    protected function default( $key = null, $value = null )
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
     * Make new directory.
     *
     * @param  array|string  $paths
     */
    protected function makeDir( $paths = '' )
    {
        foreach ((array) $paths as $path) {
            $path = $this->extensionPath($path);
            
            $this->filesystem->makeDirectory($path, 0755, true, true);
        }
    }
    
    /**
     * Extension path.
     *
     * @param  string  $path
     *
     * @return string
     */
    protected function extensionPath( $path = '' )
    {
        $path = rtrim($path, DIRECTORY_SEPARATOR);
        
        if (empty($path)) {
            return rtrim($this->basePath, DIRECTORY_SEPARATOR);
        }
        
        return rtrim($this->basePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR);
    }
    
    /**
     * Create package dirs.
     */
    protected function makeDirs()
    {
        $this->makeDir($this->option('theme') ? $this->themeDirs : $this->dirs);
    }
    
    /**
     * Make extension files.
     */
    protected function makeFiles()
    {
        $this->namespace = $this->getRootNameSpace();
        
        $this->className           = $this->getClassName();
        $this->serviceProviderName = $this->getServiceProviderName();
        
        // copy files
        $this->copyFiles();
        
        // make composer.json
        $this->makeFile(
            [
                '{package}'     => $this->package,
                '{alias}'       => $this->makeComposerAlias($this->className),
                '{homepage}'    => $this->makeHomepage('https://github.com/' . $this->package),
                '{namespace}'   => str_replace('\\', '\\\\', $this->namespace) . '\\\\',
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
        
        // Setting
        $this->makeFile(
            [
                '{namespace}' => $this->namespace,
            ],
            $this->stubPath('setting'), 'src/Setting.php'
        );
        
        // Facade
        if ($this->default('facade')) {
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
        
        // Configuration
        if ($this->default('config')) {
            $this->makeFile(
                [ '{config_default}' => $this->default('config_default') ],
                $this->stubPath('config'),
                'config/' . Helper::slug(basename($this->package)) . '.php'
            );
        }
        
        // Extension Class
        $this->makeFile([
            '{namespace}' => $this->namespace,
            '{className}' => $this->className,
        ], $this->stubPath('base_class'), "src/{$this->className}.php");
        
        // ./src/helpers.php
        $this->makeFile([
            '{namespace}'     => $this->namespace,
            '{className}'     => $this->className,
            '{CLASSNAME}'     => Str::upper($this->className),
            '{extensionName}' => $this->getExtensionName(),
        ], self::getStubPath('helpers.stub'), "src/helpers.php");
        
        // ./src/ServiceProvider.php
        $basePackage = Helper::slug(basename($this->package));
        $this->makeFile(
            [
                '{namespace}'     => $this->getNamespace(),
                '{className}'     => $this->getClassName(),
                '{extensionName}' => $this->getExtensionName(),
                '{CLASSNAME}'     => Str::upper($this->getClassName()),
                '{title}'         => Str::title($this->className),
                '{path}'          => $basePackage,
                '{basePackage}'   => $basePackage,
                '{traits}'        => $this->makeUseTraits([
                    'Directories', 'Publishable',
                ]),
                '{importTraits}'  => $this->makeImportTraits([
                    $this->getRootNameSpace() . '\\Concerns\\Directories',
                    $this->getRootNameSpace() . '\\Concerns\\Publishable',
                ]),
                '{property}'      => $this->makeProviderVars(),
                '{registerTheme}' => $this->makeRegisterThemeContent(),
            ],
            self::getStubPath('provider.stub'), "src/{$this->getServiceProviderName()}.php"
        );
        
        // ./src/Concerns/Directories
        $this->makeFile(
            [
                '{namespace}'                    => $this->namespace,
                '{className}'                    => 'Directories',
                '{ServiceProviderWithNamespace}' => $this->getServiceProviderWithNamespace(),
                '{ServiceProviderName}'          => $this->getServiceProviderName(),
            ],
            self::getStubPath('Directories.stub'), "src/Concerns/Directories.php"
        );
        
        // ./src/Concerns/Publishable
        $this->makeFile(
            [
                '{namespace}'                    => $this->namespace,
                '{className}'                    => 'Publishable',
                '{packageName}'                  => Helper::slug(basename($this->package)),
                '{ServiceProviderWithNamespace}' => $this->getServiceProviderWithNamespace(),
                '{ServiceProviderName}'          => $this->getServiceProviderName(),
            ],
            self::getStubPath('Publishable.stub'), "src/Concerns/Publishable.php"
        );
        
        // ./src/Console/Commands/PublishCommand.php
        $this->makeFile(
            [
                '{namespace}'                    => $this->getNamespace('Console\Commands'),
                '{className}'                    => $this->getClassName(),
                '{packageName}'                  => Helper::slug(basename($this->package)),
                '{ServiceProviderWithNamespace}' => $this->getServiceProviderWithNamespace(),
                '{ServiceProviderName}'          => $this->getServiceProviderName(),
            ],
            self::getStubPath('PublishCommand.stub'), "src/Console/Commands/PublishCommand.php"
        );
        
        if (! $this->option('theme')) {
            // make controller
            $this->makeFile(
                replaces: [
                    '{namespace}' => $this->namespace,
                    '{className}' => $this->className,
                    '{name}'      => $this->extensionName,
                ],
                stub    : $this->stubPath('controller'),
                path    : "src/Http/Controllers/{$this->className}Controller.php"
            );
            
            // make index.blade.php
            $this->makeFile(
                replaces: [
                    '{name}' => $this->extensionName,
                ],
                stub    : $this->stubPath('view'),
                path    : 'resources/views/index.blade.php'
            );
            
            // make routes
            $this->makeFile([
                '{namespace}' => $this->namespace,
                '{className}' => $this->className,
                '{path}'      => $basePackage,
            ], $this->stubPath('routes.stub'), 'routes/admin.php');
            
            // ./resources/lang/en
            $this->makeFile(
                replaces: [
                    '{namespace}' => $this->namespace,
                    '{className}' => $this->className,
                ],
                stub    : self::getStubPath('lang.stub'),
                path    : 'resources/lang/en/' . $this->getExtensionName() . '.php'
            );
            
            // ./resources/lang/zh-CN
            $this->makeFile(
                replaces: [
                    '{namespace}' => $this->namespace,
                    '{className}' => $this->className,
                ],
                stub    : self::getStubPath('lang.stub'),
                path    : 'resources/lang/zh_CN/' . $this->getExtensionName() . '.php'
            );
            
            // ./resources/lang/zh-TW
            $this->makeFile(
                replaces: [
                    '{namespace}' => $this->namespace,
                    '{className}' => $this->className,
                ],
                stub    : self::getStubPath('lang.stub'),
                path    : 'resources/lang/zh_TW/' . $this->getExtensionName() . '.php'
            );
        }
    }
    
    /**
     * Get root namespace for this package.
     *
     * @return array|null|string
     */
    protected function getRootNameSpace()
    {
        [ $vendor, $name ] = explode('/', $this->package);
        
        $default = str_replace('-', '', Str::title($vendor) . '\\' . Str::title($name));
        
        if (! $namespace = $this->option('namespace')) {
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
     * Gets the Service Provider name.
     *
     * @return string
     */
    protected function getServiceProviderName(): string
    {
        $this->serviceProviderName = sprintf("%sServiceProvider", $this->getClassName());
        
        return $this->serviceProviderName;
    }
    
    protected function copyFiles()
    {
        $files     = $this->files;
        $formatted = [];
        
        if ($this->option('theme')) {
            Arr::forget($files, [ 'view.stub', 'js.stub' ]);
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
     * Return the stub path.
     *
     * @param  string  $name
     *
     * @return string
     */
    protected function stubPath( string $name )
    {
        $name = str_replace('.stub', '', $name);
        
        return __DIR__ . '/stubs/extension/' . $name . '.stub';
    }
    
    /**
     * Copy files to an extension path.
     *
     * @param  string|array  $from
     * @param  string|null   $to
     */
    protected function copy( $from, $to = null )
    {
        if (is_array($from) && is_null($to)) {
            foreach ($from as $key => $value) {
                $this->copy($key, $value);
            }
            
            return;
        }
        
        if (! file_exists($from)) {
            return;
        }
        
        $to = $this->extensionPath($to);
        
        $this->filesystem->copy($from, $to);
    }
    
    /**
     * Use stub to create files.
     *
     * @param  array   $replaces
     * @param  string  $stub
     * @param  string  $path
     *
     * @return void
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function makeFile( array $replaces, string $stub, string $path )
    {
        $search       = array_keys($replaces);
        $replacements = array_values($replaces);
        $content      = str_replace($search, $replacements, $this->getStub($stub));
        
        $this->putFile($path, $content);
    }
    
    /**
     * Get stub content.
     *
     * @param  string  $path
     *
     * @return null|string
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function getStub( string $path )
    {
        return $this->filesystem->exists($path) ? $this->filesystem->get($path) : null;
        
    }
    
    /**
     * Put contents to file.
     *
     * @param  string  $path
     * @param  string  $content
     */
    protected function putFile( $path, $content )
    {
        $path    = $this->extensionPath($path);
        $dirname = str($path)->dirname();
        
        $this->filesystem->ensureDirectoryExists($dirname);
        $this->filesystem->put($path, $content);
    }
    
    /**
     * @param $name
     *
     * @return bool|string
     */
    protected function makeComposerAlias( $name = null )
    {
        return is_null($name) ?: PHP_EOL . '    "alias": "' . $name . '",';
    }
    
    /**
     * @param $homepage
     *
     * @return bool|string
     */
    protected function makeHomepage( $homepage = null )
    {
        return is_null($homepage) ? '' : PHP_EOL . '    "homepage": "' . $homepage . '",';
    }
    
    /**
     * Get formated keywords.
     *
     * @param $keywords
     *
     * @return string
     */
    protected function getKeywords( $keywords )
    {
        $content = '';
        
        if (is_array($keywords) && ! empty($keywords)) {
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
    
    /**
     * Get formatted authors.
     *
     * @param $authors
     *
     * @return string
     */
    protected function getAuthors( $authors )
    {
        $content = '';
        
        if (is_array($authors) && ! empty($authors)) {
            $entries = count($authors);
            $counter = 1;
            foreach ($authors as $author) {
                $content .= $this->makeAuthor($author);
                
                $content .= $counter === $entries ? '' : ',' . PHP_EOL;
                $counter++;
            }
        }
        
        return $content;
    }
    
    /**
     * Make the author.
     *
     * @param  array  $author
     *
     * @return string
     */
    protected function makeAuthor( array $author )
    {
        $content = '{';
        $content .= '"name": "' . $author['name'] . '", ';
        $content .= '"email": "' . $author["email"] . '"';
        
        if (Arr::exists($author, 'homepage')) {
            $content .= ", \"homepage\": \"" . $author['homepage'] . "\"";
        }
        
        if (Arr::exists($author, 'role')) {
            $content .= ", \"role\": \"" . $author['role'] . "\"";
        }
        
        $content .= '}';
        
        return $content;
    }
    
    /**
     * Gets the extension name.
     *
     * @return string
     */
    protected function getExtensionName()
    {
        return Str::of($this->className)->snake()->value();
    }
    
    /**
     * @param  string|null  $path
     *
     * @return string
     */
    public static function getStubPath( string $path = null ): string
    {
        if ($path) {
            $path = str_split($path)[0] === DIRECTORY_SEPARATOR ? $path : DIRECTORY_SEPARATOR . $path;
        }
        
        return realpath(self::STUB_DIR) . $path;
    }
    
    /**
     * @param $name
     *
     * @return string
     */
    protected function getNamespace( $name = null ): string
    {
        return null === $name ? $this->namespace : $this->namespace . '\\' . ucfirst($name);
    }
    
    /**
     * Make use traits strings.
     *
     * @param  array  $traits
     *
     * @return string
     */
    public function makeUseTraits( array $traits = [] ): string
    {
        $total = count($traits);
        
        if ($total != 0) {
            $str     = '';
            $counter = 0;
            $return  = 0;
            foreach ($traits as $trait) {
                if ($counter === 0) {
                    $str .= '    use ';
                }
                
                ++$counter;
                ++$return;
                
                $str .= $trait;
                $str .= $counter === $total ? ';' : ', ';
                
                if ($return === 4 || $counter === $total) {
                    $str    .= PHP_EOL . '    ';
                    $return = 0;
                }
            }
            
            return $str;
        }
        
        return '';
    }
    
    public function makeImportTraits( array $traits = [] )
    {
        $str = '';
        
        foreach ($traits as $trait) {
            $str .= 'use ' . $trait . ';' . PHP_EOL;
        }
        
        return $str;
    }
    
    /**
     * Return a provider property.
     *
     * @return string
     */
    protected function makeProviderVars()
    {
        $content = '';
        
        if (! $this->option('theme')) {
            $content .= "    /**" . PHP_EOL;
            $content .= "    * Commands to register." . PHP_EOL;
            $content .= "    * " . PHP_EOL;
            $content .= "    * @var string[]" . PHP_EOL;
            $content .= "    */" . PHP_EOL;
            $content .= "    protected \$commands = [" . PHP_EOL;
            $content .= "        Console\\Commands\\PublishCommand::class," . PHP_EOL;
            $content .= "    ];" . PHP_EOL . PHP_EOL;
            
            // Asset
            $content .= "    protected \$js = ['js/index.js'];" . PHP_EOL . PHP_EOL;
            $content .= "    protected \$css = ['css/index.css'];" . PHP_EOL;
        }
        else {
            $content .= "    protected \$type = self::TYPE_THEME;" . PHP_EOL;
        }
        
        return $content;
    }
    
    protected function makeRegisterThemeContent()
    {
        if (! $this->option('theme')) {
            return;
        }
        
        return <<<'TEXT'
            Admin::baseCss($this->formatAssetFiles($this->css));
            TEXT;
    }
    
    protected function getServiceProviderWithNamespace(): string
    {
        $this->serviceProviderWithNamespace = sprintf("%s\%s", $this->getRootNameSpace(), $this->getServiceProviderName());
        
        return $this->serviceProviderWithNamespace;
    }
    
    /**
     * Show extension scaffolding with tree structure.
     */
    protected function showTree()
    {
        $tree = directory($this->extensionPath(), Helper::DIRECTORY_OUTPUT_TREE) ?? '';
        
        $this->info($tree);
    }
    
    protected function makeMenu(): string
    {
        $menu = <<<'TEXT'
            /**
             * @var array[]
             */
            protected $menu = [
                [
                    'title' => 'testing',
                    'uri'   => '#',
                    'icon'  => '',
                ],
            ];
            TEXT;
        
        return (string) $menu;
    }
    
    protected function getPackageName()
    {
        return Helper::slug(basename($this->package));
    }
    
    protected function getMenuUrl()
    {
        return '#';
    }
    
}
