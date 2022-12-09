<?php

declare(strict_types=1);

namespace Hyperf\Apidoc\Annotation;

/**
 * @Annotation
 * @Target({"METHOD"})
 */
class FormData extends Param
{
    public $in = 'formData';
}
