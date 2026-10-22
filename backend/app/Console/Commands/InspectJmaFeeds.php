<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\SafetyCheck;
use App\Models\WeatherWarningState;
use Illuminate\Support\Str;

#[Signature('app:inspect-jma-feeds {--file= : 読み込むXMLファイルのパス} {--test-typhoon : 台風確率の判定テスト} {--test-weather-status : 気象警報の状態判定テスト} {--dry-run : DBを更新せずに判定だけ行う}')]
#[Description('Command description')]
class InspectJmaFeeds extends Command
{
    private const HEAD_NS =
    'http://xml.kishou.go.jp/jmaxml1/informationBasis1/';

    private const SEISMIC_NS =
    'http://xml.kishou.go.jp/jmaxml1/body/seismology1/';

    private const METEOROLOGY_NS =
    'http://xml.kishou.go.jp/jmaxml1/body/meteorology1/';

    private const ATOM_NS =
    'http://www.w3.org/2005/Atom';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if ($this->option('test-weather-status')) {
            foreach (['発表', '継続', '解除', 'その他'] as $status) {
                $isIssued = in_array($status, [
                    '発表',
                    '継続',
                ], true);

                $result = $isIssued
                    ? '[対象警報]'
                    : ($status === '解除'
                        ? '[解除・対象外]'
                        : '[対象外]');

                $this->line("状態: {$status} => {$result}");
            }

            return self::SUCCESS;
        }

        if ($this->option('test-typhoon')) {
            $this->testTyphoonProbabilityRules();

            return self::SUCCESS;
        }

        $file = $this->option('file');

        // サンプルXMLの解析モード
        if ($file) {
            return $this->inspectSample($file);
        }

        $feeds = [
            '地震・火山' =>
            'https://www.data.jma.go.jp/developer/xml/feed/eqvol.xml',
            '気象情報' =>
            'https://www.data.jma.go.jp/developer/xml/feed/extra.xml',
        ];

        $hasError = false;

        $seenTyphoonAnnouncements = [];

