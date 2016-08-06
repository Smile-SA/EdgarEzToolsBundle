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
        $contentTypeCreateStruct->isContainer = (isset($struct['isContainer'])) ? $struct['isContainer'] : false;
        // set names for the content type
        $contentTypeCreateStruct->names = array(
            $struct['contentTypeMainLanguage'] => $struct['contentTypeName']
        );

        $i = 1;
        foreach ($struct['fields'] as $field) {
            $fieldCreateStruct = $this->addField($contentTypeService, $field, $i * 10);
            $contentTypeCreateStruct->addFieldDefinition($fieldCreateStruct);
            $i++;
        }

        $contentTypeDraft = $contentTypeService->createContentType($contentTypeCreateStruct, array($struct['contentTypeGroup']) );
        return $contentTypeService->publishContentTypeDraft($contentTypeDraft);
    }

    private function addField(ContentTypeService $contentTypeService, array $field, $pos = 10)
    {
        $fieldCreateStruct = $contentTypeService->newFieldDefinitionCreateStruct($field['identifier'], $field['type']);
        $fieldCreateStruct->names = array($field['mainLanguage'] => $field['name']);
        $fieldCreateStruct->fieldGroup = 'content';
        $fieldCreateStruct->position = $pos;
        $fieldCreateStruct->isTranslatable = (isset($field['isTranslatable'])) ? $field['isTranslatable'] : true;
        $fieldCreateStruct->isRequired = (isset($field['isRequired'])) ? $field['isRequired'] : true;
        $fieldCreateStruct->isSearchable = (isset($field['isSearchable'])) ? $field['isSearchable'] : true;

        return $fieldCreateStruct;
    }
}
