<?php

namespace Swift\Translation;

use Swift\Contracts\Bootstrap;
use Symfony\Component\Translation\Loader\PhpFileLoader;
use Symfony\Component\Translation\Translator;
use Workerman\Worker;

/**
 * Class TranslationProvider
 * @package Swift\Translation
 * @method static string trans(?string $id, array $parameters = [], string $domain = null, string $locale = null)
 * @method static void setLocale(string $locale)
 * @method static string getLocale()
 */
class TranslationProvider implements Bootstrap
{
    /**
     * @var array
     */
    protected static $_translator = [];

    /**
     * @param Worker $worker
     * @return void
     */
    public static function start($worker)
    {
        if (!class_exists('\Symfony\Component\Translation\Translator')) {
            return;
        }

        $config = config('translation', []);
        if (!$translations_path = realpath($config['path'])) {
            return;
        }

        static::$_translator = $translator = new Translator($config['locale']);
        $translator->setFallbackLocales($config['fallback_locale']);

        $classes = [
            'Symfony\Component\Translation\Loader\PhpFileLoader' => [
                'extension' => '.php',
                'format' => 'phpfile'
            ],
            'Symfony\Component\Translation\Loader\PoFileLoader' => [
                'extension' => '.po',
                'format' => 'pofile'
            ]
        ];

        foreach ($classes as $class => $opts)
        {
            $translator->addLoader($opts['format'], new $class);
            foreach (glob($translations_path . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . '*' . $opts['extension']) as $file) {
                $domain = basename($file, $opts['extension']);
                $dir_name = pathinfo($file, PATHINFO_DIRNAME);
                $locale = substr(strrchr($dir_name, DIRECTORY_SEPARATOR), 1);
                if ($domain && $locale) {
                    $translator->addResource($opts['format'], $file, $locale, $domain);
                }
            }
        }
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public static function __callStatic($name, $arguments)
    {
        return static::$_translator->{$name}(... $arguments);
    }
}
