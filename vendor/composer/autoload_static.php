<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitd9c6377d74637611be1596553dbfac95
{
    public static $prefixLengthsPsr4 = array (
        'C' => 
        array (
            'Carbon_Fields\\' => 14,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Carbon_Fields\\' => 
        array (
            0 => __DIR__ . '/..' . '/htmlburger/carbon-fields/core',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitd9c6377d74637611be1596553dbfac95::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitd9c6377d74637611be1596553dbfac95::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInitd9c6377d74637611be1596553dbfac95::$classMap;

        }, null, ClassLoader::class);
    }
}
