<?php

namespace Tests\Browser;

use App\Models\User;
use Facebook\WebDriver\Chrome\ChromeDevToolsDriver;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ResponsiveQaTest extends DuskTestCase
{
    public function test_core_pages_do_not_overflow_common_responsive_viewports(): void
    {
        $viewports = [
            'small phone' => [375, 812],
            'large phone' => [430, 932],
            'tablet' => [768, 1024],
            'desktop' => [1366, 768],
        ];

        $scenarios = [
            'staff' => [
                'email' => 'staff@funeral.test',
                'pages' => [
                    '/staff',
                    '/funeral-cases',
                    '/intake/main',
                    '/payments/history',
                    '/clients',
                ],
            ],
            'admin' => [
                'email' => 'admin@funeral.test',
                'pages' => [
                    '/admin',
                    '/admin/cases',
                    '/admin/users',
                    '/admin/service-management',
                    '/reports?report_type=master_cases',
                ],
            ],
            'owner' => [
                'email' => 'owner@funeral.test',
                'pages' => [
                    '/owner',
                    '/owner/branch-analytics',
                    '/owner/case-history',
                    '/reports?report_type=owner_branch_analytics',
                ],
            ],
        ];

        $this->browse(function (Browser $browser) use ($viewports, $scenarios) {
            foreach ($scenarios as $role => $scenario) {
                $user = User::where('email', $scenario['email'])->firstOrFail();

                foreach ($viewports as $viewportName => [$width, $height]) {
                    $this->forceViewport($browser, $width, $height, "{$role} {$viewportName}");

                    $browser->loginAs($user);

                    foreach ($scenario['pages'] as $path) {
                        $browser->visit($path)
                            ->waitUntil('document.body !== null', 10)
                            ->pause(200);

                        $this->assertViewport($browser, $width, "{$role} {$path} at {$viewportName}");
                        $this->assertResponsivePageHealth($browser, "{$role} {$path} at {$viewportName} {$width}x{$height}");
                    }
                }
            }
        });
    }

    public function test_mobile_sidebar_toggle_is_reachable_for_core_roles(): void
    {
        $roleHomepages = [
            'staff@funeral.test' => '/staff',
            'admin@funeral.test' => '/admin',
            'owner@funeral.test' => '/owner',
        ];

        $this->browse(function (Browser $browser) use ($roleHomepages) {
            foreach ($roleHomepages as $email => $path) {
                $user = User::where('email', $email)->firstOrFail();

                $this->forceViewport($browser, 375, 812, $email);

                $browser->loginAs($user)
                    ->visit($path)
                    ->waitUntil('document.body !== null', 10)
                    ->pause(200);

                $this->assertViewport($browser, 375, "{$email} mobile sidebar");

                $visibleToggleScript = <<<'JS'
                    return Array.from(document.querySelectorAll('#mobileSidebarToggle, .mobile-menu-btn, .panel-floating-menu-btn')).some((el) => {
                        const style = window.getComputedStyle(el);
                        const rect = el.getBoundingClientRect();

                        return style.display !== 'none'
                            && style.visibility !== 'hidden'
                            && rect.width > 0
                            && rect.height > 0;
                    });
                JS;

                $hasVisibleToggle = (bool) ($browser->script($visibleToggleScript)[0] ?? false);

                $this->assertTrue($hasVisibleToggle, "No visible mobile sidebar toggle for {$email} on {$path}.");

                $browser->script(<<<'JS'
                    const toggle = Array.from(document.querySelectorAll('#mobileSidebarToggle, .mobile-menu-btn, .panel-floating-menu-btn')).find((el) => {
                        const style = window.getComputedStyle(el);
                        const rect = el.getBoundingClientRect();

                        return style.display !== 'none'
                            && style.visibility !== 'hidden'
                            && rect.width > 0
                            && rect.height > 0;
                    });

                    toggle?.click();
                JS);

                $browser->pause(150);

                $sidebarOpen = $browser->script(
                    "return document.body.getAttribute('data-sidebar-open') === 'true'"
                    . " || document.getElementById('appSidebar')?.classList.contains('is-open')"
                    . " || document.body.classList.contains('sidebar-open');"
                )[0] ?? false;

                $this->assertTrue($sidebarOpen, "Mobile sidebar did not open for {$email} on {$path}.");
            }
        });
    }

    private function forceViewport(Browser $browser, int $width, int $height, string $context): void
    {
        $devTools = new ChromeDevToolsDriver($browser->driver);
        $devTools->execute('Emulation.setDeviceMetricsOverride', [
            'width' => $width,
            'height' => $height,
            'deviceScaleFactor' => $width < 768 ? 2 : 1,
            'mobile' => $width < 768,
        ]);
        $devTools->execute('Emulation.setTouchEmulationEnabled', [
            'enabled' => $width < 768,
        ]);
        $browser->pause(100);
    }

    private function assertViewport(Browser $browser, int $width, string $context): void
    {
        $actualWidth = (int) (($browser->script('return window.innerWidth;')[0] ?? 0));
        $this->assertEqualsWithDelta(
            $width,
            $actualWidth,
            2,
            "Viewport was not emulated for {$context}; expected about {$width}px, got {$actualWidth}px."
        );
    }

    private function assertResponsivePageHealth(Browser $browser, string $context): void
    {
        $source = $browser->driver->getPageSource();
        $text = trim(html_entity_decode(strip_tags($source)));

        foreach (['Server Error', 'This action is unauthorized', 'Whoops', 'Undefined variable', 'Call to undefined', 'Stack trace'] as $unexpected) {
            $this->assertStringNotContainsString($unexpected, $text, $context);
            $this->assertStringNotContainsString($unexpected, $source, $context);
        }

        $metrics = $browser->script(<<<'JS'
            const root = document.documentElement;
            const body = document.body;
            const viewportWidth = window.innerWidth;
            const scrollWidth = Math.max(root.scrollWidth, body.scrollWidth);
            const offenders = [];
            const criticalOffenders = [];
            const clippedOffenders = [];
            const criticalSelector = [
                '.case-records-top-wrapper',
                '.case-records-tabs-row',
                '.case-records-tabs',
                '.master-records-page .table-system-list',
                '.master-records-page .case-records-tabs',
                '.case-compact-search-row',
                '.case-compact-search-field',
                '.case-compact-search-control',
                '.case-compact-filter-bar',
                '.case-compact-date-filter',
                '.case-compact-sort-filter',
                '.case-compact-actions',
                '.case-compact-more',
                '.case-compact-reset',
                '.case-compact-apply',
                '.reports-module-tabs',
                '.reports-module-controls',
                '.reports-module-tab',
                '.reports-summary-grid',
                '.reports-metric',
                '.reports-card',
                '.table-system-list',
                '.table-system-wrap',
                '.table-quick-tab',
                'button',
                'input',
                'select',
            ].join(',');

            document.querySelectorAll('body *').forEach((el) => {
                const style = window.getComputedStyle(el);
                if (
                    style.display === 'none'
                    || style.visibility === 'hidden'
                    || style.position === 'absolute'
                    || el.closest('[hidden]')
                    || el.matches('script, style, template')
                ) {
                    return;
                }

                const rect = el.getBoundingClientRect();
                if (rect.width <= 0 || rect.height <= 0) return;
                if (el.closest('.sidebar') && rect.right <= 2) return;

                if (rect.left < -2 || rect.right > viewportWidth + 2) {
                    const offender = {
                        tag: el.tagName.toLowerCase(),
                        className: String(el.className || '').slice(0, 120),
                        left: Math.round(rect.left),
                        right: Math.round(rect.right),
                        width: Math.round(rect.width),
                    };

                    offenders.push(offender);

                    const isScrollableTableContent = Boolean(el.closest('.table-system-wrap, .table-wrapper'));

                    if (el.matches(criticalSelector) && !isScrollableTableContent) {
                        criticalOffenders.push(offender);
                    }
                }
            });

            const clipContainerSelector = [
                '.reports-metric',
                '.reports-card-head',
                '.reports-module-tabs',
                '.reports-module-controls',
                '.case-records-tabs',
                '.case-compact-filter-bar',
                '.master-records-page .admin-master-table tr',
                '.records-page .case-records-table tr',
            ].join(',');

            document.querySelectorAll(clipContainerSelector).forEach((container) => {
                const style = window.getComputedStyle(container);
                const containerRect = container.getBoundingClientRect();

                if (
                    style.display === 'none'
                    || style.visibility === 'hidden'
                    || containerRect.width <= 0
                    || containerRect.height <= 0
                    || container.closest('.sidebar')
                ) {
                    return;
                }

                Array.from(container.children).forEach((child) => {
                    const childStyle = window.getComputedStyle(child);
                    const childRect = child.getBoundingClientRect();

                    if (
                        childStyle.display === 'none'
                        || childStyle.visibility === 'hidden'
                        || childRect.width <= 0
                        || childRect.height <= 0
                    ) {
                        return;
                    }

                    if (childRect.left < containerRect.left - 2 || childRect.right > containerRect.right + 2) {
                        clippedOffenders.push({
                            container: String(container.className || container.tagName).slice(0, 120),
                            child: String(child.className || child.tagName).slice(0, 120),
                            containerLeft: Math.round(containerRect.left),
                            containerRight: Math.round(containerRect.right),
                            childLeft: Math.round(childRect.left),
                            childRight: Math.round(childRect.right),
                        });
                    }
                });
            });

            return {
                viewportWidth,
                scrollWidth,
                overflow: scrollWidth - viewportWidth,
                offenders: offenders.slice(0, 12),
                criticalOffenders: criticalOffenders.slice(0, 12),
                clippedOffenders: clippedOffenders.slice(0, 12),
            };
        JS)[0] ?? [];

        $overflow = (int) ($metrics['overflow'] ?? 0);
        $criticalOffenders = $metrics['criticalOffenders'] ?? [];
        $clippedOffenders = $metrics['clippedOffenders'] ?? [];

        $this->assertLessThanOrEqual(
            2,
            $overflow,
            $context . ' has page-level horizontal overflow: ' . json_encode($metrics, JSON_PRETTY_PRINT)
        );

        $this->assertCount(
            0,
            $criticalOffenders,
            $context . ' has controls or containers outside the viewport: ' . json_encode($metrics, JSON_PRETTY_PRINT)
        );

        $this->assertCount(
            0,
            $clippedOffenders,
            $context . ' has controls or cards clipped inside their container: ' . json_encode($metrics, JSON_PRETTY_PRINT)
        );
    }
}
