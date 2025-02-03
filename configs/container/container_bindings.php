<?php

declare(strict_types = 1);

use App\Auth;
use App\Config;
use App\Contracts\AuthInterface;
use App\Contracts\EntityManagerServiceInterface;
use App\Contracts\RequestValidatorFactoryInterface;
use App\Contracts\SessionInterface;
use App\Contracts\UserProviderServiceInterface;
use App\Csrf;
use App\DTO\SessionConfig;
use App\Enum\AppEnvironment;
use App\Enum\SameSite;
use App\Enum\StorageDriver;
use App\Filters\UserFilter;
use App\RequestValidators\RequestValidatorFactory;
use App\RouteEntityBindingStrategy;
use App\Services\EntityManagerService;
use App\Services\UserProviderService;
use App\Session;
use Clockwork\DataSource\DoctrineDataSource;
use Clockwork\Storage\FileStorage;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMSetup;
use League\Flysystem\Filesystem;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\SimpleCache\CacheInterface;
use Ray\Aop\Aspect;
use Slim\App;
use Slim\Csrf\Guard;
use Slim\Factory\AppFactory;
use Slim\Interfaces\RouteParserInterface;
use Slim\Views\Twig;
use Symfony\Bridge\Twig\Extension\AssetExtension;
use Symfony\Bridge\Twig\Mime\BodyRenderer;
use Symfony\Component\Asset\Package;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Asset\VersionStrategy\JsonManifestVersionStrategy;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Cache\Psr16Cache;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\BodyRendererInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\CacheStorage;
use Symfony\WebpackEncoreBundle\Asset\EntrypointLookup;
use Symfony\WebpackEncoreBundle\Asset\TagRenderer;
use Symfony\WebpackEncoreBundle\Twig\EntryFilesTwigExtension;
use function DI\create;
use Clockwork\Clockwork;

return [
    App::class => function(ContainerInterface $container){
        AppFactory::setContainer($container);
        $app = AppFactory::create();

        $routeCollector = $app->getRouteCollector();
        $routeCollector->setDefaultInvocationStrategy(new RouteEntityBindingStrategy(
            $container->get(EntityManagerServiceInterface::class),
            $app->getResponseFactory()
        ));

        $router = require CONFIG_PATH . '/routes/web.php';
        $router($app);

        $addMiddlewares = require CONFIG_PATH . '/middleware.php';
        $addMiddlewares($app);

        return $app;
    },
    Config::class                 => create(Config::class)->constructor(require CONFIG_PATH . '/app.php'),
    EntityManagerInterface::class          => function(Config $config){
        $configuration = ORMSetup::createAttributeMetadataConfiguration(
            $config->get('doctrine.entity_dir'),
            $config->get('doctrine.dev_mode')
        );

        $configuration->addFilter('user', UserFilter::class);

        return EntityManager::create(
            $config->get('doctrine.connection'),
            $configuration
        );
    },
    Twig::class                   => function (Config $config, ContainerInterface $container) {
        $twig = Twig::create(VIEW_PATH, [
            'cache'       => STORAGE_PATH . '/cache/templates',
            'auto_reload' => AppEnvironment::isDevelopment($config->get('app_environment')), 
        ]);

        //$twig->addExtension(new IntlExtension());
        $twig->addExtension(new EntryFilesTwigExtension($container));
        $twig->addExtension(new AssetExtension($container->get('webpack_encore.packages')));

        return $twig;
    },
    /**
     * The following two bindings are needed for EntryFilesTwigExtension & AssetExtension to work for Twig
     */
    'webpack_encore.packages'     => fn() => new Packages(
        new Package(new JsonManifestVersionStrategy(BUILD_PATH . '/manifest.json'))
    ),
    'webpack_encore.tag_renderer' => fn(ContainerInterface $container) => new TagRenderer(
        new EntrypointLookup(BUILD_PATH . '/entrypoints.json'),
        $container->get('webpack_encore.packages')
    ),
    ResponseFactoryInterface::class => fn(App $app) => $app->getResponseFactory(),
    AuthInterface::class => fn(ContainerInterface $container) => $container->get(
        Auth::class
    ),
    UserProviderServiceInterface::class => fn(ContainerInterface $container) => $container->get(
        UserProviderService::class
    ),
    SessionInterface::class => fn(Config $config) => new Session(
        new SessionConfig(
            $config->get('session.name', 'session_expennies'),
            $config->get('session.secure', true),
            $config->get('session.httponly', true),
            SameSite::from($config->get('session.samesite', SameSite::Lax->value)),
            $config->get('session.flash_name', 'session_flash')
        )
    ),
    RequestValidatorFactoryInterface::class => fn(ContainerInterface $container) => $container->get(
        RequestValidatorFactory::class
    ),
    'csrf' => fn(
        ResponseFactoryInterface $responseFactory,
        Csrf $csrf
    ) => new Guard($responseFactory, failureHandler: $csrf->failureHandler(), persistentTokenMode: true),
    Filesystem::class => function (Config $config) {
        $adapter = match ($config->get('storage.driver')){
            StorageDriver::Local => new League\Flysystem\Local\LocalFilesystemAdapter(STORAGE_PATH)
        };

        return new League\Flysystem\Filesystem($adapter);
    },
    Clockwork::class => function(EntityManagerInterface $entityManager) {
        $clockwork = new Clockwork();

        $clockwork->storage(new FileStorage(STORAGE_PATH . '/clockwork'));
        $clockwork->addDataSource(new DoctrineDataSource($entityManager));

        return $clockwork;
    },
    EntityManagerServiceInterface::class => fn(EntityManagerInterface $entityManager) => new EntityManagerService($entityManager),
    MailerInterface::class => function(Config $config){
        $transport = Transport::fromDsn($config->get('mailer.dsn'));

        return new Mailer($transport);
    },
    BodyRendererInterface::class => fn(Twig $twig) => new BodyRenderer($twig->getEnvironment()),
    RouteParserInterface::class => fn(App $app) => $app->getRouteCollector()->getRouteParser(),
    RedisAdapter::class => function(Config $config){
        $redis = new \Redis();

        $redis->connect($config->get('redis.host'), (int) $config->get('redis.port'));
        $redis->auth($config->get('redis.password'));

        return new RedisAdapter($redis);
    },
    CacheInterface::class => fn (RedisAdapter $adapter) => new Psr16Cache($adapter),
    RateLimiterFactory::class => function (Config $config, RedisAdapter $adapter) {
        $storage = new CacheStorage($adapter);

        return new RateLimiterFactory($config->get('limiter'), $storage);
    },
    Aspect::class => function(){
        $aspect = new Aspect();

        $interceptors = require CONFIG_PATH . '/interceptors/interceptors.php';
        $interceptors($aspect);

        return $aspect;
    },
];
