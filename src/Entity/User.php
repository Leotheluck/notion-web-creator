<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=UserRepository::class)
 */
class User
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="integer")
     */
    private $uid;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $token;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $workspace_name;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $workspace_icon;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $workspace_id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $notion_id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $notion_name;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $notion_icon;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $notion_email;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUid(): ?int
    {
        return $this->uid;
    }

    public function setUid(int $uid): self
    {
        $this->uid = $uid;

        return $this;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(string $token): self
    {
        $this->token = $token;

        return $this;
    }

    public function getWorkspaceName(): ?string
    {
        return $this->workspace_name;
    }

    public function setWorkspaceName(string $workspace_name): self
    {
        $this->workspace_name = $workspace_name;

        return $this;
    }

    public function getWorkspaceId(): ?string
    {
        return $this->workspace_id;
    }

    public function getWorkspaceIcon(): ?string
    {
        return $this->workspace_icon;
    }

    public function setWorkspaceIcon(?string $workspace_icon): self
    {
        $this->workspace_icon = $workspace_icon;

        return $this;
    }
    
    public function setWorkspaceId(string $workspace_id): self
    {
        $this->workspace_id = $workspace_id;

        return $this;
    }

    public function getNotionId(): ?string
    {
        return $this->notion_id;
    }

    public function setNotionId(string $notion_id): self
    {
        $this->notion_id = $notion_id;

        return $this;
    }

    public function getNotionName(): ?string
    {
        return $this->notion_name;
    }

    public function setNotionName(string $notion_name): self
    {
        $this->notion_name = $notion_name;

        return $this;
    }

    public function getNotionIcon(): ?string
    {
        return $this->notion_icon;
    }

    public function setNotionIcon(?string $notion_icon): self
    {
        $this->notion_icon = $notion_icon;

        return $this;
    }

    public function getNotionEmail(): ?string
    {
        return $this->notion_email;
    }

    public function setNotionEmail(string $notion_email): self
    {
        $this->notion_email = $notion_email;

        return $this;
    }

}
