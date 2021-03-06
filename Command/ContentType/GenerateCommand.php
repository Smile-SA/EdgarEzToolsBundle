<?php

namespace Smile\EzToolsBundle\Command\ContentType;

use Smile\EzToolsBundle\Service\ContentType;
use eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Configuration\ConfigResolver;
use eZ\Publish\API\Repository\ContentTypeService;
use eZ\Publish\API\Repository\Exceptions\ForbiddenException;
use eZ\Publish\API\Repository\Exceptions\NotFoundException;
use eZ\Publish\API\Repository\Exceptions\UnauthorizedException;
use eZ\Publish\API\Repository\LanguageService;
use eZ\Publish\API\Repository\Repository;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

class GenerateCommand extends ContainerAwareCommand
{
    /** @var $input InputInterface */
    private $input;

    /** @var $output OutputInterface */
    private $output;

    /**
     * Configure ContentType generate command
     */
    protected function configure()
    {
        $this
            ->setName('smile:tools:contenttype:generate')
            ->setDescription('Generate ContentType');
    }

    /**
     * Execute ContentType generate command
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

        /** @var $contentTypeService ContentTypeService */
        $contentTypeService = $repository->getContentTypeService();
        /** @var $contentLanguageService LanguageService */
        $contentLanguageService = $repository->getContentLanguageService();

        /** @var $questionHelper QuestionHelper */
        $questionHelper = $this->getHelper('question');

        $struct = array();

        $struct['contentTypeGroup'] = $this->getContentTypeGroup($contentTypeService, $questionHelper);
        $struct['contentTypeIdentifier'] = $this->getContentTypeIdentifier($questionHelper);
        $struct['contentTypeName'] = $this->getContentTypeName($questionHelper);
        $struct['contentTypeMainLanguage'] = $this->getContentTypeMainLanguage($contentLanguageService, $questionHelper);
        $struct['contentTypeNameSchema'] = $this->getContentTypeNameSchema($questionHelper);
        $struct['fields'] = array();

        while (true) {
            $struct['fields'][] = $this->getContentTypeField($questionHelper, $struct['contentTypeMainLanguage']);
            $question = new ConfirmationQuestion(
                '<question>Do you want to add new field?</question> ',
                false
            );

            if (!$questionHelper->ask($input, $output, $question)) {
                $output->writeln('');
                break;
            }
        }

        /** @var $configResolver ConfigResolver */
        $configResolver = $this->getContainer()->get('ezpublish.config.resolver');
        $adminID = $configResolver->getParameter('adminid', 'smile_ez_tools');

