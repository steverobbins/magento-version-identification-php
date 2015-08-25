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
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Generates useful md5 hashes of files in release/
 */
class GenerateCommand extends MviCommand
{
    const DIR_RELEASE = 'release';

    /**
     * Folders that have files whos has is of use
     *
     * @var string[]
     */
    protected $hashFolders = ['js', 'media', 'skin'];

    /**
     * Configure generate command
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('generate')
            ->setDescription('Generate MD5 hashes from locally stored releases');
    }

    /**
     * Run generate command
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $releases = $this->getReleases();
        $releaseCount = count($releases);
        if ($releaseCount === 0) {
            $output->writeln('<error>No releases were found</error>');
        }
        $output->writeln(sprintf('Found <info>%d</info> release(s)', $releaseCount));
        $progressBar = new ProgressBar($output, $releaseCount);
        $progressBar->setFormat('[%bar%] %percent%% %message%');
        $progressBar->setMessage('Processing...');
        $progressBar->start();
        foreach ($releases as $release) {
            $progressBar->setMessage($release);
            if (false === $this->generate($release)) {
                $output->writeln('<error>Failed to generate hashes</error>');
            }
            $progressBar->advance();
        }
        $progressBar->setMessage('Done');
        $progressBar->finish();
        $output->writeln('');
        $output->writeln('<info>Generation complete</info>');
    }

    /**
     * Get list of release folders
     *
     * @return string[]
     */
    protected function getReleases()
    {
        $names = [];
        foreach (glob($this->baseDir . DS . self::DIR_RELEASE . DS . '*') as $name) {
            $name = explode(DS, $name);
            $names[] = $name[count($name) - 1];
        }
        return $names;
    }

    /**
     * Generate the the list of hashes for this release
     *
     * @param string $release
     *
     * @return integer|boolean
     */
    protected function generate($release)
    {
        $hashes = [];
        foreach ($this->hashFolders as $folder) {
            $files = $this->getFiles($release, $folder);
            foreach ($files as $file) {
                $hashes[] = $this->getHash($release, $file);
            }
        }
        return file_put_contents(
            $this->baseDir . DS . self::DIR_MD5 . DS . $release,
            implode("\n", array_merge($hashes, array('')))
        );
    }

    /**
     * Gets all files in a specified folder of a release
     *
     * @param string $release
     * @param string $folder
     *
     * @return string[]
     */
    protected function getFiles($release, $folder)
    {
        $path      = $this->baseDir . DS . self::DIR_RELEASE . DS . $release . DS;
        $directory = new \RecursiveDirectoryIterator($path . $folder . DS);
        $iterator  = new \RecursiveIteratorIterator($directory);
        $matches   = [];
        foreach ($iterator as $file) {
            if (in_array($file->getFilename(), array('.', '..'))) {
                continue;
            }
            $matches[] = str_replace($path, '', (string) $file);
        }
        return $matches;
    }

    /**
     * Gets the md5 has of a file in the release
     *
     * @param string $release
     * @param string $file
     *
     * @return string
     */
    protected function getHash($release, $file)
    {
        return md5(file_get_contents($this->baseDir . DS . self::DIR_RELEASE . DS . $release . DS . $file))
            . ' ' . $file;
    }
}
