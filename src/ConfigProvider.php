<?php

declare(strict_types=1);
namespace Hyperf\Apidoc;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'commands' => [
                UICommand::class,
                DocCreateCommand::class,
            ],
            'dependencies' => [
                \Hyperf\HttpServer\Router\DispatcherFactory::class => DispatcherFactory::class,
            ],
            'listeners' => [
                //BootAppConfListener::class,
            ],
            'annotations' => [
                'scan' => [
                    'paths' => [
                        __DIR__,
                    ],
                ],
            ],
            'publish' => [
                [
                    'id' => 'config',
                    'description' => 'The config for apidoc.',
                    'source' => __DIR__ . '/../publish/apidoc.php',
                    'destination' => BASE_PATH . '/config/autoload/apidoc.php',
                ],
            ],
        ];
    }
}
