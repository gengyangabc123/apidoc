## ApiDoc 
框架 swagger 文档生成组件

根据注解和注释自动生成Swagger文档, 让接口文档维护更省心.

整体实现思路：循环路由获取controller和方法，反射解析方法获取request和response参数，反射参数类型获取参数属性和注释对应当前路由生成接口文档

## 安装

```
composer require basetools/apidoc
```
## 使用

#### 1. 发布配置文件

```bash
php bin/hyperf.php vendor:publish basetools/apidoc

```
配置文件发布后生成 `config/autoload/apidoc.php`

```php
<?php

return [
    // enable false 将不会生成 swagger 文件
    'enable' => env('APP_ENV') !== 'production',
    // swagger 配置的输出文件
    // 当你有多个 http server 时, 可以在输出文件的名称中增加 {server} 字面变量
    // 比如 /public/swagger/swagger_{server}.json
    'output_file' => BASE_PATH . '/public/swagger/swagger.json',
    // 自定义验证器错误码、错误描述字段
    'error_code' => 400,
    'http_status_code' => 400,
    'field_error_code' => 'code',
    'field_error_message' => 'message',
    'exception_enable' => false,
    // swagger 的基础配置
    'swagger' => [
        'swagger' => '2.0',
        'info' => [
            'description' => 'hyperf swagger api desc',
            'version' => '1.0.0',
            'title' => 'HYPERF API DOC',
        ],
        'host' => '0.0.0.0',
        'schemes' => ['http'],
    ],
    'templates' => [
    // {template} 字面变量  替换 schema 内容(目前还在开发中......)
    // // 默认 成功 返回
    // 'success' => [
    //     "code|code"    => '0',
    //     "result"  => '{template}',
    //     "message|message" => 'Success',
    // ],
    // // 分页
    // 'page' => [
    //     "code|code"    => '0',
    //     "result"  => [
    //         'pageSize' => 10,
    //         'total' => 1,
    //         'totalPage' => 1,
    //         'list' => '{template}'
    //     ],
    //     "message|message" => 'Success',
    //],
    ],
    // golbal 节点 为全局性的 参数配置
    // 跟注解相同, 支持 header, path, query, body, formData
    // 子项为具体定义
    // 模式一: [ key => rule ]
    // 模式二: [ [key, rule, defautl, description] ]
    'global' => [
        // 'header' => [
        //     "x-token|验签" => "required|cb_token"
        // ],
        // 'query' => [
        //     [
        //         'key' => 'xx|cc',
        //         'rule' => 'required',
        //         'default' => 'abc',
        //         'description' => 'description'
        //     ]
        // ]
    ]
];
```

## swagger文档生成
执行命令
```
 php bin/hyperf.php doc:create
```

## 支持的注解 

#### Api类型
`GetApi`, `PostApi`

### 参数类型
`Header`, `Query`, `Body`, `FormData`

### 列表指定包含对象
`ApiListFieldClass`

### 其他
`ApiController`, `ApiResponse`, `ApiVersion`, `ApiServer`, `ApiDefinitions`, `ApiDefinition`

```php
/**
 * @ApiVersion(version="v1")
 * @ApiServer(name="http")
 */
class UserController {} 
```

`ApiServer` 当你在 `config/autoload.php/server.php servers` 中配置了多个 `http` 服务时, 如果想不同服务生成不同的`swagger.json` 可以在控制器中增加此注解.

`ApiVersion` 当你的统一个接口存在不同版本时, 可以使用此注解, 路由注册时会为每个木有增加版本号, 如上方代码注册的实际路由为 `/v1/user/***`

`ApiDefinition` 定义一个 `Definition`，用于Response的复用。 *swagger* 的difinition是以引用的方式来嵌套的，如果需要嵌套另外一个(值为object类型就需要嵌套了)，可以指定具体 `properties` 中的 `$ref` 属性

`ApiDefinitions` 定义一个组`Definition`

