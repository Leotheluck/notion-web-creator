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

    public function buildPage($token, $stylesheet): array
    {
        // Fetch content from the workspace
        $workspace_content = $this->notionService->getWorkSpaceContent($token);

        // Fetch content from the first page of the workspace
        $page_content = $this->notionService->fetchContent($token, $workspace_content['results'][0]['id']);

        // Store file content
        $file_content = [];

        // Get stylesheet
        $styles = explode(",", $stylesheet);

        // Explode array
        for ($i = 0; $i < count($styles); $i++) {
            $styles[$i] = explode('::', $styles[$i]);
        }

        array_push(
            $file_content,
            sprintf('
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>%s</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%shttp://www.w3.org/2000/svg%s viewBox=%s0 0 100 100%s><text y=%s.9em%s font-size=%s90%s>%s</text></svg>">
</head>
<body>',
                $workspace_content['results'][0]['properties']['title']['title'][0]['text']['content'],
                "'","'","'","'","'","'","'","'",
                $workspace_content['results'][0]['icon']['emoji']
            )
        );

        foreach ($page_content['results'] as $result) {
            $type = $result['type'];

            $styling = 0;
            for ($i = 0; $i < count($styles); $i++) {
                if ($styles[$i][0] == $result['id']) {
                    $styling = $styles[$i][1];
                }
            }

            if ($type == 'heading_1') {
                if (!empty($result['heading_1']['text'][0]['text']['content']) && $styling == 0) {
                    array_push(
                        $file_content,
                        sprintf(
                            '
    <h1>%s</h1>',
                            $result['heading_1']['text'][0]['text']['content']
                        )
                    );
                } else if (!empty($result['heading_1']['text'][0]['text']['content']) && $styling == 1) {
                    array_push(
                        $file_content,
                        sprintf(
                            '
    <h1 class="style-1">%s</h1>',
                            $result['heading_1']['text'][0]['text']['content']
                        )
                    );
                }
            } else if ($type == 'heading_2') {
                if (!empty($result['heading_2']['text'][0]['text']['content'])) {
                    array_push(
                        $file_content,
                        sprintf(
                            '
    <h2>%s</h2>',
                            $result['heading_2']['text'][0]['text']['content']
                        )
                    );
                }
            } else if ($type == 'heading_3') {
                if (!empty($result['heading_3']['text'][0]['text']['content'])) {
                    array_push(
                        $file_content,
                        sprintf(
                            '
    <h3>%s</h3>',
                            $result['heading_3']['text'][0]['text']['content']
                        )
                    );
                }
            } else if ($type == 'paragraph') {
                if (!empty($result['paragraph']['text'][0]['text']['content'])) {
                    array_push(
                        $file_content,
                        sprintf(
                            '
    <p>%s</p>',
                            $result['paragraph']['text'][0]['text']['content']
                        )
                    );
                }
            } else if ($type == 'image') {
                if (!empty($result['image']['file']['url'])) {
                    array_push(
                        $file_content,
                        sprintf(
                            '
    <img src="%s"/>',
                            $result['image']['file']['url']
                        )
                    );
                }
            }
        }

        array_push(
            $file_content,
            '
</body>
</html>

<style>
h1{
    background: red;
}

h1.style-1{
    background: blue;
}
</style>');

        return $file_content;
    }
}