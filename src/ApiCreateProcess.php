<?php

declare(strict_types=1);

namespace Hyperf\Apidoc;

use Hyperf\Apidoc\Swagger\SwaggerJson;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Framework\Event\BootApplication;
use Hyperf\HttpServer\Router\DispatcherFactory;
use Hyperf\HttpServer\Router\Handler;
use Hyperf\Logger\LoggerFactory;
use Hyperf\Utils\ApplicationContext;
use Hyperf\Utils\Str;

class ApiCreateProcess
{
    public function process()
    {
        $container = ApplicationContext::getContainer();
        $logger = $container->get(LoggerFactory::class)->get('apidoc');
        $config = $container->get(ConfigInterface::class);
        if (! $config->get('apidoc.enable')) {
            $logger->debug('apidoc not enable');
            return;
        }
        $output = $config->get('apidoc.output_file');
        if (! $output) {
            $logger->error('/config/autoload/apidoc.php need set output_file');
            return;
        }
        $servers = $config->get('server.servers');
        if (count($servers) > 1 && ! Str::contains($output, '{server}')) {
            $logger->warning('You have multiple serve, but your apidoc.output_file not contains {server} var');
        }
        foreach ($servers as $server) {
            if ($server['type'] != \Hyperf\Server\Server::SERVER_HTTP) {
                continue;
            }
            $router = $container->get(DispatcherFactory::class)->getRouter($server['name']);
            $data = $router->getData();
            $swagger = new SwaggerJson($server['name']);

            $ignore = $config->get('apidoc.ignore', function ($controller, $action) {
                return false;
            });

            foreach ($data as $v) {
                foreach ($v as $requestMethod => $v1) {
                    foreach ($v1 as $itemKey => $item) {
                        if ($item instanceof Handler && !($item->callback instanceof \Closure)) {
                            if (!empty($item->route) && $item->route != '/') {
                                [$controller, $action] = $this->prepareHandler($item->callback);
                                (!$ignore($controller, $action)) && $swagger->addPath($controller, $action, $item->route, $requestMethod);
                            }
                        }
                    }
                }
            }
            //});

            if($swagger->save()){
                return true;
            } else{
                return false;
            }
        }
    }

    protected function prepareHandler($handler): array
    {
        if (is_string($handler)) {
            if (strpos($handler, '@') !== false) {
                return explode('@', $handler);
            }
            return explode('::', $handler);
        }
        if (is_array($handler) && isset($handler[0], $handler[1])) {
            return $handler;
        }
        throw new \RuntimeException('Handler not exist.');
    }
}
