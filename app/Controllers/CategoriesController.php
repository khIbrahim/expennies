<?php

namespace App\Controllers;

use App\Contracts\EntityManagerServiceInterface;
use App\Contracts\RequestValidatorFactoryInterface;
use App\Entity\Category;
use App\RequestValidators\CreateCategoryRequestValidation;
use App\RequestValidators\UpdateCategoryRequestValidator;
use App\ResponseFormatter;
use App\Services\CategoryService;
use App\Services\RequestService;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class CategoriesController
{

    public function __construct(
        private readonly Twig                             $twig,
        private readonly RequestValidatorFactoryInterface $requestValidatorFactory,
        private readonly CategoryService                  $categoryService,
        private readonly ResponseFormatter                $responseFormatter,
        private readonly RequestService                   $requestService,
        private readonly EntityManagerServiceInterface    $entityManagerService
    ){}


    /**
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError|NotSupported
     */
    public function index(Request $request, Response $response): Response {
        return $this->twig->render(
            $response,
            'categories/index.twig',
            [
                'categories' => $this->categoryService->getAll()
            ]
        );
    }

    public function store(Request $request, Response $response): Response
    {
        $data = $this->requestValidatorFactory->make(CreateCategoryRequestValidation::class)->validate(
            $request->getParsedBody()
        );

        $category = $this->categoryService->create($data['name'], $request->getAttribute('user'));
        $this->entityManagerService->sync($category);

        return $response->withHeader('Location', '/categories')->withStatus(302);
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        $category = $this->categoryService->getById((int) $args['id']);

        $this->entityManagerService->delete($category, true);

        return $response->withHeader('Location', '/categories')->withStatus(302);
    }

    public function get(Request $request, Response $response, array $args): Response
    {
        $category = $this->categoryService->getById((int) $args['id']);

        if(! $category){
            return $response->withStatus(404);
        }

        $data = ['id' => $category->getId(), 'name' => $category->getName()];

        return $this->responseFormatter->asJson($response, $data);
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function update(Request $request, Response $response, array $args): Response
    {
        $data = $this->requestValidatorFactory->make(UpdateCategoryRequestValidator::class)->validate(
            $args + $request->getParsedBody()
        );

        $id   = (int) $data['id'];
        $name = (string) $data['name'];

        $category = $this->categoryService->getById($id);

        if(! $category){
            return $response->withStatus(404);
        }

        $this->entityManagerService->sync($this->categoryService->update($category, $name));

        return $response;
    }

    /**
     * @throws NotSupported
     * @throws \Exception
     */
    public function load(Request $request, Response $response): Response
    {
        $params = $this->requestService->getDatatableQueryParams($request);
        $categories = $this->categoryService->getPaginatedCategories($params);

        $transformer = function(Category $category){
            return [
                'id'        => $category->getId(),
                'name'      => $category->getName(),
                'user'      => $category->getUser()->getName(),
                'updatedAt' => $category->getUpdatedAt()->format('m/d/Y g:i A'),
                'createdAt' => $category->getCreatedAt()->format('m/d/Y g:i A')
            ];
        };

        $totalCategories = count($categories);

        return $this->responseFormatter->asJson($response, [
            'data'            => array_map($transformer, (array) $categories->getIterator()),
            'draw'            => $params->draw,
            'recordsTotal'    => $totalCategories,
            'recordsFiltered' => $totalCategories,
        ]);
    }

}