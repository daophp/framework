<?php

namespace Swift\View;

use Jenssegers\Blade\Blade as BladeView;
use Swift\Contracts\View as ViewContract;

/**
 * Class Blade
 * @package Swift\View
 */
class Blade implements ViewContract
{
    /**
     * @var array
     */
    protected static $_vars = [];

    /**
     * @param $name
     * @param null $value
     */
    public static function assign($name, $value = null)
    {
        static::$_vars += is_array($name) ? $name : [$name => $value];
    }

    /**
     * @param $template
     * @param $vars
     * @param string $app
     * @return mixed
     */
    public static function render($template, $vars, $app = null)
    {
        static $views = [];

        $app = is_null($app) ? request()->app : $app;

        if (!isset($views[$app])) {
            $viewPath = $app === '' ? config('view.paths') : app_path($app . '/Views');
            $cachePath = runtime_path('views' . ($app ? DIRECTORY_SEPARATOR . $app : ''));
            if (!is_dir($cachePath)) {
                mkdir($cachePath, 0755, true);
            }
            $views[$app] = isset($views[$app]) ? $views[$app] : new BladeView($viewPath, $cachePath);
        }

        $vars += static::$_vars;
        $content = $views[$app]->render($template, $vars);
        static::$_vars = [];
        return $content;
    }
}
