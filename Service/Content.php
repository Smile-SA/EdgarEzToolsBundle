<?php
/**
 * Created by PhpStorm.
 * User: emdro
 * Date: 03/08/2016
 * Time: 08:54
 */

namespace EdgarEz\ToolsBundle\Service;


use eZ\Publish\API\Repository\ContentService;
use eZ\Publish\API\Repository\ContentTypeService;
use eZ\Publish\API\Repository\LocationService;
use eZ\Publish\API\Repository\Repository;

class Content
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

    public function add(array $struct)
    {
        $this->repository->setCurrentUser($this->repository->getUserService()->loadUser($this->adminID));

        /** @var $contentTypeService ContentTypeService */
        $contentTypeService = $this->repository->getContentTypeService();
        /** @var $contentService ContentService */
        $contentService = $this->repository->getContentService();
        /** @var $locationService LocationService */
        $locationService = $this->repository->getLocationService();

        $contentType = $contentTypeService->loadContentTypeByIdentifier($struct['contentTypeIdentifier']);
        $contentCreateStruct = $contentService->newContentCreateStruct($contentType, $struct['languageCode']);

        foreach ($struct['fields'] as $field) {
            $contentCreateStruct->setField($field['identifier'], $field['value']);
        }

        $locationCreateStruct = $locationService->newLocationCreateStruct($struct['parentLocationID']);
        $draft = $contentService->createContent($contentCreateStruct, array($locationCreateStruct));
        return $contentService->publishVersion($draft->versionInfo);
    }

    /**
     * Copy content subtree
     *
     * @param int $fromLocationID location ID source
     * @param int $toLocationID location ID dest
     * @param bool|string $name
     * @return int new root Location ID subtree
     */
    public function copySubtree($fromLocationID, $toLocationID, $name = false)
    {
        $this->repository->setCurrentUser($this->repository->getUserService()->loadUser($this->adminID));

        /** @var $locationService LocationService */
        $locationService = $this->repository->getLocationService();
        /** @var ContentService $contentService */
        $contentService = $this->repository->getContentService();

        $fromLocation = $locationService->loadLocation($fromLocationID);
        $toLocation = $locationService->loadLocation($toLocationID);

        $newLocation = $locationService->copySubtree($fromLocation, $toLocation);

        if ($name) {
            $contentInfo = $newLocation->getContentInfo();
            $contentDraft = $contentService->createContentDraft($contentInfo);
            $contentUpdateStruct = $contentService->newContentUpdateStruct();
            $contentUpdateStruct->initialLanguageCode = $contentInfo->mainLanguageCode;
            $contentUpdateStruct->setField('title', $name);
            $contentDraft = $contentService->updateContent($contentDraft->versionInfo, $contentUpdateStruct);
            $contentService->publishVersion($contentDraft->versionInfo);
        }

        return $newLocation->getContentInfo()->mainLocationId;
    }
}
