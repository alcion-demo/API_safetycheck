<?php

namespace Tests\Feature;

use App\Models\SafetyCheck;
use App\Models\WeatherWarningState;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class WeatherWarningReleaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_level_4_warning_release_does_not_close_active_safety_check(): void
    {
        $disasterKey = 'weather-warning:1310100:test-release';

        $state = WeatherWarningState::create([
            'area_code' => '1310100',
            'area_name' => 'テスト区',
            'warning_name' => 'レベル４大雨危険警報',
            'is_active' => true,
            'active_disaster_key' => $disasterKey,
        ]);

        $user = \App\Models\User::factory()->create();

        SafetyCheck::create([
            'disaster_key' => $disasterKey,
            'title' => 'テスト区 大雨警報による安否確認',
            'message' => '解除テスト',
            'type' => 'disaster',
            'status' => 'active',
            'started_at' => now(),
            'ended_at' => null,
            'created_by' => $user->id,
        ]);

        $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<Report xmlns="http://xml.kishou.go.jp/jmaxml1/"
        xmlns:head="http://xml.kishou.go.jp/jmaxml1/informationBasis1/"
        xmlns:body="http://xml.kishou.go.jp/jmaxml1/body/meteorology1/">
  <head:Title>気象警報・注意報</head:Title>
  <head:ReportDateTime>2026-10-22T10:00:00+09:00</head:ReportDateTime>
  <body:Warning type="気象警報・注意報（市町村等）">
    <body:Item>
      <body:Area>
        <body:Name>テスト区</body:Name>
        <body:Code>1310100</body:Code>
      </body:Area>
      <body:Kind>
        <body:Name>大雨注意報</body:Name>
        <body:Status>警報から注意報</body:Status>
        <body:LastKind>
          <body:Name>レベル４大雨危険警報</body:Name>
        </body:LastKind>
      </body:Kind>
    </body:Item>
  </body:Warning>
</Report>
XML;

        $file = tempnam(sys_get_temp_dir(), 'jma-release-');
        file_put_contents($file, $xml);

        try {
            $this->artisan('app:inspect-jma-feeds', [
                '--file' => $file,
            ])->assertExitCode(0);
        } finally {
            @unlink($file);
        }

        $state->refresh();

        $this->assertFalse((bool) $state->is_active);
        $this->assertNull($state->active_disaster_key);

        $this->assertDatabaseHas('safety_checks', [
            'disaster_key' => $disasterKey,
            'status' => 'active',
            'ended_at' => null,
        ]);

        $this->assertNull(
            SafetyCheck::where('disaster_key', $disasterKey)
                ->firstOrFail()
                ->ended_at
        );
    }


    public function test_warning_remains_active_when_release_is_not_confirmed(): void
    {
        $disasterKey = 'weather-warning:1310100:test-no-release';

        $state = WeatherWarningState::create([
            'area_code' => '1310100',
            'area_name' => 'テスト区',
            'warning_name' => 'レベル４大雨危険警報',
            'is_active' => true,
            'active_disaster_key' => $disasterKey,
        ]);

        $user = \App\Models\User::factory()->create();

        \App\Models\SafetyCheck::create([
            'disaster_key' => $disasterKey,
            'title' => 'テスト区 大雨警報による安否確認',
            'message' => '解除されていないことのテスト',
            'type' => 'disaster',
            'status' => 'active',
            'started_at' => now(),
            'ended_at' => null,
            'created_by' => $user->id,
        ]);

        $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<Report xmlns="http://xml.kishou.go.jp/jmaxml1/"
        xmlns:head="http://xml.kishou.go.jp/jmaxml1/informationBasis1/"
        xmlns:body="http://xml.kishou.go.jp/jmaxml1/body/meteorology1/">
  <head:Title>気象警報・注意報</head:Title>
  <head:ReportDateTime>2026-10-22T10:00:00+09:00</head:ReportDateTime>
  <body:Warning type="気象警報・注意報（市町村等）">
    <body:Item>
      <body:Area>
        <body:Name>テスト区</body:Name>
        <body:Code>1310100</body:Code>
      </body:Area>
      <body:Kind>
        <body:Name>大雨注意報</body:Name>
        <body:Status>発表</body:Status>
      </body:Kind>
    </body:Item>
  </body:Warning>
</Report>
XML;

        $file = tempnam(sys_get_temp_dir(), 'jma-no-release-');
        file_put_contents($file, $xml);

        try {
            $this->artisan('app:inspect-jma-feeds', [
                '--file' => $file,
            ])->assertExitCode(0);
        } finally {
            @unlink($file);
        }

        $state->refresh();

        $this->assertTrue((bool) $state->is_active);
        $this->assertSame($disasterKey, $state->active_disaster_key);

        $this->assertDatabaseHas('safety_checks', [
            'disaster_key' => $disasterKey,
            'status' => 'active',
            'ended_at' => null,
        ]);
    }


    public function test_level_5_escalation_creates_new_safety_check_and_keeps_previous_active(): void
    {
        $oldKey = 'weather-warning:1310100:test-level-4';

        $state = WeatherWarningState::create([
            'area_code' => '1310100',
            'area_name' => 'テスト区',
            'warning_name' => 'レベル４大雨危険警報',
            'is_active' => true,
            'active_disaster_key' => $oldKey,
        ]);

        $user = \App\Models\User::factory()->create();

        SafetyCheck::create([
            'disaster_key' => $oldKey,
            'title' => 'テスト区 大雨警報による安否確認',
            'message' => 'レベル4の安否確認',
            'type' => 'disaster',
            'status' => 'active',
            'started_at' => now(),
            'ended_at' => null,
            'created_by' => $user->id,
        ]);

        $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<Report xmlns="http://xml.kishou.go.jp/jmaxml1/"
        xmlns:head="http://xml.kishou.go.jp/jmaxml1/informationBasis1/"
        xmlns:body="http://xml.kishou.go.jp/jmaxml1/body/meteorology1/">
  <head:Title>気象警報・注意報</head:Title>
  <head:ReportDateTime>2026-10-22T10:00:00+09:00</head:ReportDateTime>
  <body:Warning type="気象警報・注意報（市町村等）">
    <body:Item>
      <body:Area>
        <body:Name>テスト区</body:Name>
        <body:Code>1310100</body:Code>
      </body:Area>
      <body:Kind>
        <body:Name>レベル５大雨特別警報</body:Name>
        <body:Status>発表</body:Status>
        <body:LastKind>
          <body:Name>レベル４大雨危険警報</body:Name>
        </body:LastKind>
      </body:Kind>
    </body:Item>
  </body:Warning>
</Report>
XML;

        $file = tempnam(sys_get_temp_dir(), 'jma-level5-');
        file_put_contents($file, $xml);

        try {
            $command = Artisan::call('app:inspect-jma-feeds', [
                '--file' => $file,
            ]);

            $this->assertSame(0, $command);
        } finally {
            @unlink($file);
        }

        $state->refresh();

        // 状態はレベル5の新しいSafetyCheckを指す。
        $this->assertSame('レベル５大雨特別警報', $state->warning_name);
        $this->assertNotSame($oldKey, $state->active_disaster_key);
        $this->assertTrue((bool) $state->is_active);

        // 以前のレベル4のSafetyCheckは自動終了しない。
        $this->assertDatabaseHas('safety_checks', [
            'disaster_key' => $oldKey,
            'status' => 'active',
            'ended_at' => null,
        ]);

        // レベル5の新しいSafetyCheckが作成される。
        $this->assertDatabaseHas('safety_checks', [
            'disaster_key' => $state->active_disaster_key,
            'status' => 'active',
            'ended_at' => null,
        ]);

        $this->assertSame(
            1,
            SafetyCheck::where('type', 'disaster')->count()
        );

        $this->assertSame(
            1,
            SafetyCheck::where('type', 'production')->count()
        );
    }


    public function test_repeated_level_4_warning_does_not_create_duplicate_safety_check(): void
    {
        $disasterKey = 'weather-warning:1310100:test-repeat-level-4';

        $state = WeatherWarningState::create([
            'area_code' => '1310100',
            'area_name' => 'テスト区',
            'warning_name' => 'レベル４大雨危険警報',
            'is_active' => true,
            'active_disaster_key' => $disasterKey,
        ]);

        $user = \App\Models\User::factory()->create();

        SafetyCheck::create([
            'disaster_key' => $disasterKey,
            'title' => 'テスト区 大雨警報による安否確認',
            'message' => '既存のレベル4確認',
            'type' => 'disaster',
            'status' => 'active',
            'started_at' => now(),
            'ended_at' => null,
            'created_by' => $user->id,
        ]);

        $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<Report xmlns="http://xml.kishou.go.jp/jmaxml1/"
        xmlns:head="http://xml.kishou.go.jp/jmaxml1/informationBasis1/"
        xmlns:body="http://xml.kishou.go.jp/jmaxml1/body/meteorology1/">
  <head:Title>気象警報・注意報</head:Title>
  <head:ReportDateTime>2026-10-22T10:00:00+09:00</head:ReportDateTime>
  <body:Warning type="気象警報・注意報（市町村等）">
    <body:Item>
      <body:Area>
        <body:Name>テスト区</body:Name>
        <body:Code>1310100</body:Code>
      </body:Area>
      <body:Kind>
        <body:Name>レベル４大雨危険警報</body:Name>
        <body:Status>発表</body:Status>
      </body:Kind>
    </body:Item>
  </body:Warning>
</Report>
XML;

        $file = tempnam(sys_get_temp_dir(), 'jma-repeat-level4-');
        file_put_contents($file, $xml);

        try {
            $this->artisan('app:inspect-jma-feeds', [
                '--file' => $file,
            ])->assertExitCode(0);
        } finally {
            @unlink($file);
        }

        $state->refresh();

        $this->assertTrue((bool) $state->is_active);
        $this->assertSame($disasterKey, $state->active_disaster_key);
        $this->assertSame(
            'レベル４大雨危険警報',
            $state->warning_name
        );

        $this->assertSame(
            1,
            SafetyCheck::where('type', 'disaster')->count()
        );
    }


    public function test_level_5_downgrade_to_level_4_keeps_level_5_state_without_creating_duplicate(): void
    {
        $disasterKey = 'weather-warning:1310100:test-level-5-downgrade';

        $state = WeatherWarningState::create([
            'area_code' => '1310100',
            'area_name' => 'テスト区',
            'warning_name' => 'レベル５大雨特別警報',
            'is_active' => true,
            'active_disaster_key' => $disasterKey,
        ]);

        $user = \App\Models\User::factory()->create();

        SafetyCheck::create([
            'disaster_key' => $disasterKey,
            'title' => 'テスト区 大雨警報による安否確認',
            'message' => 'レベル5の安否確認',
            'type' => 'production',
            'status' => 'active',
            'started_at' => now(),
            'ended_at' => null,
            'created_by' => $user->id,
        ]);

        $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<Report xmlns="http://xml.kishou.go.jp/jmaxml1/"
        xmlns:head="http://xml.kishou.go.jp/jmaxml1/informationBasis1/"
        xmlns:body="http://xml.kishou.go.jp/jmaxml1/body/meteorology1/">
  <head:Title>気象警報・注意報</head:Title>
  <head:ReportDateTime>2026-10-22T11:00:00+09:00</head:ReportDateTime>
  <body:Warning type="気象警報・注意報（市町村等）">
    <body:Item>
      <body:Area>
        <body:Name>テスト区</body:Name>
        <body:Code>1310100</body:Code>
      </body:Area>
      <body:Kind>
        <body:Name>レベル４大雨危険警報</body:Name>
        <body:Status>発表</body:Status>
        <body:LastKind>
          <body:Name>レベル５大雨特別警報</body:Name>
        </body:LastKind>
      </body:Kind>
    </body:Item>
  </body:Warning>
</Report>
XML;

        $file = tempnam(sys_get_temp_dir(), 'jma-level5-to-4-');
        file_put_contents($file, $xml);

        try {
            $this->artisan('app:inspect-jma-feeds', [
                '--file' => $file,
            ])->assertExitCode(0);
        } finally {
            @unlink($file);
        }

        $state->refresh();

        $this->assertTrue((bool) $state->is_active);
        $this->assertSame(
            'レベル５大雨特別警報',
            $state->warning_name
        );
        $this->assertSame(
            $disasterKey,
            $state->active_disaster_key
        );

        $this->assertSame(
            1,
            SafetyCheck::where('type', 'production')->count()
        );

        $this->assertDatabaseHas('safety_checks', [
            'disaster_key' => $disasterKey,
            'status' => 'active',
            'ended_at' => null,
        ]);
    }
    public function test_reissued_level_4_warning_after_release_creates_new_safety_check(): void
    {
        $oldKey = 'weather-warning:1310100:test-reissue-old';

        $state = WeatherWarningState::create([
            'area_code' => '1310100',
            'area_name' => 'テスト区',
            'warning_name' => 'レベル４大雨危険警報',
            'is_active' => true,
            'active_disaster_key' => $oldKey,
        ]);

        $user = \App\Models\User::factory()->create();

        SafetyCheck::create([
            'disaster_key' => $oldKey,
            'title' => 'テスト区 大雨警報による安否確認',
            'message' => '最初の安否確認',
            'type' => 'production',
            'status' => 'active',
            'started_at' => now(),
            'ended_at' => null,
            'created_by' => $user->id,
        ]);

        // 1回目：Level 4 警報から注意報への移行
        $releaseXml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<Report xmlns="http://xml.kishou.go.jp/jmaxml1/"
        xmlns:head="http://xml.kishou.go.jp/jmaxml1/informationBasis1/"
        xmlns:body="http://xml.kishou.go.jp/jmaxml1/body/meteorology1/">
  <head:Title>気象警報・注意報</head:Title>
  <head:ReportDateTime>2026-10-22T11:00:00+09:00</head:ReportDateTime>
  <body:Warning type="気象警報・注意報（市町村等）">
    <body:Item>
      <body:Area>
        <body:Name>テスト区</body:Name>
        <body:Code>1310100</body:Code>
      </body:Area>
      <body:Kind>
        <body:Name>大雨注意報</body:Name>
        <body:Status>警報から注意報</body:Status>
        <body:LastKind>
          <body:Name>レベル４大雨危険警報</body:Name>
        </body:LastKind>
      </body:Kind>
    </body:Item>
  </body:Warning>
</Report>
XML;

        $releaseFile = tempnam(sys_get_temp_dir(), 'jma-reissue-release-');
        file_put_contents($releaseFile, $releaseXml);

        try {
            $this->artisan('app:inspect-jma-feeds', [
                '--file' => $releaseFile,
            ])->assertExitCode(0);
        } finally {
            @unlink($releaseFile);
        }

        $state->refresh();

        $this->assertFalse((bool) $state->is_active);
        $this->assertNull($state->active_disaster_key);

        // 解除されても、以前の安否確認は自動終了しない。
        $this->assertDatabaseHas('safety_checks', [
            'disaster_key' => $oldKey,
            'status' => 'active',
            'ended_at' => null,
        ]);

        // 2回目：Level 4 警報が再発表される
        $reissueXml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<Report xmlns="http://xml.kishou.go.jp/jmaxml1/"
        xmlns:head="http://xml.kishou.go.jp/jmaxml1/informationBasis1/"
        xmlns:body="http://xml.kishou.go.jp/jmaxml1/body/meteorology1/">
  <head:Title>気象警報・注意報</head:Title>
  <head:ReportDateTime>2026-10-22T12:00:00+09:00</head:ReportDateTime>
  <body:Warning type="気象警報・注意報（市町村等）">
    <body:Item>
      <body:Area>
        <body:Name>テスト区</body:Name>
        <body:Code>1310100</body:Code>
      </body:Area>
      <body:Kind>
        <body:Name>レベル４大雨危険警報</body:Name>
        <body:Status>発表</body:Status>
      </body:Kind>
    </body:Item>
  </body:Warning>
</Report>
XML;

        $reissueFile = tempnam(sys_get_temp_dir(), 'jma-reissue-new-');
        file_put_contents($reissueFile, $reissueXml);

        try {
            $this->artisan('app:inspect-jma-feeds', [
                '--file' => $reissueFile,
            ])->assertExitCode(0);
        } finally {
            @unlink($reissueFile);
        }

        $state->refresh();

        $this->assertTrue((bool) $state->is_active);
        $this->assertSame(
            'レベル４大雨危険警報',
            $state->warning_name
        );
        $this->assertNotSame($oldKey, $state->active_disaster_key);

        $this->assertSame(
            2,
            SafetyCheck::where('type', 'production')->count()
        );

        $this->assertDatabaseHas('safety_checks', [
            'disaster_key' => $oldKey,
            'status' => 'active',
            'ended_at' => null,
        ]);

        $this->assertDatabaseHas('safety_checks', [
            'disaster_key' => $state->active_disaster_key,
            'status' => 'active',
            'ended_at' => null,
        ]);
    }


    public function test_flood_warning_creates_safety_check(): void
    {
        $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<Report xmlns="http://xml.kishou.go.jp/jmaxml1/"
        xmlns:head="http://xml.kishou.go.jp/jmaxml1/informationBasis1/"
        xmlns:body="http://xml.kishou.go.jp/jmaxml1/body/meteorology1/">
  <head:Title>気象警報・注意報</head:Title>
  <head:ReportDateTime>2026-10-22T12:00:00+09:00</head:ReportDateTime>
  <body:Warning type="気象警報・注意報（市町村等）">
    <body:Item>
      <body:Area>
        <body:Name>テスト区</body:Name>
        <body:Code>1310100</body:Code>
      </body:Area>
      <body:Kind>
        <body:Name>洪水警報</body:Name>
        <body:Status>発表</body:Status>
      </body:Kind>
    </body:Item>
  </body:Warning>
</Report>
XML;

        $file = tempnam(sys_get_temp_dir(), 'jma-flood-');
        file_put_contents($file, $xml);

        try {
            $this->artisan('app:inspect-jma-feeds', [
                '--file' => $file,
            ])->assertExitCode(0);
        } finally {
            @unlink($file);
        }

        $state = WeatherWarningState::where('area_code', '1310100')
            ->where('warning_type', 'flood')
            ->first();

        $this->assertNotNull($state);
        $this->assertTrue((bool) $state->is_active);
        $this->assertSame('洪水警報', $state->warning_name);
        $this->assertNotNull($state->active_disaster_key);

        $this->assertDatabaseHas('safety_checks', [
            'disaster_key' => $state->active_disaster_key,
            'title' => 'テスト区 洪水警報による安否確認',
            'status' => 'active',
            'ended_at' => null,
        ]);
    }

    public function test_repeated_flood_warning_does_not_create_duplicate_safety_check(): void
    {
        $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<Report xmlns="http://xml.kishou.go.jp/jmaxml1/"
        xmlns:head="http://xml.kishou.go.jp/jmaxml1/informationBasis1/"
        xmlns:body="http://xml.kishou.go.jp/jmaxml1/body/meteorology1/">
  <head:Title>気象警報・注意報</head:Title>
  <head:ReportDateTime>2026-10-22T12:00:00+09:00</head:ReportDateTime>
  <body:Warning type="気象警報・注意報（市町村等）">
    <body:Item>
      <body:Area>
        <body:Name>テスト区</body:Name>
        <body:Code>1310100</body:Code>
      </body:Area>
      <body:Kind>
        <body:Name>洪水警報</body:Name>
        <body:Status>発表</body:Status>
      </body:Kind>
    </body:Item>
  </body:Warning>
</Report>
XML;

        $file = tempnam(sys_get_temp_dir(), 'jma-flood-');
        file_put_contents($file, $xml);

        try {
            $this->artisan('app:inspect-jma-feeds', [
                '--file' => $file,
            ])->assertExitCode(0);

            $this->artisan('app:inspect-jma-feeds', [
                '--file' => $file,
            ])->assertExitCode(0);
        } finally {
            @unlink($file);
        }

        $state = WeatherWarningState::where('area_code', '1310100')
            ->where('warning_type', 'flood')
            ->first();

        $this->assertNotNull($state);
        $this->assertTrue((bool) $state->is_active);
        $this->assertSame('洪水警報', $state->warning_name);
        $this->assertNotNull($state->active_disaster_key);

        $this->assertDatabaseHas('safety_checks', [
            'disaster_key' => $state->active_disaster_key,
            'title' => 'テスト区 洪水警報による安否確認',
            'status' => 'active',
            'ended_at' => null,
        ]);

        $this->assertSame(
            1,
            SafetyCheck::where('type', 'production')
                ->where('title', 'テスト区 洪水警報による安否確認')
                ->count()
        );
    }


    public function test_flood_warning_release_resets_state_without_closing_safety_check(): void
    {
        $disasterKey = 'weather-warning:flood:1310100:test-release';

        $state = WeatherWarningState::create([
            'area_code' => '1310100',
            'warning_type' => 'flood',
            'area_name' => 'テスト区',
            'warning_name' => '洪水警報',
            'is_active' => true,
            'active_disaster_key' => $disasterKey,
        ]);

        $user = \App\Models\User::factory()->create();

        SafetyCheck::create([
            'disaster_key' => $disasterKey,
            'title' => 'テスト区 洪水警報による安否確認',
            'message' => '洪水警報の解除テスト',
            'type' => 'production',
            'status' => 'active',
            'started_at' => now(),
            'ended_at' => null,
            'created_by' => $user->id,
        ]);

        $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<Report xmlns="http://xml.kishou.go.jp/jmaxml1/"
        xmlns:head="http://xml.kishou.go.jp/jmaxml1/informationBasis1/"
        xmlns:body="http://xml.kishou.go.jp/jmaxml1/body/meteorology1/">
  <head:Title>気象警報・注意報</head:Title>
  <head:ReportDateTime>2026-10-22T13:00:00+09:00</head:ReportDateTime>
  <body:Warning type="気象警報・注意報（市町村等）">
    <body:Item>
      <body:Area>
        <body:Name>テスト区</body:Name>
        <body:Code>1310100</body:Code>
      </body:Area>
      <body:Kind>
        <body:Name>洪水警報</body:Name>
        <body:Status>解除</body:Status>
        <body:LastKind>
          <body:Name>洪水警報</body:Name>
        </body:LastKind>
      </body:Kind>
    </body:Item>
  </body:Warning>
</Report>
XML;

        $file = tempnam(sys_get_temp_dir(), 'jma-flood-release-');
        file_put_contents($file, $xml);

        try {
            $this->artisan('app:inspect-jma-feeds', [
                '--file' => $file,
            ])->assertExitCode(0);
        } finally {
            @unlink($file);
        }

        $state->refresh();

        $this->assertFalse((bool) $state->is_active);
        $this->assertNull($state->active_disaster_key);

        $this->assertDatabaseHas('safety_checks', [
            'disaster_key' => $disasterKey,
            'status' => 'active',
            'ended_at' => null,
        ]);
    }


    public function test_flood_warning_reissued_after_release_creates_new_safety_check(): void
    {
        $firstXml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<Report xmlns="http://xml.kishou.go.jp/jmaxml1/"
        xmlns:head="http://xml.kishou.go.jp/jmaxml1/informationBasis1/"
        xmlns:body="http://xml.kishou.go.jp/jmaxml1/body/meteorology1/">
  <head:Title>気象警報・注意報</head:Title>
  <head:ReportDateTime>2026-10-22T13:00:00+09:00</head:ReportDateTime>
  <body:Warning type="気象警報・注意報（市町村等）">
    <body:Item>
      <body:Area>
        <body:Name>テスト区</body:Name>
        <body:Code>1310100</body:Code>
      </body:Area>
      <body:Kind>
        <body:Name>洪水警報</body:Name>
        <body:Status>発表</body:Status>
      </body:Kind>
    </body:Item>
  </body:Warning>
</Report>
XML;

        $releaseXml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<Report xmlns="http://xml.kishou.go.jp/jmaxml1/"
        xmlns:head="http://xml.kishou.go.jp/jmaxml1/informationBasis1/"
        xmlns:body="http://xml.kishou.go.jp/jmaxml1/body/meteorology1/">
  <head:Title>気象警報・注意報</head:Title>
  <head:ReportDateTime>2026-10-22T14:00:00+09:00</head:ReportDateTime>
  <body:Warning type="気象警報・注意報（市町村等）">
    <body:Item>
      <body:Area>
        <body:Name>テスト区</body:Name>
        <body:Code>1310100</body:Code>
      </body:Area>
      <body:Kind>
        <body:Name>洪水警報</body:Name>
        <body:Status>解除</body:Status>
        <body:LastKind>
          <body:Name>洪水警報</body:Name>
        </body:LastKind>
      </body:Kind>
    </body:Item>
  </body:Warning>
</Report>
XML;

        $reissuedXml = str_replace(
            '2026-10-22T13:00:00+09:00',
            '2026-10-22T15:00:00+09:00',
            $firstXml
        );

        $files = [];

        try {
            foreach ([$firstXml, $releaseXml, $reissuedXml] as $xml) {
                $file = tempnam(sys_get_temp_dir(), 'jma-flood-reissue-');

                file_put_contents($file, $xml);
                $files[] = $file;

                $this->artisan('app:inspect-jma-feeds', [
                    '--file' => $file,
                ])->assertExitCode(0);
            }
        } finally {
            foreach ($files as $file) {
                @unlink($file);
            }
        }

        $title = 'テスト区 洪水警報による安否確認';

        $this->assertDatabaseCount('safety_checks', 2);

        $this->assertDatabaseHas('safety_checks', [
            'title' => $title,
            'status' => 'active',
            'ended_at' => null,
        ]);

        $this->assertDatabaseHas('weather_warning_states', [
            'area_code' => '1310100',
            'warning_type' => 'flood',
            'is_active' => true,
        ]);
    }
}
