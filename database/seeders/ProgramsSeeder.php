<?php

namespace Database\Seeders;

use App\Models\ClinicalDepartment;
use Illuminate\Database\Seeder;

class ProgramsSeeder extends Seeder
{
    public function run(): void
    {
        $programs = [
            [
                'name' => 'Grade 7',
                'slug' => 'grade-7',
                'sort_order' => 1,
                'description' => <<<'HTML'
<p>Grade 7 at New Life Leadership Academy marks the beginning of our middle-school journey. Students enter a nurturing, Christ-centred environment where Accelerated Christian Education (ACE) helps every learner master concepts at their own pace.</p>
<p>Through individualised PACEs, biblical integration, and character training in every subject, Grade 7 students grow in academic confidence while developing Christian character and early leadership habits.</p>
<h3>What students experience</h3>
<ul>
<li>Individualised ACE learning across core subjects</li>
<li>Biblical principles and character training woven into every lesson</li>
<li>Enrichment classes such as Art, Computer, Library, Drama, Music, P.E., and French</li>
<li>Co-curricular activities including swimming, academic trips, and hiking</li>
<li>Comfortable boarding with the option to go home on weekends</li>
</ul>
<p>New students typically take the ACE Diagnostic Test so we can place them at the right performance level in each subject and prescribe any gap PACEs needed for mastery.</p>
HTML,
            ],
            [
                'name' => 'Grade 8',
                'slug' => 'grade-8',
                'sort_order' => 2,
                'description' => <<<'HTML'
<p>Grade 8 continues and deepens the ACE mastery pathway at New Life Leadership Academy. Students advance at their performance level, strengthen weak areas through prescribed PACEs, and grow as confident Christian leaders.</p>
<p>As an outreach of Africa New Life Ministries, NLA equips Grade 8 learners for the next stage of education — academically, spiritually, and socially — in a supportive boarding and day-option community.</p>
<h3>What students experience</h3>
<ul>
<li>Mastery-based ACE progress that ensures concepts are captured before advancement</li>
<li>Leadership development through service, responsibility, and character formation</li>
<li>Enrichment in Art, Computer, Library, Drama, Music, P.E., and French</li>
<li>Special co-curricular activities such as swimming, academic trips, and hiking</li>
<li>Unique and comfortable boarding with weekend home options</li>
</ul>
<p>Families are encouraged to Register online and, where needed, Take the Diagnostic Test so each Grade 8 student begins at the right academic level.</p>
HTML,
            ],
        ];

        foreach ($programs as $program) {
            $existing = ClinicalDepartment::where('slug', $program['slug'])->first();

            $payload = [
                'name' => $program['name'],
                'is_active' => true,
                'sort_order' => $program['sort_order'],
            ];

            if (! $existing || blank($existing->description)) {
                $payload['description'] = $program['description'];
            }

            ClinicalDepartment::updateOrCreate(
                ['slug' => $program['slug']],
                $payload
            );
        }

        $this->command?->info('Grade 7 and Grade 8 programs seeded.');
    }
}