`ApiResponse` 响应体的`schema`支持为key设置简介. `$ref` 属性可以引用 `ApiDefinition` 定义好的结构(该属性优先级最高)
```php
@ApiResponse(code="0", description="删除成功", schema={"id|这里是ID":1})
@ApiResponse(code="0", description="删除成功", schema={"$ref": "ExampleResponse"})
```

具体使用方式参见下方样例

## Controller类注解样例

```php
<?php
declare(strict_types=1);
namespace App\Controller;

use Hyperf\Apidoc\Annotation\ApiController;
use Hyperf\Apidoc\Annotation\ApiResponse;
use Hyperf\Apidoc\Annotation\ApiVersion;
use Hyperf\Apidoc\Annotation\Body;
use Hyperf\Apidoc\Annotation\DeleteApi;
use Hyperf\Apidoc\Annotation\FormData;
use Hyperf\Apidoc\Annotation\GetApi;
use Hyperf\Apidoc\Annotation\Header;
use Hyperf\Apidoc\Annotation\PostApi;
use Hyperf\Apidoc\Annotation\Query;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\Utils\ApplicationContext;

/**
 * @ApiVersion(version="v1")
 * @ApiController(tag="demo管理", description="demo的新增/修改/删除接口")
 * @ApiDefinitions({
 *  @ApiDefinition(name="DemoOkResponse", properties={
 *     "code|响应码": 200,
 *     "msg|响应信息": "ok",
 *     "data|响应数据": {"$ref": "DemoInfoData"}
 *  }),
 *  @ApiDefinition(name="DemoInfoData", properties={
 *     "userInfo|用户数据": {"$ref": "DemoInfoDetail"}
 *  }),
 *  @ApiDefinition(name="DemoInfoDetail", properties={
 *     "id|用户ID": 1,
 *     "mobile|用户手机号": { "default": "13545321231", "type": "string" },
 *     "nickname|用户昵称": "nickname",
 *     "avatar": { "default": "avatar", "type": "string", "description": "用户头像" },
 *  })
 * })
 */
class DemoController extends AuthController
{

    /**
     * @PostApi(path="/demo", description="添加一个用户")
     * @Header(key="token|接口访问凭证", rule="required")
     * @ApiResponse(code="-1", description="参数错误", template="page")
     * @ApiResponse(code="0", description="请求成功", schema={"id":"1"})
     */
    public function add()
    {
        return [
            'code'   => 0,
            'id'     => 1,
            'params' => $this->request->post(),
        ];
    }

    // 自定义的校验方法 rule 中 cb_*** 方式调用
    public function checkName($attribute, $value)
    {
        if ($value === 'a') {
            return "拒绝添加 " . $value;
        }

        return true;
    }

    /**
     * 请注意 body 类型 rules 为数组类型
     * @ApiResponse(code="-1", description="参数错误")
     * @ApiResponse(code="0", description="删除成功", schema={"id":1})
     */
    public function delete()
    {
        $body = $this->request->getBody()->getContents();
        return [
            'code'  => 0,
            'query' => $this->request->getQueryParams(),
            'body'  => json_decode($body, true),
        ];
    }
}
```

## request或response类样例

```php
<?php
class AccountFansTrendRequest extends BaseHttpRequest
{
    public $accountUid = ''; //账户id
    public $channel = ""; //资源渠道
}

class BilibiliMediaListResponse extends BaseHttpResponse
{
    #[ApiListFieldClass(className:'App\Rest\Response\BilibiliMedia')]
    public $data = []; //资源数据
    public $page = 1; //当前页码
    public $limit =  20; //每页条数
    public $total = 0; //资源总数
}
```

## 支持Swagger UI启动

本组件提供了两种方式来启用`SwaggerUI`
, 当`config/autoload/apidoc.php enable = true` 时

#### 方式一 

系统启动时, `swagger.json` 会自动输出到配置文件中定义的 `output_file`中, 此时我们到`swagger ui`的前端文件结合`nginx`启动web服务

#### 方式二

也可以使用组件提供的快捷命令, 快速启动一个 `swagger ui`.

```bash
php bin/hyperf.php apidoc:ui

php bin/hyperf.php apidoc:ui --port 8888
```
