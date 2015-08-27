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

    const ACCURACY_FACTOR = 100;

    const EDITION_SHORT_ENTERPRISE = 'EE';
    const EDITION_SHORT_COMMUNITY  = 'CE';
    const EDITION_LONG_ENTERPRISE  = 'Enterprise';
    const EDITION_LONG_COMMUNITY   = 'Ccommunity';

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
        $tries          = 0;
        while (count($data) > 0) {
            $accuracy = ceil(++$tries / self::ACCURACY_FACTOR);
            $file     = key($fileHashCounts);
            $versionsWithThisFile = [];
            foreach ($data as $release => $value) {
                $fileHash = array_flip($value);
                if (isset($fileHash[$file]) && count($fileHashCounts[$file][$fileHash[$file]]) <= $accuracy) {
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
     *     'skin/adminhtml/default/default/boxes.css' => [
     *         'abc123' => [
     *             'CE-1.0'
     *         ],
     *         'efg456' => [
     *             'CE-1.1.0',
     *             'CE-1.1.1'
     *         ],
     *         ...
     *     ],
     *     ...
     * ]
     * 
     * @param array $data
     *
     * @return array
     */
    protected function buildFileHashCounts(array $data)
    {
        $counts = [];
        foreach ($data as $release => $value) {
            foreach ($value as $hash => $file) {
                if (!isset($counts[$file])) {
                    $counts[$file] = [];
                }
                if (!isset($counts[$file][$hash])) {
                    $counts[$file][$hash] = [];
                }
                $counts[$file][$hash][] = $release;
            }
        }
        uasort($counts, function($a, $b) {
            return count($b) - count($a);
        });
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
            case self::EDITION_SHORT_ENTERPRISE:
                $edition = self::EDITION_SHORT_ENTERPRISE;
                break;
            case self::EDITION_SHORT_COMMUNITY:
                $edition = self::EDITION_LONG_COMMUNITY;
                break;
        }
        if (!is_array($existing)) {
            $existing = [];
        }
        if (!isset($existing[$edition])) {
            $existing[$edition] = [];
        }
        $existing[$edition][] = $version;
    }
}
