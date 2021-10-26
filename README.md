# sebk/small-swoft-auth

Jwt auth for Swoft based on small-orm

Include a controller superclass to simple implement token support in your controllers and link with voters to manage user rigths

## Install

Create your Swoft project : http://swoft.io/docs/2.x/en/quick-start/install.html

Install dependencies
```
composer require sebk/small-orm-core
composer require sebk/small-orm-forms
composer require sebk/swoft-voter
```

Require Swoft Voter package (https://github.com/sebk69/small-swoft-auth) :
```
composer require sebk/small-swoft-auth
```

## Documentation

### Parameter

In base.php, register AuthManagerService : 
```
return [
...
    \Swoft\Auth\Contract\AuthManagerInterface::class => [
        'class' => \Sebk\SmallSwoftAuth\Service\AuthManagerService::class
    ],
...
];
```

In app.php, register app.user according to your app to tell AuthManager to request you're app user:
```
return [
...
    'user' => [
        'dao' => ['GenericBundle', 'User'],
        'accountField' => 'login',
        'identityField' => 'id',
    ],
...
];
```

### Implement UserModelInterface

You're application user model must implement UserModelInterface. (In this example, password stored via md5 hash for simplicity. Don't use md5 hash, prefere SHA-256 hash or more for security reasons)  :
```
use Sebk\SmallSwoftAuth\Interfaces\UserModelInterface;

class User extends Model implements UserModelInterface
{

    /**
     * Check user password
     * @param string $password
     * @return bool
     */
    public function checkPassword(string $password)
    {
        return $this->getPassword() == md5($password);
    }

}
```

### Implement your voters

See https://github.com/sebk69/small-voter for config and implementation

### Implement login



### Implement your controllers

To protect your controller :
* use AuthMiddleware of swoft/auth
* your controller must extends TokenSecuredController abstract class

Additionnaly, you can protect a route via specific rules of your app by using voter on your controller object using denyAccessUnlessGranted method :
```
$this->denyAccessUnlessGranted(VoterInterface::ATTRIBUTE_READ, $this);
```

Here is a controller example :
```
<?php declare(strict_types=1);

namespace App\Http\Controller;

use Sebk\SmallOrmSwoft\Traits\Injection\DaoFactory;
use Sebk\SmallSwoftAuth\Controller\TokenSecuredController;
use Sebk\SwoftVoter\VoterManager\VoterInterface;

use App\Model\OrderBundle\Dao\Customer;

use Swoft\Http\Message\Response;
use Swoft\Http\Server\Annotation\Mapping\Controller;
use Swoft\Http\Server\Annotation\Mapping\RequestMapping;
use Swoft\Http\Server\Annotation\Mapping\RequestMethod;
use Swoft\Http\Server\Annotation\Mapping\Middleware;
use Swoft\Http\Server\Annotation\Mapping\Middlewares;

use Swoft\Http\Message\Request;

use Swoft\Auth\Middleware\AuthMiddleware;


/**
 * Class CustomerController
 *
 * @since 2.0
 *
 * @Controller("api/customers")
 *
 * @Middlewares ({AuthMiddleware::class})
 * @Middleware (AuthMiddleware::class)
 */
class CustomerController extends TokenSecuredController
{
    use DaoFactory;

    /**
     * @RequestMapping("page/{page}/pageSize/{pageSize}", method={RequestMethod::GET})
     * @param int $page
     * @param int $pageSize
     * @param Request $request
     * @return Response
     * @throws \Sebk\SmallOrmCore\QueryBuilder\BracketException
     * @throws \Sebk\SmallOrmCore\QueryBuilder\QueryBuilderException
     */
    public function getCustomerList(int $page, int $pageSize, Request $request)
    {
        // Check user rigths
        $this->denyAccessUnlessGranted(VoterInterface::ATTRIBUTE_READ, $this);

        /** @var Customer $daoCustomer */
        $daoCustomer = $this->daoFactory->get("CommandeBundle", "Customer");
        $customers = $daoCustomer->list($request->getQueryParams(), $page, $pageSize);

        return JsonResponse($customers);
    }

}
```

### Usage : login request

Here is a AuthController implementing a login action :
```
<?php

namespace App\Http\Controller;

use Sebk\SmallSwoftAuth\Traits\Injection\AuthManagerService;

use Swoft\Auth\Exception\AuthException;
use Swoft\Http\Message\ContentType;
use Swoft\Http\Message\Request;
use Swoft\Http\Message\Response;
use Swoft\Http\Server\Annotation\Mapping\Controller;
use Swoft\Http\Server\Annotation\Mapping\RequestMapping;
use Swoft\Http\Server\Annotation\Mapping\RequestMethod;

use Swoole\Http\Status;

/**
 *
 * @since 2.0
 *
 * @Controller ("api")
 */
class AuthController
{
    use AuthManagerService;

    const LOGIN_FAILED_MESSAGE = 'Login failed !';

    /**
     * @RequestMapping (route="login_check", method={RequestMethod::POST})
     * @param Request $request
     * @return Response
     */
    public function login(Request $request): Response {
        // Check request format
        if (!in_array(ContentType::JSON, $request->getHeader(ContentType::KEY))) {
            return JsonResponse(static::LOGIN_FAILED_MESSAGE)
                ->withStatus(Status::BAD_REQUEST)
            ;
        }

        // Login & sign token
        try {
            $session = $this->authManager->auth($request->getParsedBody());
        } catch (AuthException $e) {
            return JsonResponse(static::LOGIN_FAILED_MESSAGE)
                ->withStatus(Status::UNAUTHORIZED)
            ;
        }

        $tokenData = [
            'token' => $session->getToken(),
            'expired_at' => $session->getExpirationTime()
        ];

        return JsonResponse($tokenData);
    }

}
```

