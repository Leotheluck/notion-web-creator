<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

class UserService
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;
    public function __construct(
        EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getUserFromRequest(Request $request)
    {
        $authorizationHeader = $request->headers->get('Authorization');
        if (null === $authorizationHeader) {
            return null;
        }

        // string: Bearer $token
        //$token = explode('%20', $authorizationHeader)[1];
        $token = explode(' ', $authorizationHeader)[1];
        return $this->entityManager->getRepository(User::class)->findOneByFrontToken($token);
    }

}