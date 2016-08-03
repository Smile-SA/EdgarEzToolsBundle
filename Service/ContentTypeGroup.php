<?php
/**
 * Created by PhpStorm.
 * User: emdro
 * Date: 03/08/2016
 * Time: 08:54
 */

namespace EdgarEz\ToolsBundle\Service;


use eZ\Publish\API\Repository\Repository;

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

    public function add($name)
    {
        $this->repository->setCurrentUser($this->repository->getUserService()->loadUser($this->adminID));
        $contentTypeService = $this->repository->getContentTypeService();

        $contentTypeGroupStruct = $contentTypeService->newContentTypeGroupCreateStruct($name);

        return $contentTypeService->createContentTypeGroup($contentTypeGroupStruct);
    }
}