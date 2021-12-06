<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use App\Entity\User;
use App\Entity\Page;
use App\Service\UserService;
use App\Service\NotionService;
use App\Service\PageService;
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

    /**
     * @var NotionService
     */

    private $notionService;

    /**
     * @var PageService
     */

    private $pageService;

    public function __construct(
        HttpClientInterface $httpClient,
        UserService $userService,
        NotionService $notionService,
        PageService $pageService
    )

    {
        $this->httpClient = $httpClient;
        $this->userService = $userService;
        $this->notionService = $notionService;
        $this->pageService = $pageService;
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
        // Create string with hidden client ID to secure
        $oauth_string = sprintf(
            "https://api.notion.com/v1/oauth/authorize?owner=user&client_id=%s&redirect_uri=https://api.selfer.fr/oauth_token&response_type=code",
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
        // Fetch client ID from services
        $notionClientId = $this->getParameter('notion_client_id');

        // Fetch client secret from services
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
                'redirect_uri' => 'https://api.selfer.fr/oauth_token'
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

        // Create front token used to login in front
    
        $frontToken = substr(sha1($json_response['owner']['user']['person']['email']), 0, 64);

        // Create new user to assign variables
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



        // Redirect in front page once the user is logged and variable are sent in DB
        return $this->redirect(sprintf("https://selfer.fr?frontToken=%s", $frontToken));
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

        $workspace_content = $this->notionService->getWorkSpaceContent($token);

//        return $this->json($workspace_content);

        // return $this->json($workspace_content['results'][0]['id']);

        $page_content = $this->notionService->fetchContent($token, $workspace_content['results'][0]['id']);

        return $this->json($page_content);

        $componentArray = [];

        foreach ($page_content['results'] as $element) {
            $type = $element['type'];

            if ($type == 'heading_1' || $type == 'heading_2' || $type == 'heading_3') {
                array_push($componentArray, $element[$type]['text'][0]['text']['content']);
            } 
            else if ($type == 'image') {
                array_push($componentArray, $element[$type]['file']['url']);
            }


            // else if($type == 'paragraph'){
            //     foreach ($element['paragraph']['text'] as $texts) {
            //         if (!empty($texts)) {
            //             foreach ($texts['text'] as $text) {
            //                 var_dump($text);
            //             }
            //             //die;
            //             //array_push($componentArray, $text['text']['content']);
            //         }
            //     }
            else if($type== 'paragraph'){
                if(!empty($element[$type]['text'][0]['text']['content'])){
                    array_push($componentArray, $element[$type]['text'][0]['text']['content']);
                }
                // var_dump($element[$type]['text']);
        
            }
            
            array_push($componentArray, sprintf('(Type : %s)' ,$type));
        }

        return $this->json($componentArray);

        //return $this->json($this->notionService->fetchContent($token, $page_content['id']));

        // $authorizationHeader = sprintf('Bearer %s', $token);

        // $workspace = $this->httpClient->request('POST', 'https://api.notion.com/v1/search/', [
        //     'body' => [
        //         'query' => '',
        //     ],
        //     'headers' => [
        //         'Authorization' => $authorizationHeader,
        //         'Notion-Version' => '2021-08-16'
        //     ]
        // ]);

        // $workspace_decoded = json_decode($workspace->getContent(), true);

        return $this->json($workspace_decoded['results'][0]['id']);
    }

    /**
     * @Route ("/build_page", name="build_page")
     */

    public function build_page(ManagerRegistry $doctrine): Response
    {
        //
        // Data
        //

        $entityManager = $doctrine->getManager();

        $page = new Page();
        $page->setPageId($_GET['page_id']);
        $page->setPageName($_GET['page_name']);
        $page->setWorkspaceId($_GET['code']);
        $page->setStylesheet($_GET['style']);
        $entityManager->persist($page);
        $entityManager->flush();

        // Identify matching workspace in DB
        $data = $this->getDoctrine()->getRepository(User::class)->findOneBy(['workspace_id' => $_GET['code']]);

        // Get the token associated with the workspace selected
        $token = $data->getToken();

        // Fetch style data
        $superstyle = $this->getDoctrine()->getRepository(Page::class)->findOneBy(['page_id' => $_GET['page_id']]);

        // File name
        $filename = $superstyle->getPageName();

        // Get Stylesheet
        $stylesheet = $superstyle->getStylesheet();

        //
        // File
        //

        // Initiate File
        $filesystem = new Filesystem();

        // Add root
        $staticWebsitesRootDir = sprintf('%s/%s', $this->getParameter('kernel.project_dir'), $this->getParameter('static_websites_root'));

        // Set file path & name
        $filepath = sprintf('%s/%s', $staticWebsitesRootDir, sprintf('%s.html', $filename));

        // Build file content
        $file_content = $this->pageService->buildPage($token, $stylesheet);

        // Create final file
        $filesystem->dumpFile($filepath, implode("", $file_content));

        // Send success message
        return $this->redirect(sprintf("https://api.selfer.fr/s?p=%s", $filename));
        return $this->json(sprintf("Done ! Your Notion data has been implemented into the new website %s.html!", $filename));
    }

    /**
     * @Route ("/notion_data", name="notion_data")
     */

    public function notion_data():Response
    {
     
        // return $this->json('hello world.');
        $data = $this->getDoctrine()->getRepository(User::class)->findOneBy(['workspace_id' => $_GET['code']]);

        $token = $data->getToken();

        $workspace_content = $this->notionService->getWorkSpaceContent($token);

        $returnArrayPage = [];
        $returnArrayWorkspace = [];

        $page_content = $this->notionService->fetchContent($token, $workspace_content['results'][0]['id']);

        foreach ($page_content['results'] as $element) {
            if ($element['type'] == 'heading_1' || $element['type'] == 'heading_2' || $element['type'] == 'heading_3' || $element['type'] == 'paragraph') {
                if (!empty($element[$element['type']]['text'][0]['text']['content'])) {
                    $type = $element['type'];
                    $returnArrayPage []= [
                        'obj' => $type,
                        'id' => $element['id'],
                        'content' => $element[$type]['text'][0]['text']['content'],
                        'childrens' => []
                    ];
                }
            } else if ($element['type'] == 'image') {
                if (!empty($element[$element['type']]['file']['url'])) {
                    $type = $element['type'];
                    $returnArrayPage []= [
                        'obj' => $type,
                        'id' => $element['id'],
                        'content' => $element[$type]['file']['url'],
                        'childrens' => []
                    ];
                }
            }
        }
        // return $this->json($returnArray);

        $returnArrayWorkspace []= [
            'obj' => $workspace_content['results'][0]['object'],
            'id' => $workspace_content['results'][0]['id'],
            'content' => $workspace_content['results'][0]['properties']['title']['title'][0]['plain_text'], 
            'childrens' => $returnArrayPage,
        ];

        $returnArray = $returnArrayWorkspace;

        return $this->json($returnArray);

    }

    /**
     * @Route ("/s", name="s")
     */
    public function s (): Response
    {
        return $this->render(sprintf('%s.html', $_GET['p']));
    }
}
