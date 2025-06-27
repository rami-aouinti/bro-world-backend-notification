<?php

declare(strict_types=1);

namespace App\Notification\Application\Service;

use App\Notification\Domain\Entity\MailjetTemplate;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

use function sprintf;

/**
 * Class MailjetTemplateService
 *
 * @package App\Notification\Application\Service
 * @author  Rami Aouinti <rami.aouinti@tkdeutschland.de>
 */
class MailjetTemplateService
{
    private string $apiUrl = 'https://api.mailjet.com/v3/REST/template';
    private string $detailContentUrl = 'https://api.mailjet.com/v3/REST/template/%d/detailcontent';
    private string $detailUrl = 'https://api.mailjet.com/v3/REST/template/%d/detail';

    public function __construct(
        private readonly HttpClientInterface    $httpClient,
        private readonly EntityManagerInterface $entityManager,
        private readonly string                 $mailjetApiKey,
        private readonly string                 $mailjetSecretKey
    ) {}

    /**
     * @throws ClientExceptionInterface
     * @throws TransportExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function fetchAndStoreTemplates(): void
    {
        $response = $this->httpClient->request('GET', $this->apiUrl, [
            'auth_basic' => [$this->mailjetApiKey, $this->mailjetSecretKey]
        ]);

        $data = $response->toArray();

        foreach ($data['Data'] as $template) {
            $templateId = $template['ID'];

            $variables = $this->fetchTemplateVariables($templateId);

            $existingTemplate = $this->entityManager->getRepository(MailjetTemplate::class)
                ->findOneBy(['templateId' => $templateId]);

            if (!$existingTemplate) {
                $existingTemplate = new MailjetTemplate();
                $existingTemplate->setTemplateId($templateId);
            }

            $existingTemplate->setName($template['Name']);
            $existingTemplate->setLocale($template['Locale'] ?? 'en_US');
            $existingTemplate->setVariables($variables);
            $this->entityManager->persist($existingTemplate);
        }

        $this->entityManager->flush();
    }

    /**
     * @param int $templateId
     * @return array
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    private function fetchTemplateVariables(int $templateId): array
    {
        try {
            $response = $this->httpClient->request('GET', sprintf($this->detailContentUrl, $templateId), [
                'auth_basic' => [$this->mailjetApiKey, $this->mailjetSecretKey]
            ]);

            $data = $response->toArray();

            if (isset($data['Data'])) {
                foreach ($data['Data'] as $document) {
                    if(isset($document['Text-part'])) {
                        return $this->extractVariables($document['Text-part']);
                    }
                }
            }

        } catch (ClientExceptionInterface $e) {
            if ($e->getCode() !== 404) {
                throw $e;
            }
        }

        return [];
    }

    private function extractVariables(string $text): array
    {
        preg_match_all('/\{\{var:([\w\d_]+)\}\}/', $text, $matches1);
        preg_match_all('/\{% if var:([\w\d_]+)\s/', $text, $matches2);
        preg_match_all('/\{% for ([\w\d_]+) in var:([\w\d_]+)\s/', $text, $matches3);
        preg_match_all('/\{\{([\w\d_]+)\.([\w\d_]+)\}\}/', $text, $matches4);
        $variables = array_merge($matches1[1] ?? [], $matches2[1] ?? []);
        $groupedVariables = [];
        foreach ($matches3[1] as $key => $loopVar) {
            $itemName = $matches3[2][$key];
            if (!isset($groupedVariables[$itemName])) {
                $groupedVariables[$itemName] = [];
            }
            foreach ($matches4[2] as $attribute) {
                $groupedVariables[$itemName][] = $attribute;
            }
        }
        $variables = array_unique($variables);
        foreach ($groupedVariables as $item => &$attributes) {
            $attributes = array_unique($attributes);
        }
        $result = [];
        foreach ($variables as $var) {
            $result[] = $var;
        }

        unset($attributes);
        foreach ($groupedVariables as $item => $attributes) {
            $result[] = [
                $item => $attributes
            ];
        }

        return $result;
    }
}
