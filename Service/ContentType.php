<?php
/**
 * Created by PhpStorm.
 * User: emdro
 * Date: 03/08/2016
 * Time: 13:23
 */

namespace EdgarEz\ToolsBundle\Service;


use eZ\Publish\API\Repository\ContentTypeService;
use eZ\Publish\API\Repository\Repository;

class ContentType
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

        $contentTypeCreateStruct = $contentTypeService->newContentTypeCreateStruct($struct['contentTypeIdentifier']);
        $contentTypeCreateStruct->mainLanguageCode = $struct['contentTypeMainLanguage'];
        $contentTypeCreateStruct->nameSchema = '<' . $struct['contentTypeNameSchema'] . '>';
        // set names for the content type
        $contentTypeCreateStruct->names = array(
            $struct['contentTypeMainLanguage'] => $struct['contentTypeName']
        );

        for ($i = 0; $i < count($struct['attributes']); $i++) {
            $attribute = $struct['attributes'][$i];
            $fieldCreateStruct = $this->addAttribute($contentTypeService, $attribute, $i * 10);
            $contentTypeCreateStruct->addFieldDefinition($fieldCreateStruct);
        }

        $contentTypeDraft = $contentTypeService->createContentType($contentTypeCreateStruct, array($struct['contentTypeGroup']) );
        $contentTypeService->publishContentTypeDraft($contentTypeDraft);
    }

    private function addAttribute(ContentTypeService $contentTypeService, array $attribute, $pos = 10)
    {
        $fieldCreateStruct = $contentTypeService->newFieldDefinitionCreateStruct($attribute['identifier'], $attribute['type']);
        $fieldCreateStruct->names = array($attribute['mainLanguage'] => $attribute['name']);
        $fieldCreateStruct->fieldGroup = 'content';
        $fieldCreateStruct->position = $pos;
        $fieldCreateStruct->isTranslatable = $attribute['isTranslatable'];
        $fieldCreateStruct->isRequired = $attribute['isRequired'];
        $fieldCreateStruct->isSearchable = $attribute['isSearchable'];

        return $fieldCreateStruct;
    }
}