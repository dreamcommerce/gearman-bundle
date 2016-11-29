<?php

namespace DreamCommerce\GearmanBundle\Command;

use Francodacosta\Supervisord\Configuration;
use Francodacosta\Supervisord\Loader\ArrayLoader;
use Francodacosta\Supervisord\Processors\CommandConfigurationProcessor;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateWorkersConfigCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('dream_commerce_gearman_bundle:generate_workers_config')
            ->setDescription('Generates supervisord configuration file based on created workers');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $client = $this->getContainer()->get('gearman');
        $workers = $client->getWorkers();

        $results = ['programs'=>[]];

        $phpPath = $this->getContainer()->getParameter('dream_commerce_gearman.config')['php_path'];

        $workersConfig = $this->getContainer()->getParameter('dream_commerce_gearman.config')['workers'];

        $prefix = $this->getContainer()->getParameter('dream_commerce_gearman.config')['name_prefix'];

        foreach($workers as $w){
            foreach($w['jobs'] as $x) {
                $directory = realpath($this->getContainer()->getParameter('kernel.root_dir').'/../');
                $command = sprintf('%s bin/console gearman:job:execute %s -n --env=prod', $phpPath, $x['realCallableNameNoPrefix']);

                // worker not configured
                if(!isset($workersConfig[$x['realCallableNameNoPrefix']])){
                    continue;
                }

                $numProcs =
                    $workersConfig[$x['realCallableNameNoPrefix']] ?: 3;

                if(!$numProcs){
                    continue;
                }

                $name = !$prefix ? $x['realCallableNameNoPrefix'] : sprintf('%s_%s', $prefix, $x['realCallableNameNoPrefix']);

                $results['programs'][] = [
                    'name'=>$name,
                    'directory'=>$directory,
                    'command'=> $command,
                    'process_name'=>'%(program_name)s_%(process_num)02d',
                    'autostart'=>'true',
                    'autorestart'=>'true',
                    'numprocs'=>$numProcs
                ];
            }
        }

        if($results['programs']) {
            $cfg = new Configuration();
            $cfg->registerProcessor(
                new CommandConfigurationProcessor()
            );
            $loader = new ArrayLoader();
            $loader->setSource($results);
            $loader->setConfiguration(
                $cfg
            );

            $gen = $loader->load();

            $output->writeln($gen->generate());
        }

    }
}
