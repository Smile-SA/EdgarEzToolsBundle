<?php
/**
 * Created by PhpStorm.
 * User: emdro
 * Date: 03/08/2016
 * Time: 08:46
 */

namespace EdgarEz\ToolsBundle\Command\ContentTypeGroup;


use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class EdgarEzContentTypeGroupGenerateCommand extends ContainerAwareCommand
{
    /**
     * Configure ContentTypeGroup generate command
     */
    protected function configure()
    {
        $this
            ->setName('edgarez:tools:contenttypegroup:generate')
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
    }
}