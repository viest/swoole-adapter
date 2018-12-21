<?php

namespace Vtiful\Framework;

use \Exception;
use \Vtiful\Framework\Lumen\Framework as Lumen;

class Factory
{
    /**
     * Factory
     *
     * @param string $framework
     * @param string $entrancePath
     *
     * @throws Exception
     *
     * @return mixed
     */
    public static function init(string $framework, string $entrancePath, array $config)
    {
        switch ($framework) {
            case 'lumen':
                return Lumen::initialization($entrancePath, $config);
            default:
                throw new Exception('Init Frameword Failure');
        }
    }
}