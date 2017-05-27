<?php
/*
 * Go! AOP framework
 *
 * @copyright Copyright 2016, Lisachenko Alexander <lisachenko.it@gmail.com>
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Go\Lumen\GoAopBridge;

use Go\Core\AspectContainer;
use Go\Core\AspectKernel;
use Go\Lumen\GoAopBridge\Kernel\AspectLaravelKernel;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

/**
 * Service provider for registration of Go! AOP framework
 */
class GoAopServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        /** @var AspectContainer $aspectContainer */
        $aspectContainer = $this->app->make(AspectContainer::class);

        // Let's collect all aspects and just register them in the container
        $aspects = $this->app->tagged('goaop.aspect');
        foreach ($aspects as $aspect) {
            $aspectContainer->registerAspect($aspect);
        }
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->configure('go_aop');

        $this->app->singleton(AspectKernel::class, function () {
            $aspectKernel = AspectLaravelKernel::getInstance();
            $aspectKernel->init(config('go_aop'));

            return $aspectKernel;
        });

        $this->app->singleton(AspectContainer::class, function (Application $app) {
            /** @var AspectKernel $kernel */
            $kernel = $app->make(AspectKernel::class);

            return $kernel->getContainer();
        });
    }

    /**
     * @inheritDoc
     */
    public function provides()
    {
        return [AspectKernel::class, AspectContainer::class];
    }


}
