GoAopBridgeForLumen
==============
This is code reference from goaop/framework and goaop/goaop-laravel-bridge

The GoAopLumen adds support for Aspect-Oriented Programming via Go! AOP Framework for Lumen 5.4.* applications.

Overview
--------

Aspect-Oriented Paradigm allows to extend the standard Object-Oriented Paradigm with special instruments for effective solving of cross-cutting concerns in your application. This code is typically present everywhere in your application (for example, logging, caching, monitoring, etc) and there is no easy way to fix this without AOP.

AOP defines new instruments for developers, they are:

 * Joinpoint - place in your code that can be used for interception, for example, execution of single public method or accessing of single object property.
 * Pointcut is a list of joinpoints defined with a special regexp-like expression for your source code, for example, all public and protected methods in the concrete class or namespace.
 * Advice is an additional callback that will be called before, after or around concrete joinpoint. For PHP each advice is represented as a `\Closure` instance, wrapped into the interceptor object.
 * Aspect is a special class that combines pointcuts and advices together, each pointcut is defined as an annotation and each advice is a method inside this aspect.
 
 You can read more about AOP in different sources, there are good articles for Java language and they can be applied for PHP too, because it's general paradigm. Alternatively, you can watch a [nice presentation about AOP in Laravel](http://slides.com/chrisflynn-1/aspect-oriented-architecture-in-laravel)

Installation
------------

GoAopBridge can be easily installed with composer. Just ask a composer to download the bundle with dependencies by running the command:

```bash
$ composer require thieule/goaop-lumen
```

Add the `Go\Lumen\GoAopBridge\GoAopServiceProvider` to your bootstrap/app.php `providers` array:
```php
// bootstrap/app.php

    $app->register(Go\Lumen\GoAopBridge\GoAopServiceProvider::class);
```
Make sure that this service provider is the **first item** in this list. This is required for the AOP engine to work correctly.

Configuration
-------------

The default configuration in the `config/go_aop.php` file. Copy this file to your own config directory to modify the values.

Configuration can be used for additional tuning of AOP kernel and source code whitelistsing/blacklisting.
```php
// config/go_aop.php

return [
    /*
     |--------------------------------------------------------------------------
     | AOP Debug Mode
     |--------------------------------------------------------------------------
     |
     | When AOP is in debug mode, then breakpoints in the original source code
     | will work. Also engine will refresh cache files if the original files were
     | changed.
     | For production mode, no extra filemtime checks and better integration with opcache
     |
     */
    'debug' => env('APP_DEBUG', false),

    /*
     |--------------------------------------------------------------------------
     | Application root directory
     |--------------------------------------------------------------------------
     |
     | AOP will be applied only to the files in this directory, change it to app_path()
     | if needed
     */
    'appDir' => base_path(),

    /*
     |--------------------------------------------------------------------------
     | AOP cache directory
     |--------------------------------------------------------------------------
     |
     | AOP engine will put all transformed files and caches in that directory
     */
    'cacheDir' => storage_path('app/aspect'),

    /*
     |--------------------------------------------------------------------------
     | Cache file mode
     |--------------------------------------------------------------------------
     |
     | If configured then will be used as cache file mode for chmod
     */
    'cacheFileMode' => null,

    /*
     |--------------------------------------------------------------------------
     | Controls miscellaneous features of AOP engine
     |--------------------------------------------------------------------------
     |
     | See \Go\Aop\Features enumeration for bit mask
     */
    'features' => 0,

    /*
     |--------------------------------------------------------------------------
     | White list of directories
     |--------------------------------------------------------------------------
     |
     | AOP will check this list to apply an AOP to selected directories only,
     | leave it empty if you want AOP to be applied to all files in the appDir
     */
    'includePaths' => [
        app_path()
    ],

    /*
     |--------------------------------------------------------------------------
     | Black list of directories
     |--------------------------------------------------------------------------
     |
     | AOP will check this list to disable AOP for selected directories
     */
    'excludePaths' => [],

    /*
     |--------------------------------------------------------------------------
     | AOP container class
     |--------------------------------------------------------------------------
     |
     | This option can be useful for extension and fine-tuning of services
     */
    'containerClass' => GoAspectContainer::class,
]
```

Defining new aspects
--------------------

Aspects are services in the Laravel application and loaded into the AOP container with the help of service provider that collects all services tagged with `goaop.aspect` tag in the container. Here is an example how to implement a logging aspect that will log information about public method invocations in the app/ directory.


Definition of aspect class with pointuct and logging advice
```php
<?php

namespace App\Aspect;

use Go\Aop\Aspect;
use Go\Aop\Intercept\MethodInvocation;
use Go\Lang\Annotation\Before;
use Psr\Log\LoggerInterface;

/**
 * Application logging aspect
 */
class LoggingAspect implements Aspect
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Writes a log info before method execution
     *
     * @param MethodInvocation $invocation
     * @Before("execution(public **->*(*))")
     */
    public function beforeMethod(MethodInvocation $invocation)
    {
        $this->logger->info($invocation, $invocation->getArguments());
    }
}
```

To register all application aspects in the container, create a service provider (or use an existing one)
```bash
./artisan make:provider AopServiceProvider
```

Inside `register()` method for this service provider, add declaration of aspect and tag it with `goaop.aspect` tag:

```php

namespace App\Providers;

use App\Aspect\LoggingAspect;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Psr\Log\LoggerInterface;

class AopServiceProvider extends ServiceProvider
{

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(LoggingAspect::class, function (Application $app) {
            return new LoggingAspect($app->make(LoggerInterface::class));
        });

        $this->app->tag([LoggingAspect::class], 'goaop.aspect');
    }
```

Don't forget to add this service provider into your `config/app.php` service providers list!

License
-------

This bridge is under the MIT license. See the complete LICENSE in the root directory
