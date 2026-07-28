<?php

namespace App\Support;

use App\Models\WebsiteSetting;

class SiteContent
{
    public static function defaults(): array
    {
        return [
            'global' => [
                'meta_description' => 'New Life Leadership Academy — an outreach of Africa New Life Ministries offering Christ-centred ACE education that develops Christian character and leadership in every child.',
                'topbar_tagline' => 'Pursuing Education to the Glory of God',
                'topbar_announcement' => 'Christ-centred ACE education — Now enrolling · Academic year 2026-2027',
                'footer_menu_heading' => 'Explore',
                'developer_credit' => 'Developed by <a href="https://iremetech.com" target="_blank" rel="noopener noreferrer">Ireme Technologies</a>',
            ],
            'home' => [
                'hero_primary_text' => 'Register',
                'hero_primary_url' => '/appointment',
                'hero_secondary_text' => 'Take Diagnostic Test',
                'hero_secondary_url' => 'https://www.acediagnostictest.com/diagnostictest/?route=common/pages&page_identifier=diagnostictest',
                'enquiry_title' => 'Request Admission',
                'enquiry_subtitle' => 'Fill in your details and our admissions team will call you back.',
                'why_title' => 'Why New Life Leadership Academy',
                'why_text' => 'An outreach of Africa New Life Ministries, uniquely set apart to offer Christ-centred education that develops Christian character and Christian leadership — helping every child reach their full potential.',
                'why_link_text' => 'Learn More',
                'events_title' => 'Latest Events Gallery',
                'results_title' => 'Academic Excellence',
                'results_year' => '2025 Results',
                'results_stats' => [
                    ['value' => 'ACE', 'label' => 'International Christian Curriculum'],
                    ['value' => '100%', 'label' => 'Mastery before advancement'],
                    ['value' => 'AEE', 'label' => 'Registered with AEE South Africa'],
                ],
                'results_link_text' => 'View All Academic Results',
                'partners_label' => 'Academic Partnerships',
                'focus_title' => 'Individualised Mastery Learning',
                'focus_text' => 'Through Accelerated Christian Education, each student works at their own level of achievement — which may vary from subject to subject — until every concept is truly mastered.',
                'focus_text_2' => 'Biblical principles and character training are integrated into every subject, nurturing Christian character alongside strong academics and leadership.',
                'focus_primary_btn' => 'About ACE',
                'focus_secondary_btn' => 'Student Enquiry Form',
                'facilities_title' => 'Best-In-Class Facilities',
                'curriculum_title' => 'Our Curriculum',
                'curriculum_items' => [
                    ['title' => 'Accelerated Christian Education', 'text' => 'An international Christian curriculum with individualised PACEs so each student masters concepts at their own pace.'],
                    ['title' => 'Biblical Integration', 'text' => 'Biblical principles and character training woven into the study of every subject.'],
                    ['title' => 'Leadership Development', 'text' => 'Co-curricular enrichment, boarding life, and intentional leadership formation for every student.'],
                ],
                'facilities_icons' => [
                    ['label' => 'Art'],
                    ['label' => 'Computer'],
                    ['label' => 'Library'],
                    ['label' => 'Drama'],
                    ['label' => 'Music'],
                    ['label' => 'P.E.'],
                    ['label' => 'French'],
                    ['label' => 'Swimming'],
                    ['label' => 'Boarding'],
                ],
                'stats' => [
                    ['value' => 'ACE', 'label' => 'Christian Curriculum'],
                    ['value' => 'AEE', 'label' => 'South Africa Registration'],
                    ['value' => 'REB', 'label' => 'Rwanda Education Board'],
                    ['value' => 'NLA', 'label' => 'New Life Leadership Academy'],
                ],
                'testimonials_title' => 'Hear what parents have to say about us',
                'testimonials' => [
                    ['quote' => 'The ACE curriculum combined with strong Christian values has transformed our daughter. She is confident, disciplined, and excelling in her studies.', 'name' => 'Parent of NLA Student'],
                    ['quote' => 'We chose NLA for mastery learning and character training. Our son has grown tremendously in both academics and leadership.', 'name' => 'Parent of NLA Student'],
                ],
                'events_section_title' => 'Upcoming Events',
                'news_section_title' => 'Latest News',
                'events_link_text' => 'Check All Upcoming Events',
                'news_link_text' => 'Read All News & Updates',
                'overview_label' => 'School overview',
                'overview_fallback' => 'An outreach of Africa New Life Ministries uniquely set apart to offer Christ-centred education aimed at developing Christian character and Christian leadership.',
                'overview_link_text' => 'More About Our School',
                'curriculum_label' => 'Curriculum overview',
                'curriculum_intro' => 'Registered with AEE in South Africa and with Rwanda Education Board (REB), NLA delivers the Accelerated Christian Education curriculum with biblical integration and mastery-based learning.',
                'curriculum_subtitle' => 'Raising The Next Level Leaders For The Kingdom Of God',
                'curriculum_pillars' => [
                    ['title' => 'Biblical Foundation', 'description' => 'Biblical principles and character training are integrated into the study of every subject.'],
                    ['title' => 'Mastery Learning', 'description' => 'Students capture every concept through individualised study — advancing only when mastery is demonstrated.'],
                    ['title' => 'Leadership Development', 'description' => 'A nurturing Christian environment that helps every child reach their full potential in every area of life.'],
                ],
                'curriculum_link_text' => 'Read More',
                'programs_label' => 'Programs',
                'programs_title' => 'Grade 7 & 8 Programs',
                'programs_subtitle' => 'Middle-school ACE learning paths designed for adolescents ready to lead — academically, spiritually, and socially.',
                'programs_link_text' => 'View all programs',
                'programs_card_fallback' => 'ACE-aligned middle school learning for this grade level.',
                'facilities_title' => 'School Facilities',
                'events_section_title' => 'School Activities',
                'news_section_title' => 'Latest News',
                'events_link_text' => 'View all school activities',
                'news_link_text' => 'Read all news & updates',
                'why_choose_label' => 'Why choose us',
                'why_choose_title' => 'Why Choose Us',
                'why_choose_empty' => 'Edit Why Choose Us cards in Admin → Settings to highlight what makes NLA special.',
                'explore_label' => 'Discover more',
                'explore_title' => 'Explore Our School',
                'explore_subtitle' => 'Take a closer look at academics, campus facilities, and the activities that shape every day at NLA.',
                'explore_link_text' => 'Learn more',
                'explore_cards' => [
                    ['key' => 'academics', 'title' => 'Academics', 'description' => 'Learn about ACE and our mastery-based Christian curriculum.', 'url' => '/academics/about-ace'],
                    ['key' => 'facilities', 'title' => 'Facilities', 'description' => 'Discover boarding, labs, and spaces designed for learning and growth.', 'url' => '/facilities'],
                    ['key' => 'activities', 'title' => 'School Activities', 'description' => 'Swimming, academic trips, hiking, arts, and more beyond the classroom.', 'url' => '/school-activities'],
                ],
                'news_label' => 'Campus life',
                'news_title' => 'Life at NLA',
                'news_empty' => 'Photos will appear here once added in Admin → Gallery.',
                'show_cta' => '1',
            ],
            'about' => [
                'overview_label' => 'School overview',
                'overview_fallback' => 'New Life Leadership Academy is an outreach of Africa New Life Ministries uniquely set apart to offer Christ-centred education aimed at developing Christian character and Christian leadership. As a Christian school, we provide a nurturing environment that helps every child reach his or her full potential in every area of life.',
                'mission_vision_title' => 'Our mission & vision',
                'core_values_title' => 'Why Choose Us',
                'core_value_cards' => self::whyChooseCards(),
                'history_title' => 'Our history',
                'history_intro' => 'How New Life Leadership Academy was established as an outreach of Africa New Life Ministries.',
                'history_body' => '<p>New Life Leadership Academy is an outreach of Africa New Life Ministries, uniquely set apart to offer Christ-centred education aimed at developing Christian character and Christian leadership.</p><p>As a Christian school, we provide a nurturing environment that helps every child reach his or her full potential in every area of life — academically, spiritually, and as emerging leaders.</p>',
                'staff_label' => 'Our team',
                'staff_title' => 'Our Staff',
                'staff_subtitle' => 'Educators and leaders who guide learners through ACE mastery learning and character formation.',
                'staff_empty' => 'Staff profiles will appear here once added in Admin → Leadership.',
                'affiliate_label' => 'New Life family',
                'affiliate_title' => 'Our Schools',
                'affiliate_subtitle' => 'Campuses and affiliate schools under Africa New Life Ministries.',
                'affiliate_empty' => 'Add affiliate schools in Admin → Settings when you are ready to list other campuses.',
                'inquire_label' => 'Contact us',
                'inquire_title' => 'Send us a message',
                'inquire_subtitle' => 'General questions, admissions, partnerships — or schedule a visit to our campus.',
                'enroll_cta_title' => 'Ready to enrol?',
                'enroll_cta_text' => 'Start your child\'s registration online and take the next step toward Christ-centred ACE education.',
                'enroll_primary_btn' => 'Register your child',
                'enroll_secondary_btn' => 'Admissions info',
            ],
            'academics' => [
                'about_ace' => [
                    'title' => 'About ACE',
                    'intro' => 'The Accelerated Christian Education (A.C.E.) program is individualised and designed to allow each student to work at his or her own level of achievement, which may vary from subject to subject.',
                    'body' => '<p>Accelerated Christian Education has a system that negates the need to repeat a grade or advance to more difficult material without mastering a subject. Through individualised PACEs (Packets of Accelerated Christian Education), students progress at their performance level after demonstrating mastery.</p><h3>Our Curriculum</h3><p>The school is registered with AEE in South Africa under the name New Life Leadership Academy and with Rwanda Education Board (REB). This registration gives NLA eligibility to use the ACE curriculum and an account on which to order PACEs, and also eligibility to operate in Rwanda.</p><p>Conventional school systems fail when they force a struggling student to repeat an entire grade or advance a student without having grasped course concepts. Some of these students graduate from high school without basic grammar, math, or other academic skills.</p><p>At NLA, biblical principles and character training are integrated into every subject. Mastery learning ensures students capture every concept regardless of their speed through individualised studying — preparing them for academic excellence and Christian leadership.</p>',
                ],
                'diagnostic_test' => [
                    'title' => 'ACE Diagnostic Test',
                    'intro' => 'Each student entering the A.C.E. program is given diagnostic tests to determine skill and concept mastery. The free aceconnect Diagnostic Test helps identify learning gaps so the right PACEs can be prescribed.',
                    'body' => '<p>Through the free aceconnect Diagnostic Test, students are tested based on what they have learned. Learning gaps — subject concepts the student may have missed — are documented after the student takes the test. When weak areas are evident from the testing, the appropriate gap PACEs (Packets of Accelerated Christian Education) are prescribed to strengthen specific weaknesses. After completing the gap PACEs, the student progresses at their performance level. If the student demonstrates mastery at all levels of testing, they have the ability to function at his chronological grade level.</p><p>The tests assist the evaluator in determining the student\'s academic needs in each subject. After the student completes the testing, he is given a curriculum that meets and challenges him at his performance level.</p><h3>Four academic areas tested</h3><ul><li>Math (Levels 1–9)</li><li>English (Levels 1–8)</li><li>Reading comprehension for science, social studies, Bible Reading, and Literature and Creative Writing (Levels 1–8)</li><li>Spelling (Levels 2–9)</li></ul><p>These tests cover basic skills normally mastered before high school. The aceconnect Diagnostic Test is free and requires one-time registration for your entire school or homeschool.</p><p>To schedule a diagnostic test at New Life Leadership Academy, contact our admissions office or complete the online registration form.</p>',
                ],
                'tuition_fees' => [
                    'title' => 'Tuition & Fees',
                    'intro' => 'Transparent fee structure for students at New Life Leadership Academy, including curriculum materials, boarding options, and co-curricular activities.',
                    'body' => '<p>Tuition fees cover ACE curriculum materials (PACEs), classroom instruction, access to facilities, and co-curricular activities such as Art, Computer, Library, Drama, Music, P.E., and French. Additional costs may apply for uniforms, transport, and optional enrichment programmes.</p><p>NLA offers unique and comfortable boarding for all students, with the option of students going home for weekends.</p><p>For the current fee schedule, payment plans, and sibling discounts, please contact our admissions office or visit the school during office hours. Bursary and scholarship enquiries are welcome.</p>',
                ],
            ],
            'facilities' => [
                'section_label' => 'Our campus',
                'section_title' => 'Spaces for learning & growth',
                'section_intro' => 'Purpose-built facilities supporting ACE mastery learning, enrichment classes, and comfortable boarding.',
                'empty' => 'Facilities will be listed here. Add them in the admin panel.',
                'cta_title' => 'See our campus in person',
                'cta_text' => 'Parents and partners are welcome to schedule a guided tour.',
                'cta_btn' => 'Schedule a visit',
            ],
            'contact' => [
                'form_label' => 'Contact',
                'form_title' => 'Get in touch',
                'form_subtitle' => 'Questions about ACE programmes, the diagnostic test, boarding, or enrollment? We\'re happy to help.',
                'submission_help' => 'Choose WhatsApp to open a pre-filled message, or Email to send your enquiry to the school.',
                'whatsapp_help' => 'Opens WhatsApp with your message — use a phone number that has WhatsApp.',
                'email_help' => 'Saves your enquiry and emails the school (and a copy to you when possible).',
            ],
            'departments' => [
                'section_label' => 'Programs',
                'section_title' => 'Grade 7 & 8 Programs',
                'section_subtitle' => 'Mastery-based ACE learning with biblical integration — building academic strength, Christian character, and leadership.',
                'card_fallback' => 'ACE-aligned learning with dedicated teachers in a nurturing Christian environment.',
            ],
            'activities' => [
                'section_label' => 'School life',
                'section_title' => 'News, events & activities',
                'section_intro' => 'Swimming, academic trips, hiking, arts, sports, and community moments that enrich life at NLA.',
                'empty' => 'No activities yet. Check back soon.',
            ],
            'gallery' => [
                'section_label' => 'Gallery',
                'section_title' => 'Life at our school',
                'section_subtitle' => 'Classrooms, boarding, sports, celebrations, and everyday moments of Christ-centred learning.',
                'empty' => 'No gallery items yet.',
            ],
            'careers' => [
                'section_label' => 'Join our team',
                'section_title' => 'Careers at NLA',
                'section_intro' => 'We welcome passionate ACE educators and staff who share our mission — developing Christian character and Christian leadership in every child.',
                'body' => '<p>Open positions and application details can be shared here. For now, reach out to learn about teaching and support roles at our school.</p>',
                'cta_title' => 'Interested in joining our team?',
                'cta_btn' => 'Contact us',
            ],
            'leadership' => [
                'section_label' => 'Our team',
                'section_title' => 'Educators & Leaders',
                'section_subtitle' => 'Dedicated staff guiding every student through ACE mastery learning and character formation.',
            ],
            'feedback' => [
                'section_label' => 'Your voice matters',
                'section_title' => 'Share your feedback',
                'section_subtitle' => 'Parents, partners, and community members — we welcome your suggestions to help our school grow.',
            ],
            'registration' => [
                'intro' => 'This form is the first step to registering at {school_name}. New students typically take the ACE Diagnostic Test to determine their starting level in each subject.',
                'academic_levels' => ['Grade 5', 'Grade 6', 'Grade 7'],
                'success_title' => 'Application received',
                'success_message' => 'Thank you for registering. We will be in touch regarding the next steps, including the diagnostic test where applicable.',
                'fallback_sidebar' => 'Registration is now open. Complete the form to start the admission process and learn about the ACE Diagnostic Test.',
                'submission_help' => 'Choose one option. We save your application in our system, then continue via WhatsApp or email.',
                'whatsapp_help' => 'Opens WhatsApp with a summary — you must have WhatsApp on the phone number entered above.',
                'email_help' => 'Emails the school and sends you a receipt. Confirmation of admission will follow soon.',
            ],
        ];
    }

