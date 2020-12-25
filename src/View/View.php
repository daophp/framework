<?php

namespace Swift\View;

/**
 * Class View
 * @package Swift\View
 */
class View
{
    /**
     * @param $name
     * @param $value
     * @return mixed
     */
    public static function assign($name, $value = null)
    {
        static $handler;
        $handler = $handler ? : config('view.handler');
        $handler::assign($name, $value);
    }
}
