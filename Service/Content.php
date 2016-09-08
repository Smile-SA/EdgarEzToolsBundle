<?php

namespace EdgarEz\ToolsBundle\Service;

use eZ\Publish\API\Repository\ContentService;
use eZ\Publish\API\Repository\ContentTypeService;
use eZ\Publish\API\Repository\Exceptions\BadStateException;
use eZ\Publish\API\Repository\Exceptions\ContentFieldValidationException;
use eZ\Publish\API\Repository\Exceptions\ContentValidationException;
use eZ\Publish\API\Repository\Exceptions\InvalidArgumentException;
use eZ\Publish\API\Repository\Exceptions\NotFoundException;
use eZ\Publish\API\Repository\Exceptions\UnauthorizedException;
use eZ\Publish\API\Repository\LanguageService;
use eZ\Publish\API\Repository\LocationService;
use eZ\Publish\API\Repository\Repository;
use eZ\Publish\Core\FieldType\Checkbox\Value;

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
        /** @var LocationService $locationService LocationService */
        $locationService = $this->repository->getLocationService();
        /** @var LanguageService $languageService */
        $languageService = $this->repository->getContentLanguageService();

        try {
            $contentType = $contentTypeService->loadContentTypeByIdentifier($struct['contentTypeIdentifier']);
            $contentCreateStruct = $contentService->newContentCreateStruct($contentType, $struct['languageCode']);

            foreach ($struct['fields'] as $field) {
                $contentCreateStruct->setField($field['identifier'], $field['value']);
            }

            $locationCreateStruct = $locationService->newLocationCreateStruct($struct['parentLocationID']);
            $draft = $contentService->createContent($contentCreateStruct, array($locationCreateStruct));

            $contentAdded = $contentService->publishVersion($draft->versionInfo);

            $languages = $languageService->loadLanguages();
            foreach ($languages as $language) {
                $newLanguageCode = $language->languageCode;
                if (!$language->enabled || $newLanguageCode == $struct['languageCode']) {
                    continue;
                }

                $newVersionInfo = $contentService->createContentDraft($contentAdded->contentInfo)->getVersionInfo();

                $contentUpdateStruct = $contentService->newContentUpdateStruct();
                foreach ($contentAdded->getFields() as $key => $field) {
                    $contentUpdateStruct->setField($field->fieldDefIdentifier, $field->value, $newLanguageCode);
                }
                $contentUpdateStruct->initialLanguageCode = $newLanguageCode;
                $contentDraft = $contentService->updateContent($newVersionInfo, $contentUpdateStruct);
                $contentService->publishVersion($contentDraft->versionInfo);
            }

            return $contentAdded;
        } catch (NotFoundException $e) {
            throw new \RuntimeException($e->getMessage());
        } catch (UnauthorizedException $e) {
            throw new \RuntimeException($e->getMessage());
        } catch (InvalidArgumentException $e) {
            throw new \RuntimeException($e->getMessage());
        } catch (ContentFieldValidationException $e) {
            throw new \RuntimeException($e->getMessage());
        } catch (ContentValidationException $e) {
            throw new \RuntimeException($e->getMessage());
        } catch (BadStateException $e) {
            throw new \RuntimeException($e->getMessage());
        }
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

        try {
            $fromLocation = $locationService->loadLocation($fromLocationID);
            $toLocation = $locationService->loadLocation($toLocationID);

            $newLocation = $locationService->copySubtree($fromLocation, $toLocation);

            if ($name) {
                $contentInfo = $newLocation->getContentInfo();
                $contentDraft = $contentService->createContentDraft($contentInfo);
                $contentUpdateStruct = $contentService->newContentUpdateStruct();

                $fields = $contentDraft->fields;
                foreach ($fields as $key => $field) {
                    switch ($key) {
                        case 'title':
                            $contentUpdateStruct->setField('title', $name);
                            break;
                        case 'activated':
                            $contentUpdateStruct->setField('activated', new Value(false));
                            break;
                        default:
                            break;
                    }
                }

                $contentUpdateStruct->initialLanguageCode = $contentInfo->mainLanguageCode;
                $contentDraft = $contentService->updateContent($contentDraft->versionInfo, $contentUpdateStruct);
                $contentService->publishVersion($contentDraft->versionInfo);
            }

            return $newLocation->getContentInfo()->mainLocationId;
        } catch (UnauthorizedException $e) {
            throw new \RuntimeException($e->getMessage());
        } catch (NotFoundException $e) {
            throw new \RuntimeException($e->getMessage());
        } catch (InvalidArgumentException $e) {
            throw new \RuntimeException($e->getMessage());
        } catch (BadStateException $e) {
            throw new \RuntimeException($e->getMessage());
        } catch (ContentFieldValidationException $e) {
            throw new \RuntimeException($e->getMessage());
        } catch (ContentValidationException $e) {
            throw new \RuntimeException($e->getMessage());
        }
    }
}
