<?php


namespace DreamCommerce\GearmanBundle\Service;


use Mmoreram\GearmanBundle\Command\Util\GearmanOutputAwareInterface;
use Mmoreram\GearmanBundle\Event\GearmanWorkExecutedEvent;
use Mmoreram\GearmanBundle\GearmanEvents;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class GearmanExecute extends \Mmoreram\GearmanBundle\Service\GearmanExecute
{

    // keep 16 MiB free, regardless of latest heavy task amount
    const RESERVED_MEMORY_AMOUNT = 16777216;

    /**
     * @var ContainerInterface
     */
    protected $container;

    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
    }


    /**
     * Executes a job given a jobName and given settings and annotations of job
     *
     * @param string $jobName Name of job to be executed
     */
    public function executeJob($jobName)
    {
        $worker = $this->getJob($jobName);

        if (false !== $worker) {

            $this->callJob($worker);
        }
    }

    /**
     * Given a worker, execute GearmanWorker function defined by job.
     *
     * @param array $worker Worker definition
     *
     * @return GearmanExecute self Object
     */
    protected function callJob(Array $worker)
    {
        $gearmanWorker = new \GearmanWorker;

        if (isset($worker['job'])) {

            $jobs = array($worker['job']);
            $iterations = $worker['job']['iterations'];
            $this->addServers($gearmanWorker, $worker['job']['servers']);

        } else {

            $jobs = $worker['jobs'];
            $iterations = $worker['iterations'];
            $this->addServers($gearmanWorker, $worker['servers']);
        }

        $objInstance = $this->createJob($worker);
        $this->runJob($gearmanWorker, $objInstance, $jobs, $iterations);

        return $this;
    }

    /**
     * Given a worker settings, return Job instance
     *
     * @param array $worker Worker settings
     *
     * @return Object Job instance
     */
    protected function createJob(array $worker)
    {
        /**
         * If service is defined, we must retrieve this class with dependency injection
         *
         * Otherwise we just create it with a simple new()
         */
        if ($worker['service']) {

            $objInstance = $this->container->get($worker['service']);

        } else {

            $objInstance = new $worker['className'];

            /**
             * If instance of given object is instanceof
             * ContainerAwareInterface, we inject full container by calling
             * container setter.
             *
             * @see https://github.com/mmoreram/gearman-bundle/pull/12
             */
            if ($objInstance instanceof ContainerAwareInterface) {

                $objInstance->setContainer($this->container);
            }
        }

        return $objInstance;
    }

    /**
     * Given a GearmanWorker and an instance of Job, run it
     *
     * @param \GearmanWorker $gearmanWorker Gearman Worker
     * @param Object         $objInstance   Job instance
     * @param array          $jobs          Array of jobs to subscribe
     * @param integer        $iterations    Number of iterations
     *
     * @return GearmanExecute self Object
     */
    protected function runJob(\GearmanWorker $gearmanWorker, $objInstance, array $jobs, $iterations)
    {

        /**
         * Set the output of this instance, this should allow workers to use the console output.
         */
        if ($objInstance instanceof GearmanOutputAwareInterface) {
            $objInstance->setOutput($this->output ? : new NullOutput());
        }

        /**
         * Every job defined in worker is added into GearmanWorker
         */
        foreach ($jobs as $job) {

            $gearmanWorker->addFunction($job['realCallableName'], array($objInstance, $job['methodName']));
        }

        $gearmanWorker->setTimeout(1000);

        /**
         * If iterations value is 0, is like worker will never die
         */
        $alive = (0 == $iterations);

        $maxTaskMemoryUsage = 0;

        /**
         * Executes GearmanWorker with all jobs defined
         */
        while (true) {

            $gearmanWorker->work();
            $returnCode = $gearmanWorker->returnCode();

            if($returnCode == GEARMAN_IO_WAIT || $returnCode == GEARMAN_TIMEOUT){
                continue;
            }else if ($returnCode != GEARMAN_SUCCESS) {
                break;
            }

            $iterations--;

            $event = new GearmanWorkExecutedEvent($jobs, $iterations, $returnCode);
            $this->eventDispatcher->dispatch(GearmanEvents::GEARMAN_WORK_EXECUTED, $event);

            $memoryUsage = memory_get_usage(true);

            if($memoryUsage>$maxTaskMemoryUsage){
                $maxTaskMemoryUsage = $memoryUsage;
            }

            if($this->isMemoryExhausted($maxTaskMemoryUsage)){
                break;
            }

            /**
             * Only finishes its execution if alive is false and iterations
             * arrives to 0
             */
            if (!$alive && $iterations <= 0) {
                break;
            }
        }


    }

    /**
     * prevents breaking tasks when memory limit grows up too much
     * @param integer $maxTaskMemoryUsage
     * @return bool
     */
    protected function isMemoryExhausted($maxTaskMemoryUsage){
        static $memory;
        if(!$memory){
            // http://stackoverflow.com/a/10209530
            $memory = ini_get('memory_limit');
            if (preg_match('/^(\d+)(.)$/', $memory, $matches)) {
                if ($matches[2] == 'M') {
                    $memory = $matches[1] * 1024 * 1024; // nnnM -> nnn MB
                } else if ($matches[2] == 'K') {
                    $memory = $matches[1] * 1024; // nnnK -> nnn KB
                }
            }
        }

        // examine diff between tasks
        $currentMemory = memory_get_usage(true);
        $diff = abs($maxTaskMemoryUsage-$currentMemory);

        // if difference exceeds limit
        if($currentMemory+$diff>($memory-self::RESERVED_MEMORY_AMOUNT)){
            return true;
        }

        return false;
    }

    /**
     * Adds into worker all defined Servers.
     * If any is defined, performs default method
     *
     * @param \GearmanWorker $gmworker Worker to perform configuration
     * @param array          $servers  Servers array
     */
    protected function addServers(\GearmanWorker $gmworker, Array $servers)
    {
        if (!empty($servers)) {

            foreach ($servers as $server) {

                $gmworker->addServer($server['host'], $server['port']);
            }
        } else {
            $gmworker->addServer();
        }
    }

    /**
     * Executes a worker given a workerName subscribing all his jobs inside and
     * given settings and annotations of worker and jobs
     *
     * @param string $workerName Name of worker to be executed
     */
    public function executeWorker($workerName)
    {
        $worker = $this->getWorker($workerName);

        if (false !== $worker) {

            $this->callJob($worker);
        }
    }
}