To test it, use your favorit rest app on :
```
POST http://localhost/api/login_check
headers :
Content-Type : application/json
body :
{"account":"myLogin","password":"myPassword"}
```

It will respond you something like :
```
{
   "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJTZWJrXFxTbWFsbFN3b2Z0QXV0aFxcU2VydmljZVxcQXV0aExvZ2ljIiwic3ViIjoiODciLCJpYXQiOjE2MzQ3MzY2MzIsImV4cCI6MTYzNDgyMzAzMiwiZGF0YSI6eyJ1c2VyIjp7ImlkVXRpbGlzYXRldXIiOjg3LCJpZEdyb3VwIjoxMiwibm9tVXRpbGlzYXRldXIiOiJLVVMiLCJwcmVub21VdGlsaXNhdGV1ciI6IlNcdTAwZTliYXN0aWVuIiwibG9naW5VdGlsaXNhdGV1ciI6IktTIiwicGFzc3dvcmRVdGlsaXNhdGV1ciI6IjVhZWRmN2M4OWNjMzFlZjRmMzQ1ZWViY2U0YTNjZjkxIiwiaWRUeXBlQ29tbWFuZGVVdGlsaXNhdGV1ciI6MSwiZGF0ZUNvbm5leGlvblV0aWxpc2F0ZXVyIjoiMjAyMS0wOC0yMyAxNjoxMToxOCIsImlkRW50cmVwb3RVdGlsaXNhdGV1ciI6MSwiZW1haWxVdGlsaXNhdGV1ciI6Imsuc2ViYXN0aWVuQGxhLWJlY2FuZXJpZS5jb20iLCJ1dGlsaXNhdGV1ckdyb3VwIjpudWxsLCJ1dGlsaXNhdGV1cnNGb25jdGlvbm5hbGl0ZXMiOltdLCJ1dGlsaXNhdGV1cnNSb2xlcyI6W10sImNvdWxldXIiOiIjMzBmY2NjIiwiY291bGV1clN0eWxlIjoiYmFja2dyb3VuZC1jb2xvcjogIzMwZmNjYzsgY29sb3I6d2hpdGU7IiwiZnJvbURiIjp0cnVlfX19.uHcBGGQcc4hhSwcqdBFyci31DI0yeGMq4teD1zURmIE",
   "expired_at": 1634823032
}
```

Or a 401 response if wrong login or password

Now, to access protected route, use Authorization header with the token of login response.

For our customer list route, use :
```
GET http://localhost/api/customers/page/1/pageSize/10
headers :
Authorization : Bearer eyJpc3MiOiJTZWJrXFxTbWFsbFN3b2Z0QXV0aFxcU2VydmljZVxcQXV0aExvZ2ljIiwic3ViIjoiODciLCJpYXQiOjE2MzQ3MzA2NDgsImV4cCI6MTYzNDgxNzA0OCwiZGF0YSI6eyJ1c2VyIjp7ImlkVXRpbGlzYXRldXIiOjg3LCJpZEdyb3VwIjoxMiwibm9tVXRpbGlzYXRldXIiOiJLVVMiLCJwcmVub21VdGlsaXNhdGV1ciI6IlNcdTAwZTliYXN0aWVuIiwibG9naW5VdGlsaXNhdGV1ciI6IktTIiwicGFzc3dvcmRVdGlsaXNhdGV1ciI6IjVhZWRmN2M4OWNjMzFlZjRmMzQ1ZWViY2U0YTNjZjkxIiwiaWRUeXBlQ29tbWFuZGVVdGlsaXNhdGV1ciI6MSwiZGF0ZUNvbm5leGlvblV0aWxpc2F0ZXVyIjoiMjAyMS0wOC0yMyAxNjoxMToxOCIsImlkRW50cmVwb3RVdGlsaXNhdGV1ciI6MSwiZW1haWxVdGlsaXNhdGV1ciI6Imsuc2ViYXN0aWVuQGxhLWJlY2FuZXJpZS5jb20iLCJ1dGlsaXNhdGV1ckdyb3VwIjpudWxsLCJ1dGlsaXNhdGV1cnNGb25jdGlvbm5hbGl0ZXMiOltdLCJ1dGlsaXNhdGV1cnNSb2xlcyI6W10sImNvdWxldXIiOiIjMzBmY2NjIiwiY291bGV1clN0eWxlIjoiYmFja2dyb3VuZC1jb2xvcjogIzMwZmNjYzsgY29sb3I6d2hpdGU7IiwiZnJvbURiIjp0cnVlfX19.dTW8L2RyNv9OnhXHu96UCEKL3UpHJj3mXLJdf_LD-yI
```

If success, the server will return a 200 status code and if token is wrong or expired, it wil return a 401 status code.
