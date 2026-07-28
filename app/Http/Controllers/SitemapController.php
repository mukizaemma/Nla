<?php

namespace App\Http\Controllers;

use App\Models\ClinicalDepartment;
use App\Models\LeadershipTeamMember;
use App\Models\SchoolActivity;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class SitemapController extends Controller
{
    public function robots(): Response
    {
        $lines = [
            'User-agent: *',
            'Allow: /',
            'Disallow: /admin',
            'Disallow: /admin/',
            'Disallow: /login',
            'Disallow: /register',
            'Disallow: /password/',
            'Disallow: /livewire/',
            '',
            'Sitemap: '.url('/sitemap.xml'),
            '',
        ];

        return response(implode("\n", $lines), 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }

    public function sitemap(): Response
    {
        $urls = [];

        $static = [
            ['route' => 'home', 'priority' => '1.0', 'changefreq' => 'weekly'],
            ['route' => 'about', 'priority' => '0.9', 'changefreq' => 'monthly'],
            ['route' => 'admissions', 'priority' => '0.9', 'changefreq' => 'monthly'],
            ['route' => 'appointment', 'priority' => '0.9', 'changefreq' => 'monthly'],
            ['route' => 'departments.index', 'priority' => '0.8', 'changefreq' => 'weekly'],
            ['route' => 'academics.about-ace', 'priority' => '0.8', 'changefreq' => 'monthly'],
            ['route' => 'academics.diagnostic', 'priority' => '0.7', 'changefreq' => 'monthly'],
            ['route' => 'academics.tuition', 'priority' => '0.7', 'changefreq' => 'monthly'],
            ['route' => 'facilities', 'priority' => '0.8', 'changefreq' => 'monthly'],
            ['route' => 'school-activities', 'priority' => '0.8', 'changefreq' => 'weekly'],
            ['route' => 'leadership.index', 'priority' => '0.7', 'changefreq' => 'monthly'],
            ['route' => 'gallery.index', 'priority' => '0.6', 'changefreq' => 'weekly'],
            ['route' => 'careers', 'priority' => '0.6', 'changefreq' => 'monthly'],
            ['route' => 'contact', 'priority' => '0.8', 'changefreq' => 'monthly'],
            ['route' => 'feedback', 'priority' => '0.5', 'changefreq' => 'yearly'],
        ];

        foreach ($static as $page) {
            $urls[] = [
                'loc' => route($page['route']),
                'lastmod' => now()->toAtomString(),
                'changefreq' => $page['changefreq'],
                'priority' => $page['priority'],
            ];
        }

        ClinicalDepartment::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'slug', 'updated_at'])
            ->each(function (ClinicalDepartment $department) use (&$urls) {
                $urls[] = [
                    'loc' => route('departments.show', ['department' => $department->slug ?: $department->id]),
                    'lastmod' => optional($department->updated_at)->toAtomString() ?? now()->toAtomString(),
                    'changefreq' => 'monthly',
                    'priority' => '0.7',
                ];
            });

        SchoolActivity::query()
            ->where('is_active', true)
            ->orderByDesc('published_at')
            ->get(['id', 'slug', 'updated_at', 'published_at'])
            ->each(function (SchoolActivity $activity) use (&$urls) {
                $lastmod = $activity->updated_at ?? $activity->published_at;
                $urls[] = [
                    'loc' => route('school-activities.show', ['activity' => $activity->slug ?: $activity->id]),
                    'lastmod' => optional($lastmod)->toAtomString() ?? now()->toAtomString(),
                    'changefreq' => 'monthly',
                    'priority' => '0.6',
                ];
            });

        LeadershipTeamMember::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'full_name', 'updated_at'])
            ->each(function (LeadershipTeamMember $member) use (&$urls) {
                $urls[] = [
                    'loc' => route('leadership.show', [
                        'member' => $member->id,
                        'slug' => Str::slug($member->full_name),
                    ]),
                    'lastmod' => optional($member->updated_at)->toAtomString() ?? now()->toAtomString(),
                    'changefreq' => 'monthly',
                    'priority' => '0.5',
                ];
            });

        $xml = view('seo.sitemap', ['urls' => $urls])->render();

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }
}
