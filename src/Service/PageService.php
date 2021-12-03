<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use http\Env\Response;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Filesystem\Filesystem;
use App\Service\NotionService;

class PageService
{
    /**
     * @var EntityManagerInterface
     */

    private $entityManager;

    /**
     * @var HttpClientInterface
     */

    private $httpClient;

    /**
     * @var ParameterBagInterface
     */
    private $parameterBag;

    /**
     * @var NotionService
     */

    private $notionService;


    public function __construct(
        EntityManagerInterface $entityManager,
        HttpClientInterface $httpClient,
        ParameterBagInterface $parameterBag,
        NotionService $notionService
    )
    {
        $this->entityManager = $entityManager;
        $this->httpClient = $httpClient;
        $this->parameterBag = $parameterBag;
        $this->notionService = $notionService;
    }

    public function buildPage($data): array
    {
        $file_content = [];

        array_push(
            $file_content,
            '
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My website</title>
</head>
<body>');

        array_push(
            $file_content,
            sprintf('
    <h1>%s</h1>', json_decode($data, true)));

        array_push(
            $file_content,
            '
</body>
</html>');

        return $file_content;
    }
}