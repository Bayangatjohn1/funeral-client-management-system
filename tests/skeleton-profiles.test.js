import {test} from 'node:test';
import assert from 'node:assert/strict';
import {skeletonProfile} from '../resources/js/skeleton-profiles.js';

test('schedules reflect the existing day, upcoming week and by-case views', () => {
    assert.equal(skeletonProfile('/staff/reminders').view,'day');
    assert.equal(skeletonProfile('/admin/reminders?schedule_section=upcoming').view,'week');
    assert.equal(skeletonProfile('/staff/reminders?schedule_section=upcoming&calendar_view=day').view,'day');
    assert.equal(skeletonProfile('/staff/reminders?schedule_section=upcoming&upcoming_view=cases').kind,'list');
    assert.equal(skeletonProfile('/staff/reminders?schedule_section=upcoming',{narrow:true}).kind,'list');
    assert.equal(skeletonProfile('/staff/reminders?tab=warnings').kind,'list');
});

test('destination determines the page template independently of the outgoing page', () => {
    assert.equal(skeletonProfile('/admin/service-management').kind,'catalog');
    assert.equal(skeletonProfile('/staff').kind,'dashboard');
    assert.equal(skeletonProfile('/owner/branch-analytics').kind,'analytics');
    assert.equal(skeletonProfile('/intake/main').kind,'form');
    assert.equal(skeletonProfile('/payments/history').kind,'list');
    assert.equal(skeletonProfile('/admin/payments',{role:'system'}).metrics,4);
    assert.equal(skeletonProfile('/admin/branches').metrics,2);
    assert.equal(skeletonProfile('/admin/users',{role:'system'}).metrics,4);
    assert.equal(skeletonProfile('/admin/users',{role:'branch'}).metrics,3);
    assert.equal(skeletonProfile('/admin/audit-logs').columns.length,6);
    assert.equal(skeletonProfile('/admin/record-retention').metrics,4);
    assert.equal(skeletonProfile('/backup-recovery').toolbar,false);
});
test('destination profiles preserve the real page sequence and role density', () => {
    const service=skeletonProfile('/admin/service-management');
    assert.equal(service.tabs,true);
    assert.equal(service.sectionIntro,true);

    const staffPayments=skeletonProfile('/staff/payments/history',{role:'staff'});
    assert.equal(staffPayments.metrics,0);
    assert.equal(staffPayments.tabs,false);
    assert.equal(staffPayments.listColumns,5);

    const branchPayments=skeletonProfile('/admin/payments',{role:'branch'});
    assert.equal(branchPayments.tabs,true);
    assert.equal(branchPayments.metrics,4);
    assert.equal(branchPayments.metricsFirst,true);

    const corrections=skeletonProfile('/admin/payments?tab=corrections',{role:'branch'});
    assert.equal(corrections.tabs,true);
    assert.equal(corrections.toolbar,false);
    assert.equal(corrections.listColumns,3);

    assert.equal(skeletonProfile('/admin/branches',{role:'system'}).metricsFirst,true);
    assert.equal(skeletonProfile('/admin/record-retention',{role:'system'}).notice,true);
});
test('case table preserves role-specific column structure', () => {
    assert.equal(skeletonProfile('/admin/cases').columns.length,7);
    assert.equal(skeletonProfile('/owner/case-history',{role:'owner'}).columns.length,8);
    assert.equal(skeletonProfile('/admin/users',{directoryView:'table'}).columns[0],'avatar');
    assert.equal(skeletonProfile('/admin/users').kind,'directory');
});
test('catalog respects an existing table-view preference', () => {
    assert.equal(skeletonProfile('/admin/service-management?tab=caskets',{catalogView:'table'}).kind,'table');
    assert.equal(skeletonProfile('/admin/service-management?tab=packages',{catalogView:'cards'}).kind,'catalog');
});
test('promotions use their seven-column table and promotion-card destination shape', () => {
    const table=skeletonProfile('/admin/service-management?tab=promotions',{catalogView:'table'});
    assert.equal(table.columns.length,7);
    assert.equal(table.columns.at(-2),'badge');
    assert.equal(table.columns.at(-1),'actions');
    assert.equal(skeletonProfile('/admin/service-management?tab=promotions').catalog,'promotions');
});
test('system dashboard uses its existing access/catalog panels rather than staff summary cards', () => {
    const profile=skeletonProfile('/admin',{role:'system'});
    assert.equal(profile.metrics,0); assert.equal(profile.sections,'access');
});
