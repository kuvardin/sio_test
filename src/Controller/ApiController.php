<?php

declare(strict_types=1);

namespace App\Controller;

use App\Api\ApiEnvironment;
use App\Api\ApiVersionController;
use App\Api\v1\ApiMethod;
use App\Api\v1\ApiReflection;
use App\Localization\LocaleCode;
use App\Util\TemplatesEngine;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ApiController extends AbstractController
{
    #[Route('api/v1_doc', name: 'new_api_v1_doc', methods: ['GET'])]
    public function getDocumentation(): Response
    {
        $language_code = LocaleCode::RU;

        $models_errors = [];
        $models = ApiReflection::getApiModels($models_errors, $language_code);

        /** @var ApiMethod[]|string[] $methods */
        $methods = [];
        $methods_errors = [];
        $methods_data = [];

        ApiReflection::getApiMethods($methods_data, $methods_errors);


        /** @var int[][] $methods_error_codes */
        $methods_error_codes = [];
        foreach ($methods_data as $method_name => $method_data) {
            $methods[$method_name] = $method_data['class'];
            $methods_error_codes[$method_name] = $method_data['errors'];
        }

        $about_documentation = 'Generated documentation';
        $template_path = ROOT_DIR . '/templates/api/api_v1_doc.php';
        $content = TemplatesEngine::render($template_path, [
            'title' => 'API v1',
            'about_documentation' => $about_documentation,
            'language_code' => $language_code,
            'models' => $models,
            'models_errors' => $models_errors,
            'methods' => $methods,
            'methods_errors' => $methods_errors,
            'methods_error_codes' => $methods_error_codes,
        ]);

        exit($content);
    }

    #[Route('api/v1/{method}', name: 'new_api_handler', requirements: ["method" => ".+"], methods: ["GET","POST"])]
    public function handleApiRequest(
        Request $request,
        EntityManagerInterface $entity_manager,
        ValidatorInterface $validator,
        LoggerInterface $logger,
    ): JsonResponse {
        $route = preg_replace('|\?.*$|', '', $request->getRequestUri());
        $route = trim($route, '/');
        $route_parts = explode('/', $route);
        array_shift($route_parts);

        $version = array_shift($route_parts);
        if ($version === null || $version === '') {
            http_response_code(404);
            exit;
        }

        $version_controller_class = self::getVersionControllerClass($version);
        if (!class_exists($version_controller_class)) {
            http_response_code(404);
            exit;
        }

        $environment = new ApiEnvironment(
            entity_manager: $entity_manager,
            validator: $validator,
            logger: $logger,
            parameter_bag: $this->container->get('parameter_bag'),
        );

        try {
            return $version_controller_class::handle($environment, $route_parts, $request);
        } catch (\Throwable $throwable) {
            $logger->critical(
                sprintf(
                    "%s #%s: %s in %s:%d\Trace: %s",
                    get_class($throwable),
                    $throwable->getCode(),
                    $throwable->getMessage(),
                    $throwable->getFile(),
                    $throwable->getLine(),
                    $throwable->getTraceAsString(),
                ),
            );

            throw $throwable;
        }
    }

    private static function getVersionControllerClass(string $version): string|ApiVersionController
    {
        return "App\\Api\\$version\\ApiVersionController";
    }

    #[Route('/api', name: 'app_api')]
    public function index(): JsonResponse
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/ApiController.php',
        ]);
    }
}
