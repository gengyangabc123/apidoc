<?php

declare(strict_types=1);

namespace Hyperf\Apidoc\Annotation;

use Hyperf\Di\Annotation\AbstractAnnotation;

/**
 * @Annotation
 * @Target({"CLASS"})
 */
class ApiServer extends AbstractAnnotation
{
    public $name;
}
