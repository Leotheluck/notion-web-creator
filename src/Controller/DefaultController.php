<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class DefaultController extends AbstractController
{
    /**
     * @var HttpClientInterface
     */
    private $httpClient;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * @Route("/", name="default")
     */
    public function index(): Response
    {
        return $this->json('hello world.');
    }

    /**
     * @Route("/oauth", name="oauth")
     */
    public function oauth(): Response
    {
        $oauth_string = sprintf(
            "https://api.notion.com/v1/oauth/authorize?owner=user&client_id=%s&redirect_uri=http://localhost:8080/oauth_token&response_type=code",
            $this->getParameter('notion_client_id')
        );

        return $this->redirect($oauth_string);
    }

    /**
     * @Route("/oauth_token", name="oauth_token")
     */
    public function oauth_token(Request $request): Response
    {
        $authorization_code = $request->get('code');
        $basicAuthentication = base64_encode(sprintf('%s:%s',
            $this->getParameter('notion_client_id'),
            $this->getParameter('notion_client_secret')
            ));

        try {

            $headers = [
                'Authorization' => sprintf('Basic %s', $basicAuthentication),
                'Content-type' => 'application/json'
            ];

            $body = [
                'code' => $authorization_code,
                'grant_type' => 'authorization_code',
                'redirect_uri' => 'http://localhost:8080/oauth_token'
            ];

            $response = $this->httpClient->request(
                'POST',
                'https://api.notion.com/v1/oauth/token',
                [ 'json' => $body, 'headers' => $headers ]

            );
            $json_response = json_decode($response->getContent(), true);
        } catch (\Exception $e) {
            return $this->json($e->getMessage());
        }

        // Create or update user

        return $this->json($json_response);
    }
}
