<?php

namespace RegressionTests\Controllers;

class UsageChartsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * The path relative to this file that contains the expected hashes for this test case.
     *
     * @var string
     */
    const HASH_DIR_REL_PATH = '/../../../artifacts/xdmod/regression/images';

    /**
     * Hash data JSON file absolute path or null.
     *
     * @var string|null
     */
    private static $hashFilePath = null;

    protected static $helper;

    // Used when running in hash-generation mode.
    protected static $imagehashes = array();

    /**
     * Determine which JSON file to use for expected hash data.
     */
    public static function setUpBeforeClass()
    {
        $osInfo = false;
        try {
            $osInfo = parse_ini_file('/etc/os-release');
        } catch (\Exception $e) {
            // if we don't have access to OS related info then that's fine, we'll just use the default expected.json
        }

        $hashFiles = [];

        // If we have OS info available to us then look for an OS specific expected output file based on this info.
        if ($osInfo !== false && isset($osInfo['VERSION_ID']) && isset($osInfo['ID'])) {
            $hashFiles[] = sprintf("expected-%s%s.json", $osInfo['ID'], $osInfo['VERSION_ID']);
        }
        // Otherwise try the default expected.json
        $hashFiles[] = 'expected.json';

        $artifactsDir = realpath(__DIR__ . self::HASH_DIR_REL_PATH);
        foreach($hashFiles as $hashFile) {
            $hashFilePath = "$artifactsDir/$hashFile";
            if (file_exists($hashFilePath)) {
                self::$hashFilePath = $hashFilePath;
                break;
            }
        }

        if (self::$hashFilePath === null) {
            throw new \Exception('Failed to find expected data file.');
        }
    }

    public static function tearDownAfterClass()
    {
        self::$helper->logout();
        if(!empty(self::$imagehashes)) {
            if (getenv('REG_TEST_FORCE_GENERATION') === '1') {
                // Overwrite test data.
                $expectedHashes = json_decode(file_get_contents(self::$hashFilePath), true);
                foreach (self::$imagehashes as $testName => $hash) {
                    $expectedHashes[$testName] = $hash;
                }
                file_put_contents(self::$hashFilePath, json_encode($expectedHashes, JSON_PRETTY_PRINT) . "\n");
            } else {
                // print to stdout rather than, e.g., overwriting
                // the expected results file.
                print json_encode(self::$imagehashes, JSON_PRETTY_PRINT);
            }
        }
    }
    /**
     * @dataProvider chartSettingsProvider
     */
    public function testChartSettings($testName, $input, $expectedHash)
    {
        $postvars = null;
        $response = self::$helper->post('/controllers/user_interface.php', $postvars, $input);

        $imageData = $response[0];
        $actualHash = sha1($imageData);

        if ($expectedHash === false || getenv('REG_TEST_FORCE_GENERATION') === '1') {
            self::$imagehashes[$testName] = $actualHash;
            $this->markTestSkipped('Created Expected output for ' . $testName);
        } else {
            $this->assertEquals($expectedHash, $actualHash, $testName);
        }
    }

    private function genoutput($reference, $settings, $expectedHashes)
    {
        $testName = '';
        foreach ($settings as $key => $value) {
            $reference[$key] = $value;
            $testName .= "${key}=${value}/";
        }

        $hash = false;
        if (isset($expectedHashes[$testName])) {
            $hash = $expectedHashes[$testName];
        }

        return array($testName, $reference, $hash);
    }

    public function chartSettingsProvider()
    {
        self::$helper = new \TestHarness\XdmodTestHelper();
        self::$helper->authenticate('cd');

        $expectedHashes = json_decode(file_get_contents(self::$hashFilePath), true);

        // Provide all the different combinations of chart settings except Guide Lines (which do not
        // work at all) and Hide Tooltip (which is an interactive-only setting)..
        //
        // Also we do not test and changes from the default for the following settings:
        //    legend location / off
        //    font size
        //    Chart Title override

        $reference = array(
           'public_user' => 'false',
           'realm' => 'Jobs',
           'group_by' => 'pi',
           'statistic' => 'total_cpu_hours',
           'start_date' => '2016-12-22',
           'end_date' => '2017-01-01',
           'timeframe_label' => 'User Defined',
           'scale' => 1,
           'aggregation_unit' => 'Day',
           'dataset_type' => 'timeseries',
           'thumbnail' => 'n',
           'query_group' => 'tg_usage',
           'display_type' => 'line',
           'combine_type' => 'side',
           'limit' => '10',
           'offset' => '0',
           'log_scale' => 'n',
           'show_guide_lines' => 'y',
           'show_trend_line' => 'n',
           'show_error_bars' => 'n',
           'show_aggregate_labels' => 'n',
           'show_error_labels' => 'n',
           'hide_tooltip' => 'false',
           'show_title' => 'y',
           'width' => '916',
           'height' => '484',
           'legend_type' => 'bottom_center',
           'font_size' => '3',
           'none' => '-9999',
           'format' => 'png',
           'inline' => 'n',
           'operation' => 'get_data',
           'controller_module' => 'user_interface'
        );

        $statistics = array('job_count', 'total_cpu_hours', 'utilization');
        $agg_err_stats = array('avg_waitduration_hours');
        $errstatistics = array('avg_cpu_hours', 'avg_node_hours', 'avg_waitduration_hours');

        $group_bys = array('none', 'person', 'resource', 'jobsize');


        $timeseries = array(
            'dataset_type' => array('timeseries'),
            'statistic' => $statistics,
            'group_by' => $group_bys,
            'log_scale' => array('y', 'n'),
            'format' => array('png', 'svg'),
            'show_aggregate_labels' => array('y', 'n'),
            'show_trend_line' => array('y', 'n'),
            'display_type' => array('line', 'area', 'bar')
        );

        $aggregate = array(
            'dataset_type' => array('aggregate'),
            'statistic' => $statistics,
            'group_by' => $group_bys,
            'format' => array('png', 'svg'),
            'log_scale' => array('y', 'n'),
            'show_aggregate_labels' => array('y', 'n'),
            'display_type' => array('line', 'h_bar', 'bar', 'pie')
        );

        $output = array();

        foreach(\TestHarness\Utilities::getCombinations($timeseries) as $settings) {
            $output[] = $this->genoutput($reference, $settings, $expectedHashes);
        }

        foreach(\TestHarness\Utilities::getCombinations($aggregate) as $settings) {
            $output[] = $this->genoutput($reference, $settings, $expectedHashes);
        }

        $timeseries['statistic'] = $errstatistics;
        $timeseries['show_error_bars'] = array('y', 'n');
        $timeseries['show_error_labels'] = array('y', 'n');

        $aggregate['statistic'] = $agg_err_stats;
        $aggregate['show_error_bars'] = array('y', 'n');
        $aggregate['show_error_labels'] = array('y', 'n');

        foreach(\TestHarness\Utilities::getCombinations($timeseries) as $settings) {
            $output[] = $this->genoutput($reference, $settings, $expectedHashes);
        }

        foreach(\TestHarness\Utilities::getCombinations($aggregate) as $settings) {
            $output[] = $this->genoutput($reference, $settings, $expectedHashes);
        }
        if (getenv('REG_TEST_ALL') === '1') {
            return $output;
        } else {
            return array_intersect_key($output, array_flip(array_rand($output, 35)));
        }
    }
}
