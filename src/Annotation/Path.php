<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
namespace Hyperf\Apidoc\Annotation;

/**
 * @Annotation
 * @Target({"METHOD"})
 */
class Path extends Param
{
    public $in = 'path';
}
