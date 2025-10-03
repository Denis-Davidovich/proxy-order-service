<?php

declare(strict_types=1);

namespace App\Command;

use App\Soap\OrderService;
use Laminas\Soap\AutoDiscover;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @command app:generate-wsdl
 */
class GenerateWsdlCommand extends Command
{
    protected static $defaultName = 'app:generate-wsdl';
    protected static $defaultDescription = 'Generates the WSDL file for the SOAP server.';

    private UrlGeneratorInterface $router;
    private string $projectDir;

    public function __construct(UrlGeneratorInterface $router, string $projectDir)
    {
        parent::__construct();
        $this->router = $router;
        $this->projectDir = $projectDir;
    }

    protected function configure(): void
    {
        $this
            ->setName('app:generate-wsdl') // Explicitly set name
            ->setHelp('This command allows you to generate the WSDL file based on the OrderService class.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('WSDL File Generation');

        $autodiscover = new AutoDiscover();
        $autodiscover->setClass(OrderService::class);
        
        $endpointUrl = $this->router->generate('soap_order_server', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $autodiscover->setUri($endpointUrl);

        $wsdl = $autodiscover->generate();
        
        $wsdlDir = $this->projectDir . '/public/wsdl';
        if (!is_dir($wsdlDir)) {
            mkdir($wsdlDir, 0755, true);
        }
        
        $wsdlPath = $wsdlDir . '/orders.wsdl';
        $wsdl->dump($wsdlPath);

        $io->success('WSDL file has been generated successfully!');
        $io->writeln('Path: ' . $wsdlPath);

        return Command::SUCCESS;
    }
}
