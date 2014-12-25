<?php

namespace BW\LatestVersionCheckerBundle\DataCollector;

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
    public function __construct()
    {
    }

    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
        $this->data = [
            'current_version' => Kernel::VERSION,
        ];
    }

    public function getCurrentVersion()
    {
        return $this->data['current_version'];
    }


    public function getName()
    {
        return 'bw_latest_version_checker';
    }
}
