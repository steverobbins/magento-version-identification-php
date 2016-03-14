<?php
/**
 * Magento Version Identification
 *
 * PHP version 5
 *
 * @author    Steve Robbins <steve@steverobbins.com>
 * @copyright 2015 Steve Robbins
 * @license   http://creativecommons.org/licenses/by/4.0/ CC BY 4.0
 * @link      https://github.com/steverobbins/magento-version-identification-php
 */

namespace Mvi\Command;

use Mvi\Command\MviCommand;
use Mvi\Check;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Checks the edition and version of a URL
 */
class CheckCommand extends MviCommand
{
    /**
     * Configure check command
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('check')
            ->setDescription('Check site\'s Magento version')
            ->addArgument(
                'url',
                InputArgument::REQUIRED,
                'The URL of the Magento application'
            );
    }

    /**
     * Run check command
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $checker = new Check($input->getArgument('url'));
        $info    = $checker->getInfo();
        if ($info === false) {
            $output->writeln('<error>Unable to retrieve Magento information</error>');
            return;
        }
        $i = 0;
        foreach ($info as $edition => $versions) {
            $output->writeln(sprintf('Edition: <info>%s</info>', $edition));
            $output->writeln(sprintf('Version: <info>%s</info>', implode(', ', $versions)));
            if ($i++ > 0) {
                $output->writeln('OR');
            }
        }
    }
}
