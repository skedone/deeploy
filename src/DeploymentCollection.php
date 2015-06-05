<?php

namespace Deeploy;

use React\EventLoop\LoopInterface;

class DeploymentCollection {

    private $deployments = [];

    public function __construct(LoopInterface $loop, $deployments)
    {
        $this->loop = $loop;
        $this->deployments = $deployments;
    }

    public function deploy()
    {
        foreach($this->deployments as $deployment){
            $deploy = new Deployment($this->loop, $deployment);
            $deploy->run();
        }
    }
}