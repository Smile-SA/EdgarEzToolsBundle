<?php

namespace Smile\EzToolsBundle\Command\ContentTypeGroup;

use Smile\EzToolsBundle\Service\ContentTypeGroup;
use eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Configuration\ConfigResolver;
use eZ\Publish\API\Repository\Exceptions\ForbiddenException;
use eZ\Publish\API\Repository\Exceptions\UnauthorizedException;
use eZ\Publish\API\Repository\Repository;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class GenerateCommand extends ContainerAwareCommand
{
    /**
     * Configure ContentTypeGroup generate command
     */
    protected function configure()
    {
        $this
            ->setName('smile:tools:contenttypegroup:generate')
            ->setDescription('Generate ContentTypeGroup');
    }

    /**
     * Execute ContentTypeGroup generate command
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var $repository Repository */
        $repository = $this->getContainer()->get('ezpublish.api.repository');

        /** @var $questionHelper QuestionHelper */
        $questionHelper = $this->getHelper('question');

        $question = new Question('Content type group name: ');
        $question->setValidator(
            array(
                'Smile\EzToolsBundle\Command\ContentTypeGroup\Validators',
                'validateContentTypeGroupName'
            )
        );

        $contentTypeGroupName = false;
        while(!$contentTypeGroupName) {
            $contentTypeGroupName = $questionHelper->ask($input, $output, $question);

            if (!$contentTypeGroupName || empty($contentTypeGroupName)) {
                $output->writeln("<error>Content type group name should only contains numbers, letters or space</error>");
            }
        }

        /** @var $configResolver ConfigResolver */
        $configResolver = $this->getContainer()->get('ezpublish.config.resolver');
        $adminID = $configResolver->getParameter('adminid', 'smile_ez_tools');

        $contentTypeGroupService = new ContentTypeGroup($repository);
        $contentTypeGroupService->setAdminID($adminID);
        try {
            $contentTypeGroupService->add($contentTypeGroupName);
            $output->writeln( "<info>Content type group created '$contentTypeGroupName'<info>");
        } catch( UnauthorizedException $e) {
            $output->writeln( "<error>" . $e->getMessage() . "</error>" );
        } catch (ForbiddenException $e ) {
            $output->writeln( "<error>" . $e->getMessage() . "</error>" );
        }
    }
}
