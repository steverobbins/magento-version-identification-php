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

namespace Mvi;

/**
 * Checks a Magento URL and tries to determine the edition and version
 */
class Check
{
    /**
     * The URL we're checking
     *
     * @var string
     */
    private $url;

    /**
     * Initialize
     *
     * @param string $url
     */
    public function __construct($url = null)
    {
        if ($url !== null) {
            $this->setUrl($url);
        }
    }

    /**
     * Set the URL to check
     *
     * @param string $url
     *
     * @return Mvi\Check
     */
    public function setUrl($url)
    {
        $this->validateUrl($url);
        $this->url = $url;
        return $this;
    }

    /**
     * Get the edition and version for the url
     *
     * @return array|boolean
     */
    public function getInfo()
    {
        $versions = $this->getVersions();
        foreach ($versions as $file => $hash) {
            $md5 = md5(@file_get_contents($this->url . $file));
            if (isset($hash[$md5])) {
                return $hash[$md5];
            }
        }
        return false;
    }

    /**
     * Get version information from json
     *
     * @return array
     */
    public function getVersions()
    {
        return json_decode(
            file_get_contents(dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'version.json'),
            true
        );
    }

    /**
     * Validate the URL to check
     *
     * @param string $url
     *
     * @return void
     */
    protected function validateUrl($url)
    {
        if (!(substr($url, 0, 7) === 'http://' || substr($url, 0, 8) === 'https://')) {
            throw new \InvalidArgumentException('The URL must start with "http://" or "https://"');
        }
        if (substr($url, -1) !== '/') {
            throw new \InvalidArgumentException('The URL must end in a slash (/)');
        }
    }
}
