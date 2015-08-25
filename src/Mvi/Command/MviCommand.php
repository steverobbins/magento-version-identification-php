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

use Symfony\Component\Console\Command\Command;

class MviCommand extends Command
{
    const DIR_MD5 = 'md5';

    /**
     * The base of this application
     *
     * @var string
     */
    protected $baseDir;

    /**
     * Constructor.
     *
     * @param string|null $name The name of the command; passing null means it must be set in configure()
     */
    public function __construct($name = null)
    {
        parent::__construct($name);
        $this->baseDir = realpath(__DIR__ . DS . '..' . DS . '..' . DS . '..');
    }
}
