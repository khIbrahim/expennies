<?php
namespace App\Middlewares;

use App\Contracts\SessionInterface;
use App\Exception\ValidationException;
use App\ResponseFormatter;
use App\Services\RequestService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ValidationExceptionMiddleware implements MiddlewareInterface {

    public function __construct(
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly SessionInterface         $session,
        private readonly RequestService           $requestService,
        private readonly ResponseFormatter        $responseFormatter
    ){}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            $response = $handler->handle($request);
        } catch (ValidationException $e){
            $response = $this->responseFactory->createResponse();

            if($this->requestService->isXhr($request)){
                return $this->responseFormatter->asJson($response->withStatus(422), $e->errors);
            }

            $oldData       = $request->getParsedBody();
            $sensitiveData = ['password', 'confirmPassword'];
            $data          = array_diff_key($oldData, array_flip($sensitiveData));

            $this->session->flash('errors', $e->errors);
            $this->session->flash('old', $data);

            $referer = $this->requestService->getReferer($request);

            return $response->withHeader('Location', $referer)->withStatus(302);
        }

        return $response;
    }

}