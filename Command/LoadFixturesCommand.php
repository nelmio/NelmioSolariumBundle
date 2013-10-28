<?php

namespace Nelmio\SolariumBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Baldur Rensch <brensch@gmail.com>
 */
class LoadFixturesCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('nelmio:solarium:fixtures')
            ->setDescription('Loads Solarium fixtures')
            ->addOption(
                'fixtures',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'The directory or file to load data fixtures from.'
            )
            ->addOption(
                'append',
                null,
                InputOption::VALUE_NONE,
                'Append the data fixtures instead of deleting all data from the database first.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $append = $input->getOption('append');

        $dirOrFile = $input->getOption('fixtures');
        if ($dirOrFile) {
            $paths = is_array($dirOrFile) ? $dirOrFile : array($dirOrFile);
        } else {
            $paths = array();
            foreach ($this->getApplication()->getKernel()->getBundles() as $bundle) {
                $dirName = $bundle->getPath().'/DataFixtures/Solarium';

                if (is_dir($dirName)) {
                    $output->writeln(sprintf('  <comment>></comment> <info>%s</info>', $dirName));
                    $paths[] = $dirName;
                }
            }
        }

        /** @var \Solarium\Support\DataFixtures\Loader $loader */
        $loader = $this->getContainer()->get('solarium.fixtures.loader');
        foreach ($paths as $path) {
            $loader->loadFromDirectory($path);
        }

        if (!$append) {
            $output->writeln('<comment>  > Purging Solr</comment>');
            $purger = $this->getContainer()->get('solarium.fixtures.purger.default');
            $purger->purge();
        }

        /** @var $executor \Solarium\Support\DataFixtures\Executor */
        $executor = $this->getContainer()->get('solarium.fixtures.executor.default');
        $executor->execute($loader->getFixtures());
    }
} 

