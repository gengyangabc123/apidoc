<?php

declare(strict_types=1);

namespace Hyperf\Apidoc\Annotation;

use Hyperf\Di\Annotation\AbstractAnnotation;

/**
 * @Annotation
 * @Target({"CLASS"})
 */
class ApiDefinitions extends AbstractAnnotation
{
    /**
     * @var array
     */
    public $definitions;

    public function __construct($value = null)
    {
        $this->bindMainProperty('definitions', $value);
    }
}