    /**
     * Default "Why Choose Us" feature cards (home + about).
     *
     * @return list<array{name: string, description: string}>
     */
    public static function whyChooseCards(): array
    {
        return [
            [
                'name' => 'International Christian Curriculum',
                'description' => 'Our school offers an International Christian Curriculum — Accelerated Christian Education (ACE).',
            ],
            [
                'name' => 'Biblical Integration',
                'description' => 'Integration of Biblical Principles and Character training in the study of every subject.',
            ],
            [
                'name' => 'Mastery Learning',
                'description' => 'Ensuring students capture every concept regardless of their speed through individualised studying.',
            ],
            [
                'name' => 'Co-curricular Activities',
                'description' => 'Special co-curricular activities such as swimming, academic trips, hiking, and more.',
            ],
            [
                'name' => 'Enrichment Classes',
                'description' => 'We offer Art, Computer, Library, Drama, Music, P.E. and French classes.',
            ],
            [
                'name' => 'Comfortable Boarding',
                'description' => 'Unique and comfortable boarding for all students, with the option of going home for weekends.',
            ],
            [
                'name' => 'Leadership Development',
                'description' => 'Intentional formation of Christian character and Christian leadership in every student.',
            ],
        ];
    }

    public static function merge(array $defaults, ?array $stored): array
    {
        if (empty($stored)) {
            return $defaults;
        }

        return array_replace_recursive($defaults, $stored);
    }

    public static function for(?WebsiteSetting $settings): array
    {
        $stored = $settings?->page_sections;
        if (is_string($stored)) {
            $stored = json_decode($stored, true);
        }

        return self::merge(self::defaults(), is_array($stored) ? $stored : []);
    }

    public static function get(?WebsiteSetting $settings, string $path, mixed $default = null): mixed
    {
        $data = self::for($settings);

        foreach (explode('.', $path) as $segment) {
            if (! is_array($data) || ! array_key_exists($segment, $data)) {
                return $default;
            }
            $data = $data[$segment];
        }

        return $data;
    }

    public static function replacePlaceholders(string $text, ?WebsiteSetting $settings): string
    {
        $school = $settings?->company_name ?? config('app.name');

        return str_replace('{school_name}', $school, $text);
    }

    public static function hasRichTextContent(?string $html): bool
    {
        if ($html === null || trim($html) === '') {
            return false;
        }

        $text = html_entity_decode(strip_tags(str_replace(
            ['<br>', '<br/>', '<br />', '&nbsp;'],
            ' ',
            $html
        )));

        return trim(preg_replace('/\s+/u', ' ', $text)) !== '';
    }
}
