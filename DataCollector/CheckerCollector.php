<?php

namespace BW\LatestVersionCheckerBundle\DataCollector;

use Github\Api\Repo;
use Github\Client;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

/**
 * Class CheckerCollector
 * @package BW\LatestVersionCheckerBundle\DataCollector
 */
class CheckerCollector extends DataCollector
{
    const MAJOR = 1;

    const MINOR = 2;

    const PATCH = 3;

    /**
     * @param Request $request
     * @param Response $response
     * @param \Exception $exception
     */
    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
        // Get tags from GitHub API
        $client = new Client();
        /** @var Repo $repo */
        $repo = $client->api('repo');
        $repo->setPerPage(50);
        $tags = $repo->tags('symfony', 'symfony');

        $current = 'v' . Kernel::VERSION;
        $currentSegments = $this->splitVersion($current);

        $major = null;
        $minor = null;
        $patch = null;
        $minorLimit = $this->calculateMinorLimitVersion($currentSegments);
        $patchLimit = $this->calculatePatchLimitVersion($currentSegments);
        /** @var array $tag */
        foreach ($tags as $tag) {
            if (preg_match('/^(?<tag_name>v\d+\.\d+\.\d+)$/i', $tag['name'], $matches)) {
                if (version_compare($matches['tag_name'], (isset($patch) ? $patch : $current), '>')
                    and version_compare($matches['tag_name'], $patchLimit, '<')
                ) {
                    $patch = $tag['name'];
                }

                if (version_compare($matches['tag_name'], (isset($minor) ? $minor : $patchLimit), '>=')
                    and version_compare($matches['tag_name'], $minorLimit, '<')
                ) {
                    $minor = $tag['name'];
                }

                if (version_compare($matches['tag_name'], (isset($major) ? $major : $minorLimit), '>=')) {
                    $major = $tag['name'];
                }

                // Break iterating if current version has been found to improve performance
                if (version_compare($matches['tag_name'], $current, '=')) {
                    break;
                }
            }
        }
        unset($tags); // clear memory

        $this->data['current_version'] = $current;
        $this->data['major_version'] = $major;
        $this->data['minor_version'] = $minor;
        $this->data['patch_version'] = $patch;
        $this->data['is_up_to_date'] = !($patch or $minor or $major);
    }

    /**
     * @return bool
     */
    public function isUpToDate()
    {
        return $this->data['is_up_to_date'];
    }

    /**
     * @return string
     */
    public function getCurrentVersion()
    {
        return $this->data['current_version'];
    }

    /**
     * @return string
     */
    public function getMajorVersion()
    {
        return $this->data['major_version'];
    }

    /**
     * @return string
     */
    public function getMinorVersion()
    {
        return $this->data['minor_version'];
    }

    /**
     * @return string
     */
    public function getPatchVersion()
    {
        return $this->data['patch_version'];
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'bw_latest_version_checker';
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
