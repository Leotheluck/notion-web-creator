<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use App\Entity\User;


class NotionService
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


    public function __construct(
        EntityManagerInterface $entityManager, 
        HttpClientInterface $httpClient,
        ParameterBagInterface $parameterBag
        )
    {
        $this->entityManager = $entityManager;
        $this->httpClient = $httpClient;
        $this->parameterBag = $parameterBag;
    }

    public function getWorkSpaceContent($token): array
    {
        $authorizationHeader = sprintf('Bearer %s', $token);
        
        $workspace = $this->httpClient->request('POST', 'https://api.notion.com/v1/search/', [
            'body' => [
                'query' => '',
            ],
            'headers' => [
                'Authorization' => $authorizationHeader,
                'Notion-Version' => '2021-08-16'
                ]
            ]);
            
        return json_decode($workspace->getContent(), true);
    }

    public function fetchContent($token, $page_id)
    {
        $authorizationHeader = sprintf('Bearer %s', $token);
        $fetchURL = sprintf('https://api.notion.com/v1/blocks/%s/children', $page_id);
        
        $page = $this->httpClient->request('GET', $fetchURL, [
            'body' => [
                'query' => '',
            ],
            'headers' => [
                'Authorization' => $authorizationHeader,
                'Notion-Version' => '2021-08-16'
                ]
            ]);

        return json_decode($page->getContent(), true);
    }
}
