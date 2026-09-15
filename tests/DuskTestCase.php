<?php

namespace Tests;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Illuminate\Support\Collection;
use Laravel\Dusk\Browser;
use Laravel\Dusk\TestCase as BaseTestCase;
use PHPUnit\Framework\Attributes\AfterClass;
use PHPUnit\Framework\Attributes\BeforeClass;

abstract class DuskTestCase extends BaseTestCase
{
    /**
     * Prepare for Dusk test execution.
     */
    #[BeforeClass]
    public static function prepare(): void
    {
        $driverUrl = $_ENV['DUSK_DRIVER_URL'] ?? env('DUSK_DRIVER_URL') ?? 'http://localhost:9515';
        $driverHost = parse_url($driverUrl, PHP_URL_HOST) ?: '127.0.0.1';
        $driverPort = (int) (parse_url($driverUrl, PHP_URL_PORT) ?: 9515);

        if (! static::runningInSail() && ! @fsockopen($driverHost, $driverPort, $errno, $errstr, 0.1)) {
            static::startChromeDriver(['--port=9515']);
        }
    }

    /**
     * Create the RemoteWebDriver instance.
     */
    protected function driver(): RemoteWebDriver
    {
        $userDataDir = storage_path('framework/dusk-chrome-profile-'.getmypid());

        if (! is_dir($userDataDir)) {
            mkdir($userDataDir, 0777, true);
        }

        $options = (new ChromeOptions)->addArguments(collect([
            $this->shouldStartMaximized() ? '--start-maximized' : '--window-size=1920,1080',
            '--user-data-dir='.$userDataDir,
            '--no-first-run',
            '--no-default-browser-check',
            '--disable-crash-reporter',
            '--disable-search-engine-choice-screen',
            '--disable-smooth-scrolling',
            '--disable-dev-shm-usage',
            '--no-sandbox',
        ])->unless($this->hasHeadlessDisabled(), function (Collection $items) {
            return $items->merge([
                '--disable-gpu',
                '--headless=new',
            ]);
        })->all());

        $capabilities = DesiredCapabilities::chrome()
            ->setCapability(ChromeOptions::CAPABILITY, $options)
            ->setCapability('pageLoadStrategy', 'eager');

        return RemoteWebDriver::create(
            $_ENV['DUSK_DRIVER_URL'] ?? env('DUSK_DRIVER_URL') ?? 'http://localhost:9515',
            $capabilities,
            5000,
            30000
        );
    }

    public static function closeAll()
    {
        collect(static::$browsers)->each(function (Browser $browser) {
            try {
                $browser->quit();
            } catch (\Throwable $e) {
                //
            }
        });

        static::$browsers = collect();
    }

    protected function storeConsoleLogsFor($browsers)
    {
        //
    }

    #[AfterClass]
    public static function tearDownDuskClass()
    {
        static::closeAll();
        static::$afterClassCallbacks = [];
    }
}
