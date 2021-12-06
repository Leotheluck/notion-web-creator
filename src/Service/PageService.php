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
        // Get stylesheet
        $styles = explode(",", $stylesheet);

        // Explode array
        for ($i = 0; $i < count($styles); $i++) {
            $styles[$i] = explode('::', $styles[$i]);
        }

        // Fetch content from the workspace
        $workspace_content = $this->notionService->getWorkSpaceContent($token);

        // Fetch content from the first page of the workspace
        $page_content = $this->notionService->fetchContent($token, $workspace_content['results'][0]['id']);

        $file_content = [];

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
<body>
    <div class="header">
        <a class="title" href="#">%s %s</a>
        <a class="credit" href="https://www.selfer.fr">Made with Selfer</a>
    </div>
    <div class="container">',
                $workspace_content['results'][0]['properties']['title']['title'][0]['text']['content'],
                "'","'","'","'","'","'","'","'",
                $workspace_content['results'][0]['icon']['emoji'],
                $workspace_content['results'][0]['icon']['emoji'],
                $workspace_content['results'][0]['properties']['title']['title'][0]['text']['content']
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
                } else if (!empty($result['heading_1']['text'][0]['text']['content']) && $styling == 2) {
                    array_push(
                        $file_content,
                        sprintf(
                            '
    <h1 class="style-2">%s</h1>',
                            $result['heading_1']['text'][0]['text']['content']
                        )
                    );
                }
            } else if ($type == 'heading_2') {
                if (!empty($result['heading_2']['text'][0]['text']['content']) && $styling == 0) {
                    array_push(
                        $file_content,
                        sprintf(
                            '
    <h2>%s</h2>',
                            $result['heading_2']['text'][0]['text']['content']
                        )
                    );
                } else if (!empty($result['heading_2']['text'][0]['text']['content']) && $styling == 1) {
                    array_push(
                        $file_content,
                        sprintf(
                            '
    <h2 class="style-1">%s</h2>',
                            $result['heading_2']['text'][0]['text']['content']
                        )
                    );
                } else if (!empty($result['heading_2']['text'][0]['text']['content']) && $styling == 2) {
                    array_push(
                        $file_content,
                        sprintf(
                            '
    <h2 class="style-2">%s</h2>',
                            $result['heading_2']['text'][0]['text']['content']
                        )
                    );
                }
            } else if ($type == 'heading_3') {
                if (!empty($result['heading_3']['text'][0]['text']['content']) && $styling == 0) {
                    array_push(
                        $file_content,
                        sprintf(
                            '
    <h3>%s</h3>',
                            $result['heading_3']['text'][0]['text']['content']
                        )
                    );
                } else if (!empty($result['heading_3']['text'][0]['text']['content']) && $styling == 1) {
                    array_push(
                        $file_content,
                        sprintf(
                            '
    <h3 class="style-1">%s</h3>',
                            $result['heading_3']['text'][0]['text']['content']
                        )
                    );
                } else if (!empty($result['heading_3']['text'][0]['text']['content']) && $styling == 2) {
                    array_push(
                        $file_content,
                        sprintf(
                            '
    <h3 class="style-2">%s</h3>',
                            $result['heading_3']['text'][0]['text']['content']
                        )
                    );
                }
            } else if ($type == 'paragraph') {
                if (!empty($result['paragraph']['text'][0]['text']['content']) && $styling == 0) {
                    array_push(
                        $file_content,
                        sprintf(
                            '
    <p>%s</p>',
                            $result['paragraph']['text'][0]['text']['content']
                        )
                    );
                } else if (!empty($result['paragraph']['text'][0]['text']['content']) && $styling == 1) {
                    array_push(
                        $file_content,
                        sprintf(
                            '
    <p class="style-1">%s</p>',
                            $result['paragraph']['text'][0]['text']['content']
                        )
                    );
                } else if (!empty($result['paragraph']['text'][0]['text']['content']) && $styling == 2) {
                    array_push(
                        $file_content,
                        sprintf(
                            '
    <p class="style-2">%s</p>',
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
    </div>
</body>
</html>

<style>
*{
    margin: 0;
}

html{
    scroll-behavior: smooth;
}

.header{
    position: fixed;
    width: 100vw;
    height: 10vh;
    background: #FF8787;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.header .title{
    margin-left: 20vw;
    font-size: 2vw;
    text-decoration: none;
    color: #fff;
    font-family: sans-serif;
    transition: 0.3s;
    font-weight: 600;
}

.header .title:hover{
    transform: scale(0.975);
}

.header .credit{
    margin-right: 20vw;
    padding-top: .5vh;
    font-size: 1vw;
    text-decoration: none;
    color: #f6f6f6;
    font-family: sans-serif;
    font-weight: 400;
}

.header .credit:hover{
    text-decoration: underline;
}

.container{
    width: 60vw;
    padding-top: 20vh;
    margin-left: 20vw;
    margin-right: 20vw;
    display: flex;
    flex-direction: column;
    margin-bottom: 20vh;
}

h1{
    font-size: 5vw;
    font-family: sans-serif;
    font-weight: 700;
    margin: 3vh 0 .5vh 0;
}

h1.style-1{
    text-decoration: underline;
}

h1.style-2{
    color: blue;
}

h2{
    font-size: 3.5vw;
    font-family: sans-serif;
    font-weight: 600;
    margin: 2vh 0 .5vh 0;
}

h2.style-1{
    text-decoration: underline;
}

h2.style-2{
    background: blue;
}

h3{
    font-size: 2.5vw;
    font-family: sans-serif;
    font-weight: 500;
    margin: 1.25vh 0 .5vh 0;
}

h3.style-1{
    background: blue;
}

h3.style-2{
    background: blue;
}

p{
    font-size: 1.2vw;
    font-family: sans-serif;
    font-weight: 400;
    margin: 1vh 0 .5vh 0;
}

p.style-1{
    background: blue;
}

p.style-2{
    background: blue;
}

img{
    margin: 2.5vh 0 2.5vh 0;
    border-radius: 30px;
    width: 60vw;
}

img.style-1{
    background: blue;
}

img.style-2{
    background: blue;
}

</style>');

        return $file_content;
    }
}