        foreach ($feeds as $name => $url) {
            $this->newLine();
            $this->info("【{$name}】");

            try {
                $response = Http::timeout(15)->get($url);

                if (! $response->successful()) {
                    $this->error(
                        'フィード取得失敗: HTTP ' . $response->status()
                    );
                    $hasError = true;
                    continue;
                }

                $xml = simplexml_load_string(
                    $response->body(),
                    \SimpleXMLElement::class,
                    LIBXML_NONET
                );

                if ($xml === false) {
                    $this->error('フィードXMLの解析に失敗しました。');
                    $hasError = true;
                    continue;
                }

                $entries = $xml->children(self::ATOM_NS)->entry;

                $this->line('電文数: ' . count($entries));

                $targetCount = 0;

                foreach ($entries as $entry) {
                    $item = $entry->children(self::ATOM_NS);
                    $xmlUrl = '';

                    foreach ($item->link as $link) {
                        $attributes = $link->attributes();

                        if (
                            (string) $attributes['type']
                            === 'application/xml'
                        ) {
                            $xmlUrl = (string) $attributes['href'];
                            break;
                        }
                    }

                    $isEarthquake = str_contains(
                        $xmlUrl,
                        '_VXSE53_'
                    );

                    $isTyphoon = str_contains(
                        $xmlUrl,
                        '_VPTW60_'
                    );

                    $isTyphoonProbability = str_contains(
                        $xmlUrl,
                        '_VPTA50_'
                    );

                    $isTyphoonGeneral = str_contains(
                        $xmlUrl,
                        'VPZJ51'
                    );

                    $isWeatherWarning = str_contains($xmlUrl, 'VPWW');

                    if ($isTyphoonGeneral) {
                        $title = (string) $item->title;

                        $this->line('後継候補を発見: ' . $title);
                        $this->line('XML URL: ' . $xmlUrl);
                    }


                    $title = trim((string) $item->title);

                    // 台風関連の電文を探すための一時的な調査
                    if (
                        str_contains($title, '台風')
                        || str_contains($xmlUrl, 'VPT')
                        || str_contains($xmlUrl, 'VPWW')
                    ) {
                        $this->line('--- 台風関連候補 ---');
                        $this->line('タイトル: ' . $title);
                        $this->line('XML URL: ' . $xmlUrl);
                    }


                    // 地震・台風以外のXMLは処理しない
                    if (
                        ! $isEarthquake
                        && ! $isTyphoon
                        && ! $isTyphoonProbability
                        && ! $isWeatherWarning
                    ) {
                        continue;
                    }

                    $targetCount++;

                    try {
                        // 対象XMLは1回だけ取得する
                        $detail = Http::timeout(15)->get($xmlUrl);

                        if (! $detail->successful()) {
                            $this->error(
                                'XML本文の取得失敗: HTTP '
                                    . $detail->status()
                            );
                            $hasError = true;
                            continue;
                        }

                        $dom = new \DOMDocument();

                        if (
                            ! $dom->loadXML(
                                $detail->body(),
                                LIBXML_NONET
                            )
                        ) {
                            $this->error('XML本文の解析に失敗しました。');
                            $hasError = true;
                            continue;
                        }

                        $xpath = new \DOMXPath($dom);

                        $xpath->registerNamespace(
                            'head',
                            self::HEAD_NS
                        );


                        if ($isEarthquake) {
                            $xpath->registerNamespace('body', self::SEISMIC_NS);
                            $this->inspectEarthquake($xpath);
                        } elseif ($isWeatherWarning) {
                            $xpath->registerNamespace('body', self::METEOROLOGY_NS);
                            $this->inspectWeatherWarnings($xpath);
                        } else {
                            $xpath->registerNamespace('body', self::METEOROLOGY_NS);

                            // 台風の発表は、電文の種類ごとに最新のものだけ処理する
                            $eventId = $xpath->evaluate('string(//head:EventID)');
                            $serial = $xpath->evaluate('string(//head:Serial)');

                            $announcementType = $isTyphoonProbability
                                ? 'VPTA50'
                                : 'VPTW60';

                            if (
                                ($isTyphoon || $isTyphoonProbability)
                                && $eventId !== ''
                            ) {
                                $key = $announcementType . ':' . $eventId;

                                if (isset($seenTyphoonAnnouncements[$key])) {
                                    $this->line(
                                        "古い発表をスキップ: {$eventId} / "
                                            . "{$announcementType} / 発表番号 {$serial}"
                                    );
                                    continue;
                                }

                                // Atomフィードは新しい発表から並ぶため、最初の発表を採用
                                $seenTyphoonAnnouncements[$key] = true;
                            }

                            if ($isTyphoonProbability) {
                                $this->inspectTyphoonProbability($xpath);
                            } else {
                                $this->inspectTyphoon($xpath);
                            }
                        }
                    } catch (\Throwable $e) {
                        $this->error(
                            '対象XMLの処理エラー: ' . $e->getMessage()
                        );
                        $hasError = true;
                    }
                }

                $this->line('対象XML数: ' . $targetCount);
            } catch (\Throwable $e) {
                $this->error(
                    'フィード取得エラー: ' . $e->getMessage()
                );
                $hasError = true;
            }
        }

