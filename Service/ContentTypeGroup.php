<?php

namespace Smile\EzToolsBundle\Service;

use eZ\Publish\API\Repository\Exceptions\InvalidArgumentException;
use eZ\Publish\API\Repository\Exceptions\UnauthorizedException;
use eZ\Publish\API\Repository\Repository;
use Symfony\Component\Console\Exception\RuntimeException;

class ContentTypeGroup
{
    private $repository;

    /**
     * @var int admin user id
     */
    private $adminID;

    /**
     * adminID setter
     *
     * @param int $adminID
     */
    public function setAdminID($adminID)
    {
        $this->adminID = $adminID;
    }

    public function __construct(Repository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Create eZ ContentTypeGroup
     *
     * @param string $name ContentTypeGroup name
     * @return \eZ\Publish\API\Repository\Values\ContentType\ContentTypeGroup|RuntimeException
     */
    public function add($name)
    {
        $this->repository->setCurrentUser($this->repository->getUserService()->loadUser($this->adminID));
        $contentTypeService = $this->repository->getContentTypeService();

        $contentTypeGroupStruct = $contentTypeService->newContentTypeGroupCreateStruct($name);

        try {
            $contentTypeGroup = $contentTypeService->createContentTypeGroup($contentTypeGroupStruct);
            return $contentTypeGroup;
        } catch (UnauthorizedException $e) {
            throw new \RuntimeException($e->getMessage());
        } catch (InvalidArgumentException $e) {
            throw new \RuntimeException($e->getMessage());
        }
    }
}
