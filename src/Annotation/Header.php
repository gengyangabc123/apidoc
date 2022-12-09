<?php

declare(strict_types=1);

namespace Hyperf\Apidoc\Annotation;

/**
 * @Annotation
 * @Target({"METHOD", "CLASS"})
 */
class Header extends Param
{
    public $in = 'header';
}