        return $hasError ? self::FAILURE : self::SUCCESS;
    }

    private function inspectSample(string $file): int
    {
        $path = str_starts_with($file, '/')
            ? $file
            : base_path($file);

        if (! is_file($path)) {
            $this->error("ファイルが見つかりません: {$path}");
            return self::FAILURE;
        }

        $content = file_get_contents($path);

        if ($content === false) {
            $this->error('ファイルの読み込みに失敗しました。');
            return self::FAILURE;
        }

        $dom = new \DOMDocument();

        if (! $dom->loadXML($content, LIBXML_NONET)) {
            $this->error('XMLの解析に失敗しました。');
            return self::FAILURE;
        }

        $this->info('サンプルXMLを読み込みました: ' . $path);

        $xpath = new \DOMXPath($dom);

        $xpath->registerNamespace('head', self::HEAD_NS);

        $root = trim($xpath->evaluate('string(//head:Title)'));

        if (
            str_contains($root, '気象警報・注意報')
            || str_contains($root, '大雨警報・注意報')
        ) {
            $xpath->registerNamespace('body', self::METEOROLOGY_NS);

            $this->inspectWeatherWarnings($xpath);
        } else {
            $xpath->registerNamespace('body', self::SEISMIC_NS);

            $this->inspectEarthquake($xpath);
        }

        return self::SUCCESS;
    }

    private function inspectEarthquake(
        \DOMXPath $xpath
    ): void {
        $eventId = trim(
            $xpath->evaluate('string(//head:EventID)')
        );

        $maxInt = trim(
            $xpath->evaluate(
                'string(//body:Intensity/body:Observation/body:MaxInt)'
            )
        );

        $reportDateTime = trim(
            $xpath->evaluate('string(//head:ReportDateTime)')
        );

        if ($reportDateTime === '') {
            $this->warn('発表時刻がないため、本番作成をスキップします。');
            return;
        }

        $reportedAt = \Illuminate\Support\Carbon::parse($reportDateTime);

        $this->line('発表時刻: ' . $reportedAt->toDateTimeString());

        $isTarget = in_array(
            $maxInt,
            ['5-', '5+', '6-', '6+', '7'],
            true
        );

        $this->line('イベントID: ' . ($eventId !== '' ? $eventId : '未取得'));
        $this->line('最大震度: ' . ($maxInt !== '' ? $maxInt : '未取得'));
        $this->line(
            '震度5弱以上: ' . ($isTarget ? '対象' : '対象外')
        );

        if (! $isTarget) {
            return;
        }

        if ($reportedAt->lt(now()->subMinutes(30))) {
            $this->line('古い地震情報のため、本番作成をスキップします。');
            return;
        }

        if ($eventId === '') {
            $this->warn('イベントIDがないため、本番の安否確認は作成しません。');
            return;
        }

        if ($this->option('dry-run')) {
            $this->line('[DRY RUN] 本番作成をスキップします。');
            return;
        }

        $safetyCheck = app(SafetyCheck::class)
            ->createProduction([
                'disaster_key' => 'earthquake:' . $eventId,
                'title' => '地震発生に伴う安否確認',
                'message' => "気象庁の地震情報で最大震度{$maxInt}を観測しました。現在の状況を回答してください。",
            ]);

        if ($safetyCheck->wasRecentlyCreated) {
            $this->info(
                '本番の安否確認を作成しました。ID: ' . $safetyCheck->id
            );
        } else {
            $this->line(
                '同じ地震の安否確認は作成済みです。ID: ' . $safetyCheck->id
            );
        }
    }

    private function inspectTyphoon(
        \DOMXPath $xpath
    ): void {
        $eventId = $xpath->evaluate('string(//head:EventID)');
        $serial = $xpath->evaluate('string(//head:Serial)');

        $this->line('イベントID: ' . $eventId);
        $this->line('発表番号: ' . $serial);

        $infos = $xpath->query('//body:MeteorologicalInfo');

        foreach ($infos as $info) {
            $date = $xpath->evaluate(
                'string(body:DateTime)',
                $info
            );

            $this->line('予報日時: ' . $date);

            $coordinates = $xpath->query(
                './/*[local-name()="BasePoint"]',
                $info
            );

            $seenCoordinates = [];

            foreach ($coordinates as $coordinate) {
                $raw = trim($coordinate->textContent);

                if (! preg_match(
                    '/^([+-]\d{1,2}\.\d+)([+-]\d{1,3}\.\d+)\/?$/',
                    $raw,
                    $matches
                )) {
                    continue;
                }

                $latitude = (float) $matches[1];
                $longitude = (float) $matches[2];

                $key = $latitude . ',' . $longitude;

                if (isset($seenCoordinates[$key])) {
                    continue;
                }

                $seenCoordinates[$key] = true;

                $this->line(
                    "中心座標: 緯度 {$latitude}, 経度 {$longitude}"
                );
            }

            $locations = $xpath->query(
                './/*[local-name()="Location"]',
                $info
            );

            foreach ($locations as $location) {
                $value = trim($location->textContent);

                if ($value !== '') {
                    $this->line('位置: ' . $value);
                }
            }
        }
    }

    private function inspectTyphoonProbability(
        \DOMXPath $xpath
    ): void {
        $eventId = $xpath->evaluate(
            'string(//head:EventID)'
        );

        $serial = $xpath->evaluate(
            'string(//head:Serial)'
        );

        $reportDateTime = $xpath->evaluate(
            'string(//head:ReportDateTime)'
        );

        $this->info('=== 台風の暴風域に入る確率 ===');
        $this->line('台風ID: ' . $eventId);
        $this->line('発表番号: ' . $serial);
        $this->line('発表時刻: ' . $reportDateTime);

        $infos = $xpath->query(
            '//body:MeteorologicalInfo[contains(@type, "確率")]'
        );

        if ($infos === false) {
            $this->error('確率データの検索に失敗しました。');
            return;
        }

        foreach ($infos as $info) {
            $type = $info->getAttribute('type');

            $this->line('');
            $this->line('--- ' . $type . ' ---');

            $items = $xpath->query(
                './body:Item',
                $info
            );

            if ($items === false) {
                continue;
            }

            $rows = [];

            foreach ($items as $item) {
                $this->line('取得した地域数: ' . $items->length);
                $name = $xpath->evaluate(
                    'string(./body:Area/body:Name)',
                    $item
                );

                $probability = $xpath->evaluate(
                    'string(.//body:FiftyKtWindProbability)',
                    $item
                );

                if ($probability === '') {
                    $this->line('確率が空: ' . $name);
                }

                if ($name === '' || $probability === '') {
                    continue;
                }

                $rows[] = [
                    'name' => $name,
                    'probability' => (float) $probability,
                ];
            }

            usort(
                $rows,
                fn($a, $b) =>
                $b['probability'] <=> $a['probability']
            );

            $isTargetPeriod = preg_match('/[1-5]日積算/u', $type) === 1;

            $matchedRows = [];

            foreach ($rows as $row) {
                $probability = (int) $row['probability'];

                if ($this->option('dry-run')) {
                    $state = \App\Models\TyphoonNotificationState::where(
                        'event_id',
                        $eventId
                    )->where(
                        'region_name',
                        $row['name']
                    )->where(
                        'forecast_period',
                        $type
                    )->first();

                    $shouldNotify = $this->shouldNotifyTyphoonProbability(
                        null,
                        $probability,
                        true
                    );

                    $this->line('状態確認: ' . $row['name'] . ' / ' . $type . ' / 前回=' . ($state?->last_notified_probability ?? 'なし') . ' / below=' . ($state?->below_threshold === null ? 'なし' : (int) $state->below_threshold));

                    $this->line(
                        '[DRY RUN] '
                            . $row['name'] . ' / '
                            . $type . ' / '
                            . $probability . '%'
                            . ' / 判定: '
                            . ($shouldNotify ? '通知候補' : '通知なし')
                            . ' / DB更新なし'
                    );
                } else {
                    $shouldNotify = false;

                    $this->line(
                        '[メール未実装] 通知状態の更新をスキップ: '
                            . $row['name'] . ' / '
                            . $type . ' / '
                            . $probability . '%'
                    );
                }

                if ($shouldNotify) {
                    $matchedRows[] = $row;
                }

                if ($probability < 10) {
                    continue;
                }

                $this->line(
                    $row['name'] . ': ' . $probability . '%'
                        . ' / 通知判定: '
                        . (
                            $isTargetPeriod
                            ? ($shouldNotify ? '通知候補' : '通知なし')
                            : '対象外期間'
                        )
                );
            }

            if ($isTargetPeriod && $matchedRows !== []) {
                $this->line('');
                $this->warn('--- 全国通知候補（ログのみ）---');
                $this->line('台風ID: ' . $eventId);
                $this->line('判定期間: ' . $type);
                $this->line(
                    '該当地域: '
                        . implode(
                            '、',
                            array_map(
                                fn($row) => $row['name']
                                    . ' ' . $row['probability'] . '%',
                                $matchedRows
                            )
                        )
                );
                $this->line('対象: 全国の登録ユーザー');
                $this->line('メール送信: まだ実行しない');
            }
        }
    }

    private function shouldNotifyTyphoonProbability(
        ?int $lastNotifiedProbability,
        int $currentProbability,
        bool $belowThreshold
    ): bool {
        // 50%未満は通知対象外
        if ($currentProbability < 50) {
            return false;
        }

        // まだ一度も通知していない
        if ($belowThreshold || $lastNotifiedProbability === null) {
            return true;
        }

        // 70%以上は、前回通知した確率から上がれば通知
        if ($currentProbability >= 70) {
            return $currentProbability > $lastNotifiedProbability;
        }

        // 50～69%は、前回通知した確率から10ポイント以上上昇で通知
        return $currentProbability >= $lastNotifiedProbability + 10;
    }

    private function updateTyphoonProbabilityState(
        string $eventId,
        string $regionName,
        string $forecastPeriod,
        int $currentProbability,
        string $serial
    ): bool {
        $state = \App\Models\TyphoonNotificationState::firstOrCreate(
            [
                'event_id' => $eventId,
                'region_name' => $regionName,
                'forecast_period' => $forecastPeriod,
            ],
            [
                'latest_probability' => $currentProbability,
                'last_notified_probability' => null,
                'last_notified_serial' => null,
                'below_threshold' => true,
            ]
        );

        $lastNotified = $state->last_notified_probability;
        $belowThreshold = $state->below_threshold;

        // 現在の確率を保存
        $state->latest_probability = $currentProbability;

        // 50%未満なら次回の50%以上を初回通知として扱う
        if ($currentProbability < 50) {
            $state->last_notified_probability = null;
            $state->last_notified_serial = null;
            $state->below_threshold = true;
            $state->save();

            return false;
        }

        $shouldNotify = $this->shouldNotifyTyphoonProbability(
            $lastNotified,
            $currentProbability,
            $belowThreshold
        );

        if ($shouldNotify) {
            $state->last_notified_probability = $currentProbability;
            $state->last_notified_serial = $serial;
            $state->below_threshold = false;
        }

        $state->save();

        return $shouldNotify;
    }

    private function testTyphoonProbabilityRules(): void
    {
        $eventId = 'TEST-TYPHOON-' . uniqid();
        $region = 'テスト地域';
        $period = 'テスト期間';

        $cases = [
            ['初回50%', 50, true],
            ['50→55%', 55, false],
            ['50→60%', 60, true],
            ['60→65%', 65, false],
            ['60→70%', 70, true],
            ['70→75%', 75, true],
            ['75→75%', 75, false],
            ['49%に低下', 49, false],
            ['49→50%で再通知', 50, true],
        ];

        foreach ($cases as [$label, $probability, $expected]) {
            $actual = $this->updateTyphoonProbabilityState(
                $eventId,
                $region,
                $period,
                $probability,
                'TEST-SERIAL'
            );

            $result = $actual === $expected ? 'OK' : 'NG';

            $this->line(
                "{$result}: {$label}"
                    . ' / 期待=' . ($expected ? '通知候補' : '通知なし')
                    . ' / 実際=' . ($actual ? '通知候補' : '通知なし')
            );
        }

        \App\Models\TyphoonNotificationState::where(
            'event_id',
            $eventId
        )->delete();

        $this->line('テスト用DBレコードを削除しました。');
    }



    private function inspectWeatherWarnings(\DOMXPath $xpath): void
    {
        $this->line(
            '[電文情報] / タイトル: '
                . $xpath->evaluate('string(//head:Title)')
                . ' / 発表日時: '
                . $xpath->evaluate('string(//head:ReportDateTime)')
        );

        $items = $xpath->query(
            '//*[local-name()="Warning" or local-name()="Information"]'
                . '[@type="気象警報・注意報（市町村等）'
                . '" or @type="気象警報・注意報（府県予報区等）"]'
                . '/*[local-name()="Item"]'
        );

        if ($items === false) {
            $this->warn('市町村単位のWarningを取得できません。');
            return;
        }

        $targets = [
            'レベル４大雨危険警報',
            'レベル５大雨特別警報',
        ];
        $floodTarget = '洪水警報';

        $dryRun = $this->option('dry-run');
        $matched = 0;

        foreach ($items as $item) {
            $area = trim($xpath->evaluate(
                'string(./*[local-name()="Area"]/*[local-name()="Name"])',
                $item
            ));

            $code = trim($xpath->evaluate(
                'string(./*[local-name()="Area"]/*[local-name()="Code"])',
                $item
            ));

            $type = $item->parentNode instanceof \DOMElement
                ? $item->parentNode->getAttribute('type')
                : '';

            $this->line(
                "[電文調査] type={$type}"
                    . " / 地域={$area}"
                    . " / 地域コード={$code}"
            );

            if ($code === '') {
                continue;
            }

            $kinds = $xpath->query('./*[local-name()="Kind"]', $item);

            $activeName = null;
            $activeLevel = 0;
            $released = false;

            $floodActive = false;
            $floodReleased = false;

            if ($kinds !== false) {
                foreach ($kinds as $kind) {
                    $name = trim($xpath->evaluate(
                        'string(./*[local-name()="Name"])',
                        $kind
                    ));

                    $status = trim($xpath->evaluate(
                        'string(./*[local-name()="Status"])',
                        $kind
                    ));

                    $lastName = trim($xpath->evaluate(
                        'string(./*[local-name()="LastKind"]/*[local-name()="Name"])',
                        $kind
                    ));

                    $this->line(
                        "[警報確認] {$area} / 現在={$name}"
                            . " / 状態={$status}"
                            . " / 直前={$lastName}"
                    );

                    // 洪水警報は大雨レベル4・5とは別に判定する。
                    if ($name === $floodTarget) {
                        if ($status !== '解除') {
                            $floodActive = true;
                        }

                        if (
                            $lastName === $floodTarget
                            && (
                                $status === '解除'
                                || in_array($status, [
                                    '警報から注意報',
                                    '警報解除',
                                    '注意報解除',
                                ], true)
                            )
                        ) {
                            $floodReleased = true;
                        }
                    }

                    // 現在のレベル4・5を判定する。
                    // 両方存在する場合はレベル5を優先する。
                    if (
                        in_array($name, $targets, true)
                        && $status !== '解除'
                    ) {
                        $level = $name === 'レベル５大雨特別警報'
                            ? 5
                            : 4;

                        if ($level > $activeLevel) {
                            $activeLevel = $level;
                            $activeName = $name;
                        }
                    }

                    // 直前がレベル4・5で、解除または引き下げられた場合。
                    if (
                        in_array($lastName, $targets, true)
                        && (
                            $status === '解除'
                            || in_array($status, [
                                '警報から注意報',
                                '警報解除',
                                '注意報解除',
                            ], true)
                        )
                    ) {
                        $released = true;
                    }
                }
            }

            $state = WeatherWarningState::where('area_code', $code)
                ->where('warning_type', 'heavy_rain')
                ->first();

            $floodState = WeatherWarningState::where('area_code', $code)
                ->where('warning_type', 'flood')
                ->first();

            if ($activeName !== null) {
                $matched++;

                $previousLevel = match ($state?->warning_name) {
                    'レベル５大雨特別警報' => 5,
                    'レベル４大雨危険警報' => 4,
                    default => 0,
                };

                // 既存の警報状態が有効な場合は、レベルの変化を比較する。
                if ($state && $state->is_active) {
                    if ($activeLevel <= $previousLevel) {
                        // 同じレベルの継続、または引き下げでは作成しない。
                        // レベル5から4に下がった場合も、レベル5の状態を保持する。
                        $this->line(
                            "[継続・新規作成なし] {$area}"
                                . " / 保存レベル={$previousLevel}"
                                . " / 現在レベル={$activeLevel}"
                        );

                        continue;
                    }

                    // 有効な状態から、より高いレベルに上がった場合。
                    $this->line(
                        "[レベル上昇] {$area}"
                            . " / レベル{$previousLevel}→{$activeLevel}"
                    );
                } else {
                    $this->line("[新規作成予定] {$activeName} / {$area}");
                }

                // 初回発表、再発、またはレベル上昇時に新しいキーを発行する。
                $key = 'weather-warning:' . $code . ':' . Str::uuid();

                if (!$dryRun) {
                    (new SafetyCheck())->createProduction([
                        'disaster_key' => $key,
                        'title' => "{$area} 大雨警報による安否確認",
                        'message' => "{$area}で{$activeName}が発表されています。安否状況を報告してください。",
                    ]);

                    WeatherWarningState::updateOrCreate(
                        [
                            'area_code' => $code,
                            'warning_type' => 'heavy_rain',
                        ],
                        [
                            'area_name' => $area,
                            'warning_name' => $activeName,
                            'is_active' => true,
                            'active_disaster_key' => $key,
                        ]
                    );
                }

                continue;
            }

            // 洪水警報の新規作成・重複防止
            if ($floodActive) {
                if ($floodState && $floodState->is_active) {
                    $this->line("[洪水警報・継続] {$area} / 新規作成なし");
                } else {
                    $this->line("[洪水警報・新規作成予定] {$area}");

                    if (!$dryRun) {
                        $key = 'weather-warning:flood:' . $code . ':' . Str::uuid();

                        (new SafetyCheck())->createProduction([
                            'disaster_key' => $key,
                            'title' => "{$area} 洪水警報による安否確認",
                            'message' => "{$area}で洪水警報が発表されています。安否状況を報告してください。",
                        ]);

                        WeatherWarningState::updateOrCreate(
                            [
                                'area_code' => $code,
                                'warning_type' => 'flood',
                            ],
                            [
                                'area_name' => $area,
                                'warning_name' => $floodTarget,
                                'is_active' => true,
                                'active_disaster_key' => $key,
                            ]
                        );
                    }
                }
            }

            // 洪水警報の解除処理
            if (!$floodActive && $floodState && $floodState->is_active) {
                if (!$floodReleased) {
                    $this->line(
                        "[洪水警報・状態維持] {$area} / 解除を確認できません"
                    );
                } else {
                    $this->line(
                        "[洪水警報・状態リセット予定] {$area}"
                            . ' / SafetyCheckは自動終了しません'
                    );

                    if (!$dryRun) {
                        $floodState->update([
                            'is_active' => false,
                            'active_disaster_key' => null,
                        ]);
                    }
                }
            }

            if ($state && $state->is_active) {
                if (!$released) {
                    $this->line(
                        "[継続・状態維持] {$area} / 対象警報の解除を確認できません"
                    );

                    continue;
                }

                $this->line(
                    "[警報状態リセット予定] {$area}"
                        . ' / SafetyCheckは自動終了しません'
                );

                if (!$dryRun) {
                    // SafetyCheckのstatusやended_atは変更しない。
                    // 管理者による手動終了の運用は維持する。
                    $state->update([
                        'is_active' => false,
                        'active_disaster_key' => null,
                    ]);
                }
            }
        }

        $this->line("[Warning対象件数] {$matched}");
        $this->line(
            $dryRun
                ? '[調査専用] DBは更新していません。'
                : '[本番処理] 判定処理が完了しました。'
        );
    }
}
