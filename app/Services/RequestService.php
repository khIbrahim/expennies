<?php

namespace App\Services;


use App\Contracts\SessionInterface;
use App\DTO\DatatableQueryParams;
use Psr\Http\Message\ServerRequestInterface;

class RequestService
{

    public function __construct(
      private readonly SessionInterface $session
    ) {}

    public function getReferer(ServerRequestInterface $request) : string
    {
        $referer = $request->getHeader('referer')[0] ?? "";

        if(! $referer){
            return $this->session->get('previousUrl');
        }

        $refererHost = parse_url($referer, PHP_URL_HOST);
        if($refererHost !== $request->getUri()->getHost()){
            return $this->session->get('previousUrl');
        }

        return $referer;
    }

    public function isXhr(ServerRequestInterface $request): bool
    {
        return $request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest';
    }

    public function getDatatableQueryParams(ServerRequestInterface $request): DatatableQueryParams
    {
        $params = $request->getQueryParams();

        return new DatatableQueryParams(
            start: (int) $params['start'],
            length: (int) $params['length'],
            orderBy: (string) $params['columns'][$params['order'][0]['column']]['data'],
            orderDir: (string) $params['order'][0]['dir'],
            searchTerm: (string) $params['search']['value'],
            draw: (int) $params['draw']
        );
    }

}