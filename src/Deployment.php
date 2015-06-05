<?php

namespace Deeploy;

use React\ChildProcess\Process;
use GitWrapper\GitWrapper;
use React\EventLoop\LoopInterface;
use Symfony\Component\Filesystem\Filesystem;

class Deployment {

    private $wrapper;

    private $deployment;

    private $release;

    private $current;

    private $filesystem;

    public function __construct(LoopInterface $loop, $deployment)
    {
        $this->loop = $loop;

        $this->wrapper = new GitWrapper();
        $this->filesystem = new Filesystem();

        $this->deployment = $deployment;
        $this->release = $deployment['path'] . '/releases/' . time();
        $this->current = $deployment['path'] . '/current';
        $this->shared = $deployment['path']  . '/shared';
        $this->composer = $this->compose($deployment['composer']);
    }

    public function run()
    {

        $git = $this->wrapper->cloneRepository($this->deployment['repository'], $this->release);
        if(!$git->isCloned()) {
            throw new \RepositoryNotCloned('Not cloned');
        }

        $this->filesystem->remove($this->release . '/.git');


        $process = new Process($this->composer, $this->release);
        $process->on('exit', function($exitCode, $termSignal) {
            if($exitCode === 0) {
                if($this->filesystem->exists($this->current)) {
                    $this->filesystem->remove($this->current);
                }

                $this->filesystem->symlink($this->release, $this->current);
            }
        });

        $this->loop->addTimer(0.00001, function($timer) use ($process) {
            $process->start($timer->getLoop());

            $process->stdout->on('data', function($output) {
                print $output;
            });
        });

    }

    public function compose($params)
    {
        return \join(' ', [
            'exec',
            PHP_BINDIR . '/php',
            $params['path'],
            ' install',
            '-d ' . $this->release,
            $this->flags($params['flags']),
            $this->params($params['options'])
        ]);
    }

    public function flags($flags)
    {
        $string = '';
        foreach($flags as $flag) {
            $string .= ' --' . $flag;
        }
        return $string;
    }

    public function params($params)
    {
        $string = '';
        foreach($params as $key => $param) {
            $string .= '--' .$key . '=' . $param;
        }
        return $string;
    }

}