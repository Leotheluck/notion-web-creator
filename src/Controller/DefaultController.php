<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use App\Entity\User;
use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

class DefaultController extends AbstractController
{
    /**
     * @var HttpClientInterface
     */
    private $httpClient;

    /**
     * @var UserService
     */
    private $userService;

    public function __construct(
        HttpClientInterface $httpClient,
        UserService $userService
    )

    {
        $this->httpClient = $httpClient;
        $this->userService = $userService;
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
    public function oauth_token(Request $request, ManagerRegistry $doctrine): Response
    {
        $entityManager = $doctrine->getManager();
        $notionClientId = $this->getParameter('notion_client_id');
        $notionClientSecret = $this->getParameter('notion_client_secret');

        $authorization_code = $request->get('code');
        $basicAuthentication = base64_encode(sprintf('%s:%s',
            $notionClientId,
            $notionClientSecret
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

        $frontToken = substr(sha1($json_response['owner']['user']['person']['email']), 0, 64);

        $user = new User();
        $user->setToken($json_response['access_token']);
        $user->setWorkspaceName($json_response['workspace_name']);
        $user->setWorkspaceIcon($json_response['workspace_icon']);
        $user->setWorkspaceId($json_response['workspace_id']);
        $user->setNotionId($json_response['owner']['user']['id']);
        $user->setNotionName($json_response['owner']['user']['name']);
        $user->setNotionIcon($json_response['owner']['user']['avatar_url']);
        $user->setNotionEmail($json_response['owner']['user']['person']['email']);
        $user->setFrontToken($frontToken);
        $entityManager->persist($user);
        $entityManager->flush();


        return $this->redirect(sprintf("http://localhost:3000?frontToken=%s", $frontToken));
    }

    /**
     * @Route("/user_logged", name="user_logged")
     */

     public function user_logged(Request $request): Response{
        /** @var User $user */
        $user = $this->userService->getUserFromRequest($request);
        if (null === $user) {
            return new Response('Unauthorized', 401);
        }

        $user->getNotionEmail();
        
        return true;
     }

    /**
     * @Route("/get_info", name="get_info")
     */
    public function get_info(): Response
    {
        $data = $this->getDoctrine()->getRepository(User::class)->findOneBy(['workspace_id' => $_GET['code']]);

        $token = $data->getToken();

        $authorizationHeader = sprintf('Bearer %s', $token);

        $pages = $this->httpClient->request('POST', 'https://api.notion.com/v1/search/', [
            'body' => [
                'query' => '',
            ],
            'headers' => [
                'Authorization' => $authorizationHeader,
                'Notion-Version' => '2021-08-16'
            ]
        ]);

        $filesystem = new Filesystem();
        // Create file
        $staticWebsitesRootDir = sprintf('%s/%s', $this->getParameter('kernel.project_dir'), $this->getParameter('static_websites_root'));
        $fileName = sprintf('%s/%s', $staticWebsitesRootDir, 'test.html');

        $filesystem->dumpFile($fileName, 'coucou');

        return $this->json($pages->getContent());
    }

}
