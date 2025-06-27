<?php

declare(strict_types=1);

namespace App\Notification\Transport\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\KernelInterface;
use Throwable;

/**
 * Class UploadTemplateController
 * @package App\Controller
 * @author Rami Aouinti <rami.aouinti@tkdeutschland.de>
 */
class UploadTemplateController extends AbstractController
{
    /**
     * @param KernelInterface $kernel
     * @return JsonResponse
     */
    public function __invoke(KernelInterface $kernel): JsonResponse
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
