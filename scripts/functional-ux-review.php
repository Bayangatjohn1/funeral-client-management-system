<?php
// Isolated acceptance-review tooling; never targets an existing database or runs a scheduler.
require __DIR__.'/../vendor/autoload.php';

use Symfony\Component\Process\Process;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Laravel\Dusk\Browser;
use App\Models\{User, FuneralCase};
use PragmaRX\Google2FA\Google2FA;

$base = dirname(__DIR__);
$root = $base.'/storage/framework/functional-ux-review-'.date('Ymd-His');
foreach (['', '/framework/cache/data', '/framework/sessions', '/framework/views', '/logs', '/app/private', '/app/public', '/cache', '/screenshots'] as $dir) {
    if (!is_dir($root.$dir)) mkdir($root.$dir, 0777, true);
}
$db = $root.'/review.sqlite'; touch($db);
$env = [
    'APP_ENV'=>'local', 'APP_DEBUG'=>'false', 'APP_KEY'=>'base64:'.base64_encode(random_bytes(32)),
    'APP_URL'=>'http://127.0.0.1:8033', 'DB_CONNECTION'=>'sqlite', 'DB_DATABASE'=>$db,
    'LARAVEL_STORAGE_PATH'=>$root, 'APP_CONFIG_CACHE'=>$root.'/cache/config.php',
    'APP_SERVICES_CACHE'=>$root.'/cache/services.php', 'APP_PACKAGES_CACHE'=>$root.'/cache/packages.php',
    'APP_ROUTES_CACHE'=>$root.'/cache/routes.php', 'APP_EVENTS_CACHE'=>$root.'/cache/events.php',
    'VIEW_COMPILED_PATH'=>$root.'/framework/views', 'CACHE_STORE'=>'file', 'SESSION_DRIVER'=>'file',
    'SESSION_SECURE_COOKIE'=>'false', 'QUEUE_CONNECTION'=>'sync', 'MAIL_MAILER'=>'log',
    'BRANCH_SYNC_MODE'=>'standalone',
];
foreach (['APP_CONFIG_CACHE','APP_SERVICES_CACHE','APP_PACKAGES_CACHE','APP_ROUTES_CACHE','APP_EVENTS_CACHE'] as $key) {
    $env[$key]=substr($env[$key],strlen($base)+1);
}
foreach ($env as $k=>$v) { putenv("$k=$v"); $_ENV[$k]=$v; $_SERVER[$k]=$v; }
$app = require $base.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (config('database.connections.sqlite.database') !== $db || config('branch_sync.mode') !== 'standalone') throw new RuntimeException('Isolation guard failed');
Illuminate\Support\Facades\Artisan::call('migrate', ['--force'=>true]);
Illuminate\Support\Facades\Artisan::call('db:seed', ['--force'=>true]);
$seed = User::where('email','staff@funeral.test')->firstOrFail();
$accounts=[];
foreach (['staff','branch_admin','system_admin','owner'] as $role) {
    $accountBranch=$seed->branch_id;
    if (in_array('--edges',$argv,true) && $role==='staff') {
        $accountBranch=App\Models\Branch::create(['branch_code'=>'BR-UX02','branch_name'=>'Acceptance Second Branch','address'=>'QA Pangasinan','is_active'=>true])->id;
    }
    $secret=(new Google2FA)->generateSecretKey();
    $u=User::factory()->create(['email'=>"review-$role@funeral.test", 'password'=>'ReviewOnly123!',
        'role'=>str_contains($role,'admin')?'admin':$role,
        'admin_scope'=>$role==='system_admin'?'system':($role==='branch_admin'?'branch':null),
        'branch_id'=>in_array($role,['staff','branch_admin'])?$accountBranch:null,
        'is_active'=>true,'must_change_password'=>false]);
    $u->two_factor_secret=$secret; $u->two_factor_confirmed_at=now(); $u->save();
    $accounts[$role]=[$u,$secret];
}
$case=FuneralCase::where('branch_id',$seed->branch_id)->firstOrFail();
$server=null; $driverProcess=null; $driver=null;
$results=['started_at'=>now()->toIso8601String(),'root'=>$root,'database'=>$db,'pages'=>[], 'checks'=>[]];
try {
    foreach ([8033,9533] as $port) if (@fsockopen('127.0.0.1',$port,$e,$s,.1)) throw new RuntimeException("Review port occupied: $port");
    $driverProcess=new Process([$base.'/vendor/laravel/dusk/bin/chromedriver-win.exe','--port=9533'],$base);
    $driverProcess->disableOutput(); $driverProcess->start();
    $server=new Process([PHP_BINARY,'-S','127.0.0.1:8033',$base.'/vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php'],$base.'/public',$env);
    $server->disableOutput(); $server->start();
    for ($i=0;$i<60;$i++) { if (@fsockopen('127.0.0.1',8033,$e,$s,.1) && @fsockopen('127.0.0.1',9533,$e,$s,.1)) break; usleep(250000); }
    $options=(new ChromeOptions)->addArguments(['--headless=new','--no-sandbox','--disable-gpu','--window-size=1440,1000','--user-data-dir='.$root.'/chrome']);
    $caps=DesiredCapabilities::chrome()->setCapability(ChromeOptions::CAPABILITY,$options);
    $driver=RemoteWebDriver::create('http://127.0.0.1:9533',$caps,10000,30000);
    Browser::$baseUrl=$env['APP_URL']; Browser::$storeScreenshotsAt=$root.'/screenshots';
    $browser=new Browser($driver);
    $pages=[
        'staff'=>['/staff','/intake/main','/funeral-cases','/funeral-cases/'.$case->id,'/payments/history','/reminders'],
        'branch_admin'=>['/admin','/admin/cases','/admin/payment-corrections','/admin/payments','/reports','/admin/service-management'],
        'system_admin'=>['/admin','/admin/users','/admin/users/create','/admin/branches','/admin/branches/create','/admin/service-management','/admin/backup-recovery','/admin/record-retention'],
        'owner'=>['/owner','/owner/branch-analytics','/owner/collections','/owner/case-history','/owner/cases/'.$case->id,'/reports'],
    ];
    foreach ($accounts as $role=>[$user,$secret]) {
        if (in_array('--account-only',$argv,true) && $role!=='system_admin') continue;
        $browser->visit('/login')->type('email',$user->email)->type('password','ReviewOnly123!')->press('Sign in');
        if ($user->requiresMfa()) $browser->waitForLocation('/security/challenge',10)->type('code',(new Google2FA)->getCurrentOtp($secret))->press('Verify and continue');
        $browser->waitForLocation($role==='owner'?'/owner':($role==='staff'?'/staff':'/admin'),15);
        foreach ((in_array('--flows', $argv, true) || in_array('--edges', $argv, true) || in_array('--account-only',$argv,true)) ? [] : $pages[$role] as $path) {
            $browser->visit($path)->pause(350);
            $page=['role'=>$role,'requested'=>$path,'url'=>$driver->getCurrentURL(),'states'=>[]];
            foreach (['light','dark'] as $theme) foreach ([1440,390] as $width) {
                $driver->executeCustomCommand('/session/:sessionId/goog/cdp/execute','POST',['cmd'=>'Emulation.setDeviceMetricsOverride','params'=>['width'=>$width,'height'=>1000,'deviceScaleFactor'=>1,'mobile'=>false]]);
                $browser->script('document.documentElement.dataset.theme='.json_encode($theme)); $browser->pause(100);
                $name=$role.'-'.trim(str_replace(['/','?','='], '-', $path),'-')."-$theme-$width";
                $browser->screenshot($name);
                $page['states'][]=$browser->script('return {theme:document.documentElement.dataset.theme,width:innerWidth,overflow:document.documentElement.scrollWidth>innerWidth+1,headings:[...document.querySelectorAll("h1,h2")].map(e=>e.innerText).filter(Boolean),text:document.body.innerText.slice(-16000),unnamedButtons:[...document.querySelectorAll("button")].filter(e=>e.getBoundingClientRect().width && !e.innerText.trim() && !e.getAttribute("aria-label") && !e.getAttribute("title")).length}')[0];
            }
            $results['pages'][]=$page;
            file_put_contents($root.'/results.json',json_encode($results,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
            echo "$role $path reviewed\n";
        }
        if (in_array('--flows', $argv, true)) require __DIR__.'/functional-ux-review-flows.php';
        if (in_array('--edges', $argv, true) || in_array('--account-only',$argv,true)) require __DIR__.'/functional-ux-review-edges.php';
        $browser->logout();
    }
    $results['completed_at']=now()->toIso8601String();
} catch (Throwable $e) {
    $results['error']=get_class($e).': '.$e->getMessage();
    echo $results['error']."\n";
} finally {
    file_put_contents($root.'/results.json',json_encode($results,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
    if ($driver) $driver->quit();
    foreach ([$server,$driverProcess] as $process) {
        if ($process && $process->isRunning()) { $stop=new Process(['taskkill','/F','/T','/PID',(string)$process->getPid()]); $stop->run(); }
    }
}
echo "Evidence: $root/results.json\n";
exit(isset($results['error'])?1:0);
