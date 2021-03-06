<?php

namespace App\Test\TestCase\Controller;

use Cake\Core\Configure;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestCase;

class IncidentsControllerTest extends IntegrationTestCase
{
    public $fixtures = array(
        'app.notifications',
        'app.developers',
        'app.reports',
        'app.incidents',
    );

    public function setUp()
    {
        $this->Incidents = TableRegistry::get('Incidents');
        //$Session = new SessionComponent(new ComponentRegistry());
        $this->session(array('Developer.id' => 1));
        $this->Reports = TableRegistry::get('Reports');
    }

    public function testView()
    {
        $this->get('/incidents/view/1');

        $this->assertNotEmpty($this->viewVariable('incident'));
        $this->assertInternalType('array',
                $this->viewVariable('incident')['stacktrace']);
        $this->assertInternalType('array',
                $this->viewVariable('incident')['full_report']);
    }

    public function testJson()
    {
        $this->get('/incidents/json/1');
        $incident = json_decode($this->_response->body(), true);
        $expected = array(
                'id' => 1,
                'error_name' => 'Lorem ipsum dolor sit amet',
                'error_message' => 'Lorem ipsum dolor sit amet',
                'pma_version' => 'Lorem ipsum dolor sit amet',
                'php_version' => '5.5',
                'browser' => 'Lorem ipsum dolor sit amet',
                'user_os' => 'Lorem ipsum dolor sit amet',
                'locale' => 'Lorem ipsum dolor sit amet',
                'server_software' => 'Lorem ipsum dolor sit amet',
                'stackhash' => 'hash1',
                'configuration_storage' => 'Lorem ipsum dolor sit amet',
                'script_name' => 'Lorem ipsum dolor sit amet',
                'steps' => 'Lorem ipsum dolor sit amet',
                'stacktrace' => array(array('context' => array('test'))),
                'full_report' => array(
                    'pma_version' => '',
                    'php_version' => '',
                    'browser_name' => '',
                    'browser_version' => '',
                    'user_agent_string' => '',
                    'server_software' => '',
                    'locale' => '',
                    'exception' => array('uri' => ''),
                    'configuration_storage' => '',
                    'microhistory' => '',
                ),
                'report_id' => 1,
                'created' => '2013-08-29T18:10:01',
                'modified' => '2013-08-29T18:10:01',
                'exception_type' => null,
        );

        $this->assertEquals($expected, $incident);
    }

    public function testCreate()
    {
        $bugReport = file_get_contents(TESTS . 'Fixture' . DS . 'report_js.json');
        $bugReportDecoded = json_decode($bugReport, true);
        $this->configRequest(array('input' => $bugReport));
        $this->post('/incidents/create');

        $report = $this->Reports->find('all',
            array('order' => 'Reports.created desc'))->all()->first();
        $subject = 'A new report has been submitted '
            . 'on the Error Reporting Server: '
            . $report['id'];
        $this->assertEquals('4.5.4.1', $report['pma_version']);

        // Test notification email
        $emailContent = Configure::read('test_transport_email');

        $this->assertEquals('reports-notify@phpmyadmin.net', $emailContent['headers']['From']);
        $this->assertEquals('reports-notify@phpmyadmin.net', $emailContent['headers']['To']);
        $this->assertEquals($subject, $emailContent['headers']['Subject']);

        Configure::write('test_transport_email', NULL);

        $this->post('/incidents/create');

        $report = $this->Reports->find('all',
                array('order' => 'Reports.created desc'))->all()->first();
        $this->Reports->id = $report['id'];
        $incidents = $this->Reports->getIncidents();
        $incidents = $incidents->hydrate(false)->toArray();
        $this->assertEquals(2, count($incidents));
        $this->assertEquals($bugReportDecoded['exception']['message'],
                $report['error_message']);
        $this->assertEquals($bugReportDecoded['exception']['name'],
                $report['error_name']);
        $this->assertEquals(
            $this->Incidents->getStrippedPmaVersion($bugReportDecoded['pma_version']),
            $report['pma_version']
        );

        $this->configRequest(array('input' => ''));
        $this->post('/incidents/create');
        $result = json_decode($this->_response->body(), true);
        $this->assertEquals(false, $result['success']);

        // Test invalid Notification email configuration
        Configure::write('NotificationEmailsTo', '');

        $bugReport = file_get_contents(TESTS . 'Fixture' . DS . 'report_php.json');
        $bugReportDecoded = json_decode($bugReport, true);
        $this->configRequest(array('input' => $bugReport));
        $this->post('/incidents/create');

        $report = $this->Reports->find('all',
            array('order' => 'Reports.created desc'))->all()->first();
        $subject = 'A new report has been submitted '
            . 'on the Error Reporting Server: '
            . $report['id'];
        $this->assertEquals('4.5.4.1', $report['pma_version']);

        $emailContent = Configure::read('test_transport_email');

        // Since no email sent
        $this->assertEquals(NULL, $emailContent);

    }
}
