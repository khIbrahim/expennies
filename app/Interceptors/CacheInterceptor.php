<?php

namespace App\Interceptors;

use App\Attributes\FromCache;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Ray\Aop\MethodInterceptor;
use Ray\Aop\MethodInvocation;

class CacheInterceptor implements MethodInterceptor
{

    public function __construct(
        private CacheInterface $cache
    ){}

    /**
     * @throws \ReflectionException
     * @throws InvalidArgumentException
     */
    public function invoke(MethodInvocation $invocation)
    {
        $reflection = new \ReflectionMethod($invocation->getThis(), $invocation->getMethod());
        return $this->handleFromCache($reflection, $invocation);
    }

    /**
     * @throws InvalidArgumentException
     */
    private function handleFromCache(\ReflectionMethod $reflection, MethodInvocation $invocation)
    {
        $attributes = $reflection->getAttributes(FromCache::class);
        if (empty($attributes)) {
            return $invocation->proceed();
        }

        /** @var FromCache $attribute */
        $attribute = $attributes[0]->newInstance();

        $param = $attribute->param;
        $args = $invocation->getArguments();
        $paramValue = $args[$param] ?? 'all';
        $cacheKey = $attribute->key . '_' . $paramValue;

        if ($this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }

        $result = $invocation->proceed();
        $this->cache->set($cacheKey, $result, $attribute->expiration);

        return $result;
    }

}