<?php

declare(strict_types=1);
namespace Hyperf\Apidoc;

use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Utils\ApplicationContext;
use Symfony\Component\Console\Input\InputOption;

/**
 * @Command
 */
class DocCreateCommand extends HyperfCommand
{
    protected $name = 'doc:create';

    protected $coroutine = false;

    public function handle()
    {
        if((new ApiCreateProcess())->process()){
            $this->output->text('文档生成成功!');
        } else{
            $this->output->text('文档生成失败!');
        }
    }

}
