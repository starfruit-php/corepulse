<?php

namespace CorepulseBundle\Model;

use Pimcore;
use Pimcore\Model\AbstractModel;
use Pimcore\Model\Exception\NotFoundException;
use Pimcore\Model\Element;

class Indexing extends AbstractModel
{
    public ?int $id = null;

    public ?string $url = null;

    public ?string $type = null;

    public ?string $time = null;

    public ?string $response = null;

    public ?string $createAt = null;

    public ?string $updateAt = null;

    public function getClass()
    {
        return 'CorepulseIndexing';
    }

    /**
     * get score by id
     */
    public static function getById(int $id): ?self
    {
        try {
            $obj = new self;
            $obj->getDao()->getById($id);
            return $obj;
        }
        catch (NotFoundException $ex) {
            \Pimcore\Logger::warn("Notification with id $id not found");
        }

        return null;
    }

    public static function getByUrl(string $url): ?self
    {
        try {
            $obj = new self;
            $obj->getDao()->getByUrl($url);
            return $obj;
        } catch (NotFoundException $ex) {
            \Pimcore\Logger::warn("indexing with url $url not found");
        }

        return null;
    }

    public static function getByType(string $type): ?self
    {
        try {
            $obj = new self;
            $obj->getDao()->getByType($type);
            return $obj;
        } catch (NotFoundException $ex) {
            \Pimcore\Logger::warn("indexing with type $type not found");
        }

        return null;
    }

    public static function getByResponse(string $response): ?self
    {
        try {
            $obj = new self;
            $obj->getDao()->getByType($response);
            return $obj;
        } catch (NotFoundException $ex) {
            \Pimcore\Logger::warn("indexing with response $response not found");
        }

        return null;
    }

    public function setUrl(?string $url): void
    {
        $this->url = $url;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setResponse(?string $response): void
    {
        $this->response = $response;
    }

    public function getResponse(): ?string
    {
        return $this->response;
    }

    public function setType(?string $type): void
    {
        $this->type = $type;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setTime(?string $time): void
    {
        $this->time = $time;
    }

    public function getTime(): ?string
    {
        return $this->time;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setCreateAt(string $createAt): void
    {
        $this->createAt = $createAt;
    }

    public function getCreateAt(): ?string
    {
        return $this->createAt;
    }

    public function setUpdateAt(string $updateAt): void
    {
        $this->updateAt = $updateAt;
    }

    public function getUpdateAt(): ?string
    {
        return $this->updateAt;
    }

    public function getDataJson(): array
    {
        $result = [
            'id' => $this->getId(),
            'url' => $this->getUrl(),
            'createAt' => $this->getCreateAt(),
            'updateAt' => $this->getUpdateAt(),
        ];

        $data = $this->getData() ? json_decode($this->getData(), true): [];

        foreach ($data as $key => $value) {
            $result[$key] = $value;
        }

        return $result;
    }
}
