<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class OrderSoapControllerTest extends WebTestCase
{
    /**
     * Test successful order creation via SOAP.
     */
    public function testCreateOrderSuccess(): void
    {
        $client = static::createClient();

        // The WSDL file must exist for the SOAP server to work,
        // so we ensure it's generated before running the test.
        $this->generateWsdl();

        $soapRequestXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ns1="http://localhost/api/v1/soap/order">
    <SOAP-ENV:Body>
        <ns1:createOrder>
            <product>Test Product from SOAP</product>
            <quantity>10</quantity>
            <address>123 Test Street, Soap City</address>
        </ns1:createOrder>
    </SOAP-ENV:Body>
</SOAP-ENV:Envelope>
XML;

        $client->request(
            'POST',
            '/api/v1/soap/order',
            [],
            [],
            ['CONTENT_TYPE' => 'text/xml; charset=utf-8'],
            $soapRequestXml
        );

        $this->assertResponseIsSuccessful();
        $response = $client->getResponse();
        $this->assertStringContainsString('text/xml', $response->headers->get('Content-Type'));

        $responseXml = $response->getContent();

        // Use DOMDocument and XPath for reliable XML parsing
        $dom = new \DOMDocument();
        $dom->loadXML($responseXml);
        $xpath = new \DOMXPath($dom);

        // Register the SOAP namespace to use in XPath queries
        $xpath->registerNamespace('soap', 'http://schemas.xmlsoap.org/soap/envelope/');
        
        // Assertions to check the content of the SOAP response
        $this->assertEquals('true', $xpath->evaluate('string(//success)'), 'Assert that the success node contains "true"');
        $this->assertNotEmpty($xpath->evaluate('string(//orderId)'), 'Assert that an orderId node exists and is not empty');
        $this->assertStringContainsString('Order #', $xpath->evaluate('string(//message)'), 'Assert that the message contains "Order #"');
    }

    /**
     * Test for order creation failure when quantity is zero.
     */
    public function testCreateOrderFailureInvalidQuantity(): void
    {
        $client = static::createClient();
        $this->generateWsdl();

        $soapRequestXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ns1="http://localhost/api/v1/soap/order">
    <SOAP-ENV:Body>
        <ns1:createOrder>
            <product>Invalid Product</product>
            <quantity>0</quantity>
            <address>456 Error Avenue</address>
        </ns1:createOrder>
    </SOAP-ENV:Body>
</SOAP-ENV:Envelope>
XML;

        $client->request(
            'POST',
            '/api/v1/soap/order',
            [],
            [],
            ['CONTENT_TYPE' => 'text/xml; charset=utf-8'],
            $soapRequestXml
        );

        $this->assertResponseIsSuccessful();
        $responseXml = $client->getResponse()->getContent();

        $dom = new \DOMDocument();
        $dom->loadXML($responseXml);
        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('soap', 'http://schemas.xmlsoap.org/soap/envelope/');

        $this->assertEquals('false', $xpath->evaluate('string(//success)'), 'Assert that the success node contains "false"');
        $this->assertStringContainsString('Quantity must be positive', $xpath->evaluate('string(//message)'));
    }

    /**
     * Helper method to ensure the WSDL file exists before a test run.
     */
    private function generateWsdl(): void
    {
        $kernel = self::bootKernel();
        $application = new \Symfony\Bundle\FrameworkBundle\Console\Application($kernel);
        $application->setAutoExit(false);

        $input = new \Symfony\Component\Console\Input\ArrayInput([
            'command' => 'app:generate-wsdl',
            '--quiet' => true,
        ]);

        $application->run($input);
    }
}