        $contentTypeService = new ContentType($repository);
        $contentTypeService->setAdminID($adminID);
        try {
            $contentTypeService->add($struct);
            $output->writeln("<info>Content type created</info>");
        } catch (UnauthorizedException $e) {
            $output->writeln("<error>" . $e->getMessage() . "</error>");
        } catch (ForbiddenException $e ) {
            $output->writeln("<error>" . $e->getMessage() . "</error>");
        }
    }

    protected function getContentTypeGroup(ContentTypeService $contentTypeService, QuestionHelper $questionHelper)
    {
        $question = new Question('Content type group identifier where your content type will be registered: ');

        $contentTypeGroup = false;
        while (!$contentTypeGroup) {
            $contentTypeGroupIdentifier = $questionHelper->ask($this->input, $this->output, $question);

            try {
                $contentTypeGroup = $contentTypeService->loadContentTypeGroupByIdentifier($contentTypeGroupIdentifier);
            } catch (NotFoundException $e) {
                $this->output->writeln("<error>Content type group identifier should be a valid content type group identifier : " . $e->getMessage() . "</error>");
                $contentTypeGroup = false;
            }
        }

        return $contentTypeGroup;
    }

    protected function getContentTypeIdentifier(QuestionHelper $questionHelper)
    {
        $question = new Question('Content type identifier: ');
        $question->setValidator(
            array(
                'Smile\EzToolsBundle\Command\ContentType\Validators',
                'validateContentTypeIdentifier'
            )
        );

        $contentTypeIdentifier = false;
        while(!$contentTypeIdentifier) {
            $contentTypeIdentifier = $questionHelper->ask($this->input, $this->output, $question);

            if (!$contentTypeIdentifier || empty($contentTypeIdentifier)) {
                $this->output->writeln("<error>Content type identifier should only contains letters or underscore lowercase</error>");
            }
        }

        return $contentTypeIdentifier;
    }

    protected function getContentTypeName(QuestionHelper $questionHelper)
    {
        $question = new Question('Content type name: ');
        $question->setValidator(
            array(
                'Smile\EzToolsBundle\Command\ContentType\Validators',
                'validateContentTypeName'
            )
        );

        $contentTypeName = false;
        while(!$contentTypeName) {
            $contentTypeName = $questionHelper->ask($this->input, $this->output, $question);

            if (!$contentTypeName || empty($contentTypeName)) {
                $this->output->writeln("<error>Content type name should only contains letters, numeric or space</error>");
            }
        }

        return $contentTypeName;
    }

    protected function getContentTypeMainLanguage(LanguageService $contentLanguageService, QuestionHelper $questionHelper)
    {
        $languages = $contentLanguageService->loadLanguages();
        $question = new Question('Content type main language code (example eng-GB): ');

        $contentTypeMainLanguage = false;
        while (!$contentTypeMainLanguage) {
            $contentTypeMainLanguage = $questionHelper->ask($this->input, $this->output, $question);

            $match = false;
            foreach ($languages as $language) {
                if ($language->languageCode == $contentTypeMainLanguage) {
                    $match = true;
                    break;
                }
            }

            if (!$match) {
                $contentTypeMainLanguage = false;
                $this->output->writeln("<error>Language code is not valid</error>");
            }
        }

        return $contentTypeMainLanguage;
    }

    protected function getContentTypeNameSchema(QuestionHelper $questionHelper)
    {
        $question = new Question('Content type field identifier, used to define name schema: ');
        $question->setValidator(
            array(
                'Smile\EzToolsBundle\Command\ContentType\Validators',
                'validateFieldIdentifier'
            )
        );

        $fieldIdentifier = false;
        while(!$fieldIdentifier) {
            $fieldIdentifier = $questionHelper->ask($this->input, $this->output, $question);

            if (!$fieldIdentifier || empty($fieldIdentifier)) {
                $this->output->writeln("<error>Field identifier is not valid</error>");
            }
        }

        return $fieldIdentifier;
    }

    protected function getContentTypeField(QuestionHelper $questionHelper, $mainLanguage)
    {
        $field = array(
            'identifier' => null,
            'type' => null,
            'name' => null,
            'mainLanguage' => $mainLanguage,
            'isTranslatable' => true,
            'isRequired' => true,
            'isSearchable' => true
        );

        $question = new Question('New field identifier: ');
        $question->setValidator(
            array(
                'Smile\EzToolsBundle\Command\ContentType\Validators',
                'validateFieldIdentifier'
            )
        );

        $fieldIdentifier = false;
        while (!$fieldIdentifier) {
            $fieldIdentifier = $questionHelper->ask($this->input, $this->output, $question);

            if (!$fieldIdentifier || empty($fieldIdentifier)) {
                $this->output->writeln("<error>Field identifier is not valid</error>");
            }
        }
        $field['identifier'] = $fieldIdentifier;

        $question = new Question('Field type: ');

        $fieldType = false;
        while(!$fieldType) {
            $fieldType = $questionHelper->ask($this->input, $this->output, $question);

            if (!$fieldType || empty($fieldType)) {
                $this->output->writeln("<error>Field type is not valid</error>");
            }
        }
        $field['type'] = $fieldType;

        $question = new Question('Field name: ');
        $question->setValidator(
            array(
                'Smile\EzToolsBundle\Command\ContentType\Validators',
                'validateFieldName'
            )
        );

        $fieldName = false;
        while(!$fieldName) {
            $fieldName = $questionHelper->ask($this->input, $this->output, $question);

            if (!$fieldName || empty($fieldName)) {
                $this->output->writeln("<error>Field name is not valid</error>");
            }
        }
        $field['name'] = $fieldName;

        return $field;
    }
}
