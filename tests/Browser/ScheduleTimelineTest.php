<?php

namespace Tests\Browser;

use App\Models\FuneralCase;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ScheduleTimelineTest extends DuskTestCase
{
    public function test_timeline_modal_tabs_and_mobile_layout(): void
    {
        $staff = User::where('email', 'staff@funeral.test')->firstOrFail();
        $base = FuneralCase::where('branch_id', $staff->branch_id)->firstOrFail();
        $created = collect();
        $deceased = collect();
        foreach (['TIMELINE-QA-1', 'TIMELINE-QA-2', 'TIMELINE-QA-DRAFT'] as $code) {
            $case = $base->replicate();
            $person = $base->deceased->replicate();
            $person->save();
            $deceased->push($person);
            $case->deceased_id = $person->id;
            $case->fill(['case_code' => $code, 'case_number' => null, 'case_status' => str_contains($code, 'DRAFT') ? 'DRAFT' : 'ACTIVE', 'entry_source' => 'MAIN', 'funeral_service_at' => now()->toDateString(), 'funeral_service_time' => '09:30', 'interment_at' => now()->toDateString(), 'interment_time' => '10:30', 'wake_start_date' => now()->subDay()->toDateString(), 'wake_start_time' => '08:00', 'wake_end_date' => now()->addDay()->toDateString(), 'wake_end_time' => '08:00', 'payment_status' => 'PAID', 'balance_amount' => 0]);
            $case->save();
            $created->push($case);
        }
        try {
            $this->browse(function (Browser $browser) use ($staff) {
                $browser->loginAs($staff)->visit('/staff')->waitFor('[data-schedule-dialog]');
                $browser->click('[data-schedule-dialog]')->waitFor('dialog[open]');
                $this->assertTrue($browser->script('const d=document.querySelector("dialog[open]");const a=document.querySelector("#appSidebar a");a.focus();return d.matches(":modal") && document.activeElement!==a')[0]);
                $browser->assertSeeIn('dialog[open]', '9:30 AM')->assertSeeIn('dialog[open]', 'Same time')->assertSeeIn('dialog[open]', 'TIMELINE-QA-1')->assertDontSeeIn('dialog[open]', 'TIMELINE-QA-DRAFT')->screenshot('schedule-timeline-modal-light');
                $browser->script('document.documentElement.dataset.theme="dark"');
                $browser->screenshot('schedule-timeline-modal-dark')->click('dialog[open] [data-close-schedule]');
                $this->assertFalse($browser->script('return !!document.querySelector("dialog[open]")')[0]);
                $browser->visit(route('staff.reminders.index', [], false))->waitFor('.calendar-agenda')->assertSee("Today's Events")->assertSee('TIMELINE-QA-1')->assertSee('10:30 AM')->assertDontSee('TIMELINE-QA-DRAFT')->assertMissing('.ongoing-wake-list')->assertMissing('.calendar-week')->screenshot('schedule-today-events');
                $this->assertSame(2, count($browser->elements('.rp-tab')));
                $this->assertSame(3, count($browser->elements('.schedule-section-tab')));
                $browser->script('window.reminderNavigationMarker="preserved"');
                $this->assertTrue($browser->script('return window.reminderPartialNavigationReady === true')[0], 'partial script not initialized');
                $this->assertTrue($browser->script('const u=new URL(document.querySelector(".schedule-section-tab").href);return u.origin===location.origin && u.pathname===location.pathname')[0], json_encode($browser->script('return [location.href,document.querySelector(".schedule-section-tab").href]')));
                $browser->click('.calendar-agenda .calendar-event')->waitFor('.calendar-details[open]')->assertSeeIn('.calendar-details[open]', 'Case Schedule')->assertSeeIn('.calendar-details[open]', 'Wake Begins')->assertSeeIn('.calendar-details[open]', 'Wake Ends')->assertSeeIn('.calendar-details[open]', 'Funeral Ceremony')->assertSeeIn('.calendar-details[open]', 'Interment');
                $this->assertTrue($browser->script('const d=document.querySelector(".calendar-details[open]"); const a=document.querySelector("#appSidebar a"); a.focus(); return d.matches(":modal") && document.activeElement!==a')[0]);
                $browser->click('.calendar-details[open] .schedule-done');
                $browser->click('.schedule-section-tab[href*="schedule_section=ongoing"]')->waitFor('.ongoing-wake-card')->assertSee('Day 2 of 3')->assertMissing('.calendar-event')->screenshot('schedule-ongoing-wakes');
                $this->assertSame('preserved', $browser->script('return window.reminderNavigationMarker')[0], 'ongoing navigation reloaded');
                $browser->click('.ongoing-wake-card [data-calendar-event]')->waitFor('.calendar-details[open]')->assertSeeIn('.calendar-details[open]', 'Wake Begins')->click('.calendar-details[open] .schedule-done');
                $browser->click('.schedule-section-tab[href*="schedule_section=upcoming"]')->waitFor('.calendar-week')->assertSee('Wake Ends')->assertMissing('.calendar-wake-area')->screenshot('schedule-upcoming-events');
                $this->assertTrue($browser->script('return ![...document.querySelectorAll(".calendar-event.ceremony")].some(event=>event.textContent.includes("TIMELINE-QA-1"))')[0]);
                $browser->script('document.documentElement.dataset.theme="dark"');
                $browser->pause(400)->screenshot('schedule-calendar-dark');
                $this->assertSame('rgb(248, 250, 252)', $browser->script('return getComputedStyle(document.querySelector(".calendar-day strong")).color')[0]);
                $browser->script('document.documentElement.dataset.theme="light"');
                $browser->driver->executeCustomCommand('/session/:sessionId/goog/cdp/execute', 'POST', ['cmd'=>'Emulation.setDeviceMetricsOverride', 'params'=>['width'=>390,'height'=>844,'deviceScaleFactor'=>1,'mobile'=>true]]);
                $browser->pause(400)->assertVisible('.upcoming-case-list')->screenshot('schedule-calendar-mobile');
                $this->assertTrue($browser->script('return document.documentElement.scrollWidth<=window.innerWidth')[0]);
                $browser->driver->executeCustomCommand('/session/:sessionId/goog/cdp/execute', 'POST', ['cmd'=>'Emulation.clearDeviceMetricsOverride', 'params'=>(object)[]]);
                $browser->click('[data-upcoming-mode="cases"]')->waitFor('.upcoming-case-list')->assertVisible('.upcoming-case-list')->screenshot('schedule-by-case');
                $browser->type('#schedule-search', 'TIMELINE-QA-1')->waitForText('1 upcoming case');
                $browser->assertSee('TIMELINE-QA-1')->assertDontSee('TIMELINE-QA-2')->click('.upcoming-case-card [data-calendar-event]')->waitFor('.calendar-details[open]')->assertSeeIn('.calendar-details[open]', 'Funeral Ceremony')->screenshot('schedule-complete-case-modal')->click('.calendar-details[open] .schedule-done');
                $this->assertSame('preserved', $browser->script('return window.reminderNavigationMarker')[0]);
                $browser->click('.schedule-search-x')->waitForText('TIMELINE-QA-2')->click('[data-upcoming-mode="calendar"]')->waitFor('.calendar-week');
                $nextWeek = now()->addDay()->addWeek()->startOfWeek();
                $browser->click('a[aria-label="Next week"]')->waitForText($nextWeek->format('M d'));
                $this->assertTrue($browser->script('return ![...document.querySelectorAll(".calendar-week .calendar-event")].some(event=>event.textContent.includes("TIMELINE-QA-1"))')[0]);
                $browser->visit(route('staff.reminders.index', ['tab'=>'warnings'], false))->assertDontSee('TIMELINE-QA-1');
            });
        } finally {
            $created->each->forceDelete();
            $deceased->each->delete();
        }
    }
}
