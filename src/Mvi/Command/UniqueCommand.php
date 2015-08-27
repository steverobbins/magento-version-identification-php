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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Finds unique md5 hashes
 */
class UniqueCommand extends MviCommand
{
    const VERSION_DESTINATION = 'version.json';

    /**
     * Configure generate command
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('unique')
            ->setDescription('Find unique md5 hashes and save to file');
    }

    /**
     * Run unique command
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $data           = $this->collectData();
        $fileHashCounts = $this->buildFileHashCounts($data);
        $fingerprints   = [];
        while (count($data) > 0) {
            $file = key($fileHashCounts);
            $versionsWithThisFile = [];
            foreach ($data as $release => $value) {
                $fileHash = array_flip($value);
                if (isset($fileHash[$file])) {
                    if (!isset($fingerprints[$file])) {
                        $fingerprints[$file] = [];
                    }
                    $this->prepareReleaseName($release, $fingerprints[$file][$fileHash[$file]]);
                    $output->writeln(sprintf(
                        '<info>%s</info> can be identified by <info>%s</info> with hash <info>%s</info>',
                        $release,
                        $file,
                        $fileHash[$file]
                    ));
                    unset($data[$release]);
                }
            }
            unset($fileHashCounts[$file]);
            reset($fileHashCounts);
        }
        $json = str_replace('\\/', '/', json_encode($fingerprints, JSON_PRETTY_PRINT));
        if (file_put_contents($this->baseDir . DS . self::VERSION_DESTINATION, $json)) {
            $output->writeln(sprintf('Unique hashes written to <info>%s</info>', self::VERSION_DESTINATION));
        } else {
            $output->writeln('<error>Failed to write unique hashes to file</error>');
        }
    }

    /**
     * Collect all the release hash datas
     *
     * [
     *     'CE-1.0.0' => [
     *         'abc123' => 'foo.js',
     *         'edf456' => 'bar.js',
     *     ],
     *     'CE-1.1.0' => [ ... ]
     * ]
     *
     * @return array
     */
    protected function collectData()
    {
        $data = [];
        foreach (glob($this->baseDir . DS . self::DIR_MD5 . DS . 'magento-*') as $release) {
            $lines   = explode("\n", file_get_contents($release));
            $release = str_replace($this->baseDir . DS . self::DIR_MD5 . DS . 'magento-', '', $release);
            $data[$release] = [];
            foreach ($lines as $line) {
                if (strlen($line) === 0) {
                    continue;
                }
                $bits = explode(' ', $line);
                $data[$release][$bits[0]] = $bits[1];
            }
        }
        return $data;
    }

    /**
     * Get the most import files determined by how many unique hashes they have
     *
     * [
     *     'skin/adminhtml/default/default/boxes.css' => 32,
     *     'js/mage/adminhtml/sales.js'               => 31,
     *     ....
     * ]
     * 
     * @param array $data
     *
     * @return array
     */
    protected function buildFileHashCounts(array $data)
    {
        $counts = [];
        foreach ($data as $value) {
            foreach ($value as $hash => $file) {
                if (!isset($counts[$file])) {
                    $counts[$file] = [];
                }
                if (!in_array($hash, $counts[$file])) {
                    $counts[$file][] = $hash;
                }
            }
        }
        foreach ($counts as &$hashes) {
            $hashes = count($hashes);
        }
        arsort($counts);
        return $counts;
    }

    /**
     * Take the release short name and expand
     *
     * @param string $name
     * @param array  $existing
     *
     * @return string[]
     */
    protected function prepareReleaseName($name, &$existing)
    {
        list($edition, $version) = explode('-', $name);
        switch ($edition) {
            case 'EE':
                $edition = 'Enterprise';
                break;
            case 'CE':
                $edition = 'Community';
                break;
        }
        $existing = [
            $edition,
            isset($existing[1]) ? $existing[1] . ', ' . $version : $version
        ];
    }
}
