<?php

namespace App\Providers\Filament;

use App\Filament\Pages\DashboardPage;
use App\Filament\Teacher\Pages\CommunicationPage;
use App\Filament\Teacher\Pages\CourseAuthoringPage;
use App\Filament\Teacher\Pages\CourseContentBuilderPage;
use App\Filament\Teacher\Pages\ForumPage;
use App\Filament\Teacher\Pages\MyCoursesPage;
use App\Filament\Teacher\Widgets\TeacherStatsOverview;
use Filament\Enums\ThemeMode;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class TeacherPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('teacher')
            ->path('professor')
            ->brandName('EPI EAD')
            ->brandLogo(fn () => view('filament.brand'))
            ->brandLogoHeight('2.5rem')
            ->login(\App\Filament\Pages\Auth\Login::class)
            ->font('Inter')
            ->topbar(false)
            ->darkMode(false)
            ->defaultThemeMode(ThemeMode::Light)
            ->maxContentWidth(Width::Full)
            ->sidebarWidth('18rem')
            ->sidebarCollapsibleOnDesktop()
            ->collapsibleNavigationGroups(false)
            ->breadcrumbs(false)
            ->spa()
            ->viteTheme('resources/css/filament/gestao/theme.css')
            ->colors([
                'primary' => Color::Blue,
                'gray' => Color::Slate,
                'success' => Color::Emerald,
                'warning' => Color::Amber,
            ])
            ->pages([
                DashboardPage::class,
                MyCoursesPage::class,
                CourseAuthoringPage::class,
                CourseContentBuilderPage::class,
                CommunicationPage::class,
                ForumPage::class,
            ])
            ->widgets([
                TeacherStatsOverview::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
