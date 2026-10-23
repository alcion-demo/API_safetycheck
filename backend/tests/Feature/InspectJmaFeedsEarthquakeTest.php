<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\SafetyCheck;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use App\Mail\SafetyCheckNotification;

class InspectJmaFeedsEarthquakeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic feature test example.
     */
    public function test_example(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_earthquake_xml_creates_safety_check_and_queues_emails(): void
    {
        Mail::fake();

        User::factory()->create([
            'email' => 'earthquake-test@example.com',
        ]);

        $sourceFile = base_path(
            'storage/app/private/32-39_11_05_240613_VXSE53.xml'
        );

        $xml = file_get_contents($sourceFile);

        $dom = new \DOMDocument();
        $dom->loadXML($xml);

        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace(
            'head',
            'http://xml.kishou.go.jp/jmaxml1/informationBasis1/'
        );

        $reportDateTime = $xpath->query('//head:ReportDateTime')->item(0);

        $this->assertNotNull($reportDateTime);
        $reportDateTime->nodeValue = now()->toIso8601String();

        $testFile = tempnam(sys_get_temp_dir(), 'earthquake-test-');
        file_put_contents($testFile, $dom->saveXML());

        try {
            $this->artisan('app:inspect-jma-feeds', [
                '--file' => $testFile,
            ])->assertSuccessful();

            $this->assertDatabaseHas('safety_checks', [
                'disaster_key' => 'earthquake:' . $this->getEventIdFromXml($testFile),
                'type' => 'production',
                'title' => '地震発生に伴う安否確認',
            ]);

            Mail::assertQueued(SafetyCheckNotification::class, 1);
        } finally {
            @unlink($testFile);
        }
    }

    private function getEventIdFromXml(string $file): string
    {
        $dom = new \DOMDocument();
        $dom->load($file);

        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace(
            'head',
            'http://xml.kishou.go.jp/jmaxml1/informationBasis1/'
        );

        return trim($xpath->evaluate('string(//head:EventID)'));
    }
}
