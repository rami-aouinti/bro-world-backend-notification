<?php

declare(strict_types=1);

namespace App\Notification\Transport\Controller\Api;

use Bro\WorldCoreBundle\Infrastructure\ValueObject\SymfonyUser;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

/**
 * @package App\Controller
 * @author Rami Aouinti <rami.aouinti@tkdeutschland.de>
 */
#[AsController]
#[OA\Tag(name: 'Notification')]
class UploadTemplateController
{
    #[Route(path: '/v1/platform/templates/upload', name: 'template_upload', methods: [Request::METHOD_POST])]
    public function __invoke(SymfonyUser $symfonyUser, KernelInterface $kernel): JsonResponse
    {
        $application = new Application($kernel);
        $application->setAutoExit(false);

        $input = new ArrayInput([
            'command' => 'app:notification:upload-templates',
        ]);

        $output = new BufferedOutput();

        try {
            $exitCode = $application->run($input, $output);

            return new JsonResponse([
                'status' => $exitCode === 0 ? 'success' : 'error',
            ], $exitCode === 0 ? Response::HTTP_OK : Response::HTTP_INTERNAL_SERVER_ERROR);
        } catch (Throwable $e) {
            return new JsonResponse([
                'status' => 'exception',
                'message' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
