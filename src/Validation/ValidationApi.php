<?php

declare(strict_types=1);

namespace Hyperf\Apidoc\Validation;

use Hyperf\Apidoc\Annotation\Body;
use Hyperf\Apidoc\Annotation\FormData;
use Hyperf\Apidoc\Annotation\Header;
use Hyperf\Apidoc\Annotation\Query;
use Hyperf\Apidoc\ApiAnnotation;
use Hyperf\Contract\ConfigInterface;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Hyperf\Utils\ApplicationContext;
use Hyperf\Utils\Context;
use Psr\Http\Message\ServerRequestInterface;

class ValidationApi
{
    public $validation;
    public $container;
    public $config;

    public function __construct()
    {
        $this->validation = make(Validation::class);
        $this->container = ApplicationContext::getContainer();
        $this->config = $this->container->get(ConfigInterface::class);
    }

    public function paramObj($in, $value) {
       switch ($in) {
           case 'query':
               return new Query($value);
           case 'formData':
               return new FormData($value);
           case 'header':
               return new Header($value);
           case 'body':
               return new Body($value);
       }
       return null;
    }

    public function globalParams() : array
    {
        $conf = $this->config->get('apidoc.global', []);
        $globalAnno = [];
        foreach ($conf as $in  => $params) {
            $paramsObj = [];
            if (isset($params[0])) {
                foreach ($params as $param) {
                    $paramsObj[] = $this->paramObj($in, $param);
                }
            } else {
                if ($in == 'body') {
                    $globalAnno[] = $this->paramObj($in, [
                        'in' => $in,
                        'rules' => $params
                    ]);
                } else {
                    foreach ($params as $key => $rule) {
                        $paramsObj[] = $this->paramObj($in, [
                            'in' => $in,
                            'key' => $key,
                            'rule' => $rule
                        ]);
                    }
                }
            }
            $globalAnno[]= array_filter($paramsObj);
        }

        return $globalAnno;
    }

    public function validated($controller, $action)
    {
        $controllerInstance = $this->container->get($controller);
        $request = $this->container->get(ServerRequestInterface::class);
        $annotations = array_merge(
            ApiAnnotation::methodMetadata($controller, $action),
            $this->globalParams()
        );

        $header_rules = [];
        $query_rules = [];
        $body_rules = [];
        $form_data_rules = [];
        foreach ($annotations as $annotation) {
            if ($annotation instanceof Header) {
                $header_rules[$annotation->key] = $annotation->rule;
            }
            if ($annotation instanceof Query) {
                $query_rules[$annotation->key] = $annotation->rule;
            }
            if ($annotation instanceof Body) {
                $body_rules = array_merge($body_rules, $annotation->rules);
            }
            if ($annotation instanceof FormData) {
                $form_data_rules[$annotation->key] = $annotation->rule;
            }
        }

        if (! array_filter(compact('header_rules', 'query_rules', 'body_rules', 'form_data_rules'))) {
            return true;
        }

        $config = make(ConfigInterface::class);
        $error_code = $config->get('apidoc.error_code', -1);
        $field_error_code = $config->get('apidoc.field_error_code', 'code');
        $field_error_message = $config->get('apidoc.field_error_message', 'message');

        if ($header_rules) {
            $headers = $request->getHeaders();
            $headers = array_map(function ($item) {
                return $item[0];
            }, $headers);
            $real_headers = [];
            foreach ($headers as $key => $val) {
                $real_headers[implode('-', array_map('ucfirst', explode('-', $key)))] = $val;
            }
            [
                $data,
                $error,
            ] = $this->check($header_rules, $real_headers, $controllerInstance);
            if ($data === null) {
                return [
                    $field_error_code => $error_code,
                    $field_error_message => implode(PHP_EOL, $error),
                ];
            }
        }

        if ($query_rules) {
            [
                $data,
                $error,
            ] = $this->check($query_rules, $request->getQueryParams(), $controllerInstance);
            if ($data === null) {
                return [
                    $field_error_code => $error_code,
                    $field_error_message => implode(PHP_EOL, $error),
                ];
            }
            Context::set(ServerRequestInterface::class, $request->withQueryParams($data));
        }

        if ($body_rules) {
            [
                $data,
                $error,
            ] = $this->check($body_rules, (array) json_decode($request->getBody()->getContents(), true), $controllerInstance);
            if ($data === null) {
                return [
                    $field_error_code => $error_code,
                    $field_error_message => implode(PHP_EOL, $error),
                ];
            }
            Context::set(ServerRequestInterface::class, $request->withBody(new SwooleStream(json_encode($data))));
        }

        if ($form_data_rules) {
            [
                $data,
                $error,
            ] = $this->check($form_data_rules, array_merge($request->getUploadedFiles(),$request->getParsedBody()), $controllerInstance);
            if ($data === null) {
                return [
                    $field_error_code => $error_code,
                    $field_error_message => implode(PHP_EOL, $error),
                ];
            }
            Context::set(ServerRequestInterface::class, $request->withParsedBody($data));
        }

        isset($data) && Context::set('validator.data', $data);

        return true;
    }

    public function check($rules, $data, $controllerInstance)
    {
        [
            $data,
            $error,
        ] = $this->validation->check($rules, $data, $controllerInstance);
        return [$data, $error];
    }
}
