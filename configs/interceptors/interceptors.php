<?php

use App\Services\CategoryService;
use Psr\SimpleCache\CacheInterface;
use Ray\Aop\Aspect;
use App\Attributes\FromCache;
use App\Interceptors\CacheInterceptor;
use App\Services\TransactionsService;
use Ray\Aop\Matcher;

return function (Aspect $aspect, CacheInterface $cache) {
    $matcher = new Matcher();

    $aspect->bind(
        $matcher->logicalOr(
            $matcher->subclassesOf(TransactionsService::class),
            $matcher->subclassesOf(CategoryService::class)
        ),
        $matcher->annotatedWith(FromCache::class),
        [new CacheInterceptor($cache)]
    );
};