<?php

declare(strict_types=1);

namespace Hyperf\Apidoc\Annotation;

/**
 * @Annotation
 * @Target({"METHOD", "CLASS"})
 */
class Query extends Param
{
    public $in = 'query';
}
