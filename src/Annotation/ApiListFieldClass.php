<?php
declare(strict_types=1);
namespace Hyperf\Apidoc\Annotation;

use Hyperf\Di\Annotation\AbstractAnnotation;
use Hyperf\Di\Annotation\Inject;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class ApiListFieldClass extends AbstractAnnotation
{
    public $className;
}
