<?php

namespace BW\LatestVersionCheckerBundle\DataCollector;

use BW\LatestVersionCheckerBundle\Service\CheckerService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

/**
 * Class CheckerCollector
 * @package BW\LatestVersionCheckerBundle\DataCollector
 */
class CheckerCollector extends DataCollector
{
    /**
     * @var CheckerService
     */
    private $checker;

    /**
     * @param CheckerService $checker
     */
    public function __construct(CheckerService $checker)
    {
        $this->checker = $checker;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param \Exception $exception
     */
    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
        $this->checker->check();

        $this->data['is_up_to_date'] = $this->checker->isUpToDate();
        $this->data['current_version'] = $this->checker->getCurrent();
        $this->data['patch_version'] = $this->checker->getPatch();
        $this->data['minor_version'] = $this->checker->getMinor();
        $this->data['major_version'] = $this->checker->getMajor();
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
}
