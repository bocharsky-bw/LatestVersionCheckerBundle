<?php

namespace BW\LatestVersionCheckerBundle\Service;

use Github\Api\Repo;
use Github\Client;
use Symfony\Component\HttpKernel\Kernel;

/**
 * Class CheckerService
 */
class CheckerService
{
    /**
     * @var string
     */
    private $current;

    /**
     * @var string
     */
    private $patch;

    /**
     * @var string
     */
    private $minor;

    /**
     * @var string
     */
    private $major;

    /**
     * Check available version
     *
     * @return bool
     */
    public function check()
    {
        // Get tags from GitHub API
        $client = new Client();
        /** @var Repo $repo */
        $repo = $client->api('repo');
        $repo->setPerPage(50);
        $tags = $repo->tags('symfony', 'symfony');

        $this->current = 'v' . Kernel::VERSION;
        $currentSegments = $this->splitVersion($this->current);

        $minorLimit = $this->calculateMinorLimitVersion($currentSegments);
        $patchLimit = $this->calculatePatchLimitVersion($currentSegments);
        /** @var array $tag */
        foreach ($tags as $tag) {
            if (preg_match('/^(?<tag_name>v\d+\.\d+\.\d+)$/i', $tag['name'], $matches)) {
                if (version_compare($matches['tag_name'], (isset($this->patch) ? $this->patch : $this->current), '>')
                    and version_compare($matches['tag_name'], $patchLimit, '<')
                ) {
                    $this->patch = $tag['name'];
                }

                if (version_compare($matches['tag_name'], (isset($this->minor) ? $this->minor : $patchLimit), '>=')
                    and version_compare($matches['tag_name'], $minorLimit, '<')
                ) {
                    $this->minor = $tag['name'];
                }

                if (version_compare($matches['tag_name'], (isset($this->major) ? $this->major : $minorLimit), '>=')) {
                    $this->major = $tag['name'];
                }

                // Break iterating if current version has been found to improve performance
                if (version_compare($matches['tag_name'], $this->current, '=')) {
                    break;
                }
            }
        }
        unset($tags); // clear memory

        return true;
    }

    /**
     * @return string
     */
    public function getCurrent()
    {
        return $this->current;
    }

    /**
     * @return string
     */
    public function getPatch()
    {
        return $this->patch;
    }

    /**
     * @return string
     */
    public function getMinor()
    {
        return $this->minor;
    }

    /**
     * @return string
     */
    public function getMajor()
    {
        return $this->major;
    }

    /**
     * @param string $version
     * @return array
     */
    protected function splitVersion($version)
    {
        $default = [
            'major' => 0,
            'minor' => 0,
            'patch' => 0,
        ];
        $segments = [];
        if (preg_match('/^v(\d+)\.(\d+)\.(\d+)/i', $version, $matches)) {
            $segments['major'] = (int)$matches[1];
            $segments['minor'] = (int)$matches[2];
            $segments['patch'] = (int)$matches[3];
        }

        return array_merge($default, $segments);
    }

    /** Redundant */
    //protected function getMajorLimitVersion() {}

    /**
     * @param array $segments
     * @return string
     */
    protected function calculateMinorLimitVersion(array $segments)
    {
        return sprintf('v%d.%d.%d', $segments['major'] + 1, 0, 0);
    }

    /**
     * @param array $segments
     * @return string
     */
    protected function calculatePatchLimitVersion(array $segments)
    {
        return sprintf('v%d.%d.%d', $segments['major'], $segments['minor'] + 1, 0);
    }
}
