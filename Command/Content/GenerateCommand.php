<?php

namespace Smile\EzToolsBundle\Command\Content;

use Smile\EzToolsBundle\Service\Content;
use eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Configuration\ConfigResolver;
use eZ\Publish\API\Repository\ContentService;
use eZ\Publish\API\Repository\ContentTypeService;
use eZ\Publish\API\Repository\Exceptions\ForbiddenException;
use eZ\Publish\API\Repository\Exceptions\NotFoundException;
use eZ\Publish\API\Repository\Exceptions\UnauthorizedException;
use eZ\Publish\API\Repository\LocationService;
use eZ\Publish\API\Repository\Repository;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class GenerateCommand extends ContainerAwareCommand
{
    /** @var $input InputInterface */
    private $input;

    /** @var $output OutputInterface */
    private $output;

    /**
     * Configure ContentTypeGroup generate command
     */
    protected function configure()
    {
        $this
            ->setName('smile:tools:content:generate')
            ->setDescription('Generate Content');
    }

    /**
     * Execute Content generate command
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;

        /** @var $repository Repository */
        $repository = $this->getContainer()->get('ezpublish.api.repository');

        /** @var $contentService ContentService */
        $contentService = $repository->getContentService();
        /** @var $contentTypeService ContentTypeService*/
        $contentTypeService = $repository->getContentTypeService();
        /** @var $locationService LocationService */
        $locationService = $repository->getLocationService();

        /** @var $questionHelper QuestionHelper */
        $questionHelper = $this->getHelper('question');

        $struct = array();

        $struct['parentLocationID'] = $this->getParentLocationID($locationService, $questionHelper);
        $struct['contentTypeIdentifier'] = $this->getContentTypeIdentifier($contentTypeService, $questionHelper);
        $contentType = $contentTypeService->loadContentTypeByIdentifier($struct['contentTypeIdentifier']);
        $languageCode = $contentType->mainLanguageCode;
        $struct['languageCode'] = $languageCode;

        $struct['fields'] = array();
        $fields = $contentType->getFieldDefinitions();
        foreach ($fields as $field) {
            $fieldName = $field->getName($languageCode);
            $fieldIdentifier = $field->identifier;

            $fieldValue = $this->getFieldValue($questionHelper, $fieldName);
            $struct['fields'][] = array(
                'identifier' => $fieldIdentifier,
                'value' => $fieldValue
            );
        }

        /** @var $configResolver ConfigResolver */
        $configResolver = $this->getContainer()->get('ezpublish.config.resolver');
        $adminID = $this->getContainer()->getParameter('smile_ez_tools.adminid');

        $smileEzContentService = new Content($repository);
        $smileEzContentService->setAdminID($adminID);
        try {
            $smileEzContentService->add($struct);
            $output->writeln("<info>Content created</info>");
        } catch (UnauthorizedException $e) {
            $output->writeln("<error>" . $e->getMessage() . "</error>");
        } catch (ForbiddenException $e ) {
            $output->writeln("<error>" . $e->getMessage() . "</error>");
        }
    }

    protected function getParentLocationID(LocationService $locationService, QuestionHelper $questionHelper)
    {
        $question = new Question('Parentlocation ID where your content will be registered: ');
        $question->setValidator(
            array(
                'Smile\EzToolsBundle\Command\Content\Validators',
                'validateLocationID'
            )
        );

        $parentLocationID = false;
        while(!$parentLocationID) {
            $parentLocationID = $questionHelper->ask($this->input, $this->output, $question);

            try {
                $locationService->loadLocation($parentLocationID);
                if (!$parentLocationID || empty($parentLocationID)) {
                    $this->output->writeln("<error>Parent Location ID is not valid</error>");
                }
            } catch (NotFoundException $e) {
                $this->output->writeln("<error>No location found with id $parentLocationID</error>");
                $parentLocationID = false;
            }
        }

        return $parentLocationID;
    }

    protected function getContentTypeIdentifier(ContentTypeService $contentTypeService, QuestionHelper $questionHelper)
    {
        $question = new Question('Content type identifier used to create your content: ');
        $question->setValidator(
            array(
                'Smile\EzToolsBundle\Command\Content\Validators',
                'validateIdentifier'
            )
        );

        $contentTypeIdentifier = false;
        while(!$contentTypeIdentifier) {
            $contentTypeIdentifier = $questionHelper->ask($this->input, $this->output, $question);

            try {
                $contentTypeService->loadContentTypeByIdentifier($contentTypeIdentifier);
                if (!$contentTypeIdentifier || empty($contentTypeIdentifier)) {
                    $this->output->writeln("<error>Content type identifier is not valid</error>");
                }
            } catch (NotFoundException $e) {
                $this->output->writeln("<error>No content type found with id $contentTypeIdentifier</error>");
                $contentTypeIdentifier = false;
            }
        }

        return $contentTypeIdentifier;
    }

    protected function getFieldValue(QuestionHelper $questionHelper, $fieldName)
    {
        $question = new Question("Field '$fieldName' value: ");

        $fieldValue = false;
        while(!$fieldValue) {
            $fieldValue = $questionHelper->ask($this->input, $this->output, $question);

            if (!$fieldValue || empty($fieldValue)) {
                $this->output->writeln("<error>Field value should not be empty</error>");
            }
        }

        return $fieldValue;
    }
}
