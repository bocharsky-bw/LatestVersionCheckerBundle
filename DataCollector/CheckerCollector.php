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
        $client = new Client();
        /** @var Repo $repo */
        $repo = $client->api('repos');
        $tags = $repo->tags('symfony', 'symfony');

        $this->data = [
            'current_version' => 'v' . Kernel::VERSION,
        ];

        $major = null;
        $minor = null;
        $patch = null;
        $current = $this->data['current_version'];

        /** @var array $tag */
        foreach ($tags as $tag) {
            if (preg_match('/^(?<tag_name>v\d+\.\d+\.\d+)$/i', $tag['name'], $matches)) {
                if (true
                    and version_compare($matches['tag_name'], (isset($major) ? $major : $this->getMinorLimitVersion()), '>')
                ) {
                    $major = $tag['name'];
                }

                if (true
                    and version_compare($matches['tag_name'], (isset($minor) ? $minor : $current), '>')
                    and version_compare($matches['tag_name'], $this->getMinorLimitVersion(), '<')
                ) {
                    $minor = $tag['name'];
                }

                if (true
                    and version_compare($matches['tag_name'], (isset($patch) ? $patch : $current), '>')
                    and version_compare($matches['tag_name'], $this->getPatchLimitVersion(), '<')
                ) {
                    $patch = $tag['name'];
                }

                // Break iterating if current version has been found to improve performance
                if (version_compare($matches['tag_name'], $current, '=')) {
                    break;
                }
            }
        }

        $this->data['major_version'] = $major;
        $this->data['minor_version'] = $minor;
        $this->data['patch_version'] = $patch;
    }

    /**
     * @return string
     */
    public function getCurrentVersion()
    {
        return $this->data['current_version'];
    }

    public function getSemanticVersionArray()
    {
        $default = [
            'major' => 0,
            'minor' => 0,
            'patch' => 0,
        ];
        $current = [];
        if (preg_match('/^v(\d+)\.(\d+)\.(\d+)/i', $this->data['current_version'], $matches)) {
            $current['major'] = (int)$matches[1];
            $current['minor'] = (int)$matches[2];
            $current['patch'] = (int)$matches[3];
        }

        return array_merge($default, $current);
    }

    /** Redundant */
    //public function getMajorLimitVersion() {}

    public function getMinorLimitVersion()
    {
        $version = $this->getSemanticVersionArray();

        return sprintf('v%d.%d.%d', $version['major'] + 1, 0, 0);
    }

    public function getPatchLimitVersion()
    {
        $version = $this->getSemanticVersionArray();

        return sprintf('v%d.%d.%d', $version['major'], $version['minor'] + 1, 0);
    }

    public function getMajorVersion()
    {
        return $this->data['major_version'];
    }

    public function getMinorVersion()
    {
        return $this->data['minor_version'];
    }

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
}
