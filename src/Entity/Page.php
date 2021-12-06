<?php

namespace App\Entity;

use App\Repository\PageRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=PageRepository::class)
 */
class Page
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $page_id;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $page_name;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $workspace_id;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $stylesheet;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPageId(): ?string
    {
        return $this->page_id;
    }

    public function setPageId(string $page_id): self
    {
        $this->page_id = $page_id;

        return $this;
    }

    public function getPageName(): ?string
    {
        return $this->page_name;
    }

    public function setPageName(?string $page_name): self
    {
        $this->page_name = $page_name;

        return $this;
    }

    public function getWorkspaceId(): ?string
    {
        return $this->workspace_id;
    }

    public function setWorkspaceId(string $workspace_id): self
    {
        $this->workspace_id = $workspace_id;

        return $this;
    }

    public function getStylesheet(): ?string
    {
        return $this->stylesheet;
    }

    public function setStylesheet(?string $stylesheet): self
    {
        $this->stylesheet = $stylesheet;

        return $this;
    }
}
