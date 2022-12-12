<?php

declare(strict_types=1);

namespace Hyperf\Apidoc;

use Doctrine\Common\Annotations\AnnotationReader;
use Elasticsearch\Endpoints\License\Post;
use Hyperf\Apidoc\Annotation\ApiListFieldClass;
use Hyperf\Apidoc\Annotation\ApiResponse;
use Hyperf\Apidoc\Annotation\Body;
use Hyperf\Apidoc\Annotation\GetApi;
use Hyperf\Apidoc\Annotation\PostApi;
use Hyperf\Di\Annotation\AnnotationCollector;
use Hyperf\Di\ReflectionManager;
use Hyperf\HttpServer\Router\Router;
use Hyperf\Utils\Composer;

class ApiAnnotation
{
    public static function methodMetadata($className, $methodName, $route, $requestMethod)
    {
        $reflectMethod = ReflectionManager::reflectMethod($className, $methodName);
        [$request, $response] = self::argumentsMetadata($reflectMethod);
        $reader = new AnnotationReader();
        $methodAnnotations = $reader->getMethodAnnotations($reflectMethod);
        if(!empty($route) && $requestMethod == "POST") {
            $methodAnnotations[] = new PostApi(['path' => $route]);
        }
        if(!empty($request)) {
            $methodAnnotations[] = new Body(['rules' => $request]);
        }
        if(!empty($response)) {
            $methodAnnotations[] = new ApiResponse(['code'=>200,'description'=>'请求成功','schema' => $response]);
        }

        return $methodAnnotations;
    }

    public static function classMetadata($className)
    {
        return AnnotationCollector::list()[$className]['_c'] ?? [];
    }

    /**
     * @param \ReflectionMethod $reflectMethod
     * @return array[]
     */
    public static function argumentsMetadata($reflectMethod)
    {
        $request = [];
        $response = [];
        if(!empty($reflectMethod)){
            foreach ($reflectMethod->getParameters() as $parameters){
                $typeName = $parameters->getType()->getName();
                if(!empty($typeName)) {
                    if (stripos($typeName, 'Request')) {
                        self::argumentAnalysis($typeName, $request);
                    }
                    if (stripos($typeName, 'Response')) {
                        self::argumentAnalysis($typeName, $response);
                    }
                }
            }
        }
        return array($request ,$response);
    }

    public static function argumentAnalysis($className, &$argument){
        $annotationCollector = AnnotationCollector::getPropertiesByAnnotation(ApiListFieldClass::class);
        $class = new \ReflectionClass($className);
        $propertyAnnotation = self::getPropertyAnnotation($className);
        foreach ($class->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            $docComment = isset($propertyAnnotation[$property->getName()])?$propertyAnnotation[$property->getName()]:'';
            $keyString = $property->getName().'|'.($property->getDocComment()?:$docComment);
            $argument[$keyString] = "";
            foreach ($annotationCollector as $propertyInfo){
                if(isset($propertyInfo['annotation']->className) && class_exists($propertyInfo['annotation']->className)
                    && $propertyInfo['class'] == $className && $propertyInfo['property'] == $property->getName()){
                    $argument[$keyString] = [];
                    self::argumentAnalysis($propertyInfo['annotation']->className, $argument[$keyString][]);
                }
            }
        }
    }

    public static function getPropertyAnnotation($className){
        $file = Composer::getLoader()->findFile($className);
        $fileCode = file_get_contents($file);
        $codeArray = explode("\n", $fileCode);
        $codeAndAnnotation = [];
        foreach ($codeArray as $code){
            if(strpos($code, "//") && strpos($code, "public") && strpos($code, ";")){
                $codeLineInfo = explode("//", $code);
                $key = $codeLineInfo[0];
                $key = preg_replace(['/=(.*);/','/public/','/,/','/\$/','/ /','/;/',"/'/",'/"/','/=/','/array\(\)/'],'',$key);
                $codeAndAnnotation[$key] = trim($codeLineInfo[count($codeLineInfo)-1]);
            }
        }
        return $codeAndAnnotation;
    }
}
