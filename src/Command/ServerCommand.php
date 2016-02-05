<?php
namespace Uptime\Command;

use GuzzleHttp\Event\CompleteEvent;
use GuzzleHttp\Event\ErrorEvent;
use GuzzleHttp\Pool;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;
use Uptime\Configuration;
use Uptime\Instance;

class ServerCommand extends Command
{
    protected function configure()
    {
        $this->setName('uptime:server')
            ->addArgument('config-file', InputArgument::REQUIRED, 'path of config file')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $config = $this->setConfiguraton($input);
        $client = new \GuzzleHttp\Client([
            'defaults' => [
                'allow_redirects' => false,
                'timeout'         => 5,
                'connect_timeout' => 5
            ]
        ]);
        /** @var Instance $instance */
        foreach ($config->getInstances() as $instance) {
            $requests[] = $client->createRequest('HEAD', $instance->getUrl());
        }
        $options = [];
        Pool::send($client, $requests, [
            'complete' => function (CompleteEvent $event) {
            },
            'error'    => function (ErrorEvent $event) use ($config) {
                $instance = $config->findInstanceByUrl($event->getRequest()->getUrl());
                    if ($instance == null) {
                        throw new \RuntimeException('Instance not found');
                    }
                if (!$event->getException()->hasResponse()) {
                    $raven    = new \Raven_Client($instance->getSentryDsn());
                    $event_id = $raven->getIdent(
                        $raven->captureMessage(sprintf('The website %s with url -> %s is down or has a problem',
                            $instance->getName(),
                            $event->getRequest()->getUrl()),
                            [],
                            \Raven_Client::FATAL
                        )

                    );
                    if ($raven->getLastError() !== null) {
                        printf('There was an error sending the event to Sentry: %s', $raven->getLastError());
                    }
                    $error_handler = new \Raven_ErrorHandler($raven);
                    $error_handler->registerExceptionHandler();
                    $error_handler->registerErrorHandler();
                    $error_handler->registerShutdownFunction();
                }

            }
        ]);


    }

    private function setConfiguraton(InputInterface $input)
    {

        $config = Yaml::parse(file_get_contents($input->getArgument('config-file')));

        $configuration = new Configuration();
        foreach ($config['instances'] as $instance) {
            $obj = new Instance($instance);
            $configuration->addInstance($obj);
        }
        return $configuration;
    }
}