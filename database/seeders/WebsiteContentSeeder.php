<?php

namespace Database\Seeders;

use App\Models\PageHeader;
use App\Models\WebsiteSetting;
use App\Support\SiteContent;
use Illuminate\Database\Seeder;

class WebsiteContentSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = SiteContent::defaults();
        $whyChooseCards = SiteContent::whyChooseCards();

        $defaults['about']['core_value_cards'] = $whyChooseCards;

        $settings = WebsiteSetting::first() ?? WebsiteSetting::create([
            'company_name' => 'New Life Leadership Academy',
        ]);

        $existingSections = is_array($settings->page_sections)
            ? $settings->page_sections
            : (is_string($settings->page_sections) ? (json_decode($settings->page_sections, true) ?: []) : []);

        // Preserve custom edits, but refresh key NLA copy and Why Choose Us features.
        $merged = SiteContent::merge($defaults, $existingSections);
        $merged['about']['overview_fallback'] = $defaults['about']['overview_fallback'];
        $merged['about']['core_value_cards'] = $whyChooseCards;
        $merged['about']['core_values_title'] = $defaults['about']['core_values_title'];
        $merged['about']['history_body'] = $defaults['about']['history_body'];
        $merged['home']['why_text'] = $defaults['home']['why_text'];
        $merged['home']['why_title'] = $defaults['home']['why_title'];
        $merged['home']['overview_fallback'] = $defaults['home']['overview_fallback'];
        $merged['home']['hero_primary_text'] = $defaults['home']['hero_primary_text'];
        $merged['home']['hero_primary_url'] = $defaults['home']['hero_primary_url'];
        $merged['home']['hero_secondary_text'] = $defaults['home']['hero_secondary_text'];
        $merged['home']['hero_secondary_url'] = $defaults['home']['hero_secondary_url'];
        $merged['home']['why_choose_label'] = $defaults['home']['why_choose_label'];
        $merged['home']['why_choose_title'] = $defaults['home']['why_choose_title'];
        $merged['home']['curriculum_intro'] = $defaults['home']['curriculum_intro'];
        $merged['home']['curriculum_pillars'] = $defaults['home']['curriculum_pillars'];
        $merged['home']['curriculum_items'] = $defaults['home']['curriculum_items'];
        $merged['home']['facilities_icons'] = $defaults['home']['facilities_icons'];
        $merged['academics'] = $defaults['academics'];
        $merged['global']['topbar_announcement'] = $defaults['global']['topbar_announcement'];

        $settings->page_sections = $merged;
        $settings->about_value_cards = $whyChooseCards;
        $settings->about_values_subheading = 'Why Choose New Life Leadership Academy';
        $settings->about_description = $defaults['about']['overview_fallback'];
        $settings->about_heading = $settings->about_heading ?: 'Christ-Centred Education for Character & Leadership';

        $settings->company_name = $settings->company_name ?: 'New Life Leadership Academy';

        if (empty($settings->logo_path)) {
            $settings->logo_path = 'images/nla-logo.png';
        }

        if (empty($settings->email)) {
            $settings->email = 'info@nla.ac.rw';
        }

        if (empty($settings->phone_reception)) {
            $settings->phone_reception = '+250 786 900 580';
        }

        $settings->save();

        $headers = [
            'about' => ['title' => 'About', 'caption' => 'An outreach of Africa New Life Ministries'],
            'about_mission' => ['title' => 'Mission & vision', 'caption' => 'What drives us forward'],
            'about_values' => ['title' => 'Why Choose Us', 'caption' => 'What sets New Life Leadership Academy apart'],
            'about_staff' => ['title' => 'Our staff', 'caption' => 'Educators who guide every student'],
            'about_history' => ['title' => 'Our history', 'caption' => 'An outreach of Africa New Life Ministries'],
            'about_schools' => ['title' => 'Our schools', 'caption' => 'The New Life family of campuses'],
            'about_inquire' => ['title' => 'Get in touch', 'caption' => 'We are glad to hear from you'],
            'departments' => ['title' => 'Academics', 'caption' => 'Accelerated Christian Education at NLA'],
            'academics_about_ace' => ['title' => 'About ACE', 'caption' => 'Individualised mastery-based Christian curriculum'],
            'academics_diagnostic' => ['title' => 'ACE Diagnostic Test', 'caption' => 'Finding the right starting level for every student'],
            'academics_tuition' => ['title' => 'Tuition & Fees', 'caption' => 'Fee structure, boarding, and enrichment'],
            'admissions' => ['title' => 'Apply', 'caption' => 'Begin your child\'s journey with us'],
            'register' => ['title' => 'Register', 'caption' => 'Start online registration'],
            'visit_school' => ['title' => 'Visit our campus', 'caption' => 'Schedule a campus visit'],
            'facilities' => ['title' => 'Facilities', 'caption' => 'Learning spaces, enrichment, and boarding'],
            'leadership' => ['title' => 'Staff', 'caption' => 'Meet our team'],
            'school_activities' => ['title' => 'School Activities', 'caption' => 'Life beyond the classroom'],
            'careers' => ['title' => 'Careers', 'caption' => 'Join our mission'],
            'gallery' => ['title' => 'Gallery', 'caption' => 'Moments from campus life'],
            'contact' => ['title' => 'Contacts', 'caption' => 'Reach the school office'],
            'feedback' => ['title' => 'Feedback', 'caption' => 'Help us serve families better'],
        ];

        foreach ($headers as $key => $data) {
            PageHeader::updateOrCreate(
                ['page_key' => $key],
                [
                    'title' => $data['title'],
                    'caption' => $data['caption'],
                ]
            );
        }

        $this->command?->info('NLA website content, Why Choose Us features, and page headers seeded.');
    }
}
