<?php

namespace Database\Seeders;

use App\Models\Team;
use App\Models\User;
use App\Models\Audit;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    protected $errorTypes = [
        'CALL ENTERING' => [
            'CRITICAL' => ['Incorrect location', 'Missed email request', 'Back charge'],
            'MAJOR' => ['3rd party procedure issue', 'Duplicate case issue', 'Email not sent to AH', 'Incorrect case owner', 'Incorrect customer', 'Incorrect ETA date', 'Incorrect NTE', 'Incorrect PO', 'Incorrect priority code', 'Incorrect Service Category', 'Incorrect skill code', 'Incorrect skill trade', 'Missing/Incorrect case notes', 'Missing/Incorrect verbiage', 'PO not accepted', 'Missing account Team'],
            'MINOR' => ['Incomplete Portal details', 'Incorrect/Missing email attachment', 'Missing Add contact Info', 'Missing Chatter', 'Missing work order', 'Missing/Incorrect case notes', 'Security or Safety Box', 'Special Instruction Not Followed'],
        ],
        'ERG FOLLOW-UP' => [
            'CRITICAL' => ['Back charge', 'Rudeness', 'Incorrect debrief', 'Incorrect work order IVR'],
            'MAJOR' => ['Incorrect Next Action', 'Incorrect Case Owner', 'Incomplete Documentation', 'Missed Follow-Up', 'Customer IVR issue', 'Incorrect recipients', 'Incorrect ETA', 'Service order subtype error', 'End of month procedure', 'Incorrect Repair Details', 'Left WOs in Active cases', 'Incomplete Follow up', 'Inaccurate Documentation'],
            'MINOR' => ['Gameplan', 'Failed to execute bulk ff-up', 'Email to Service Team/AH', 'Invalid Follow-up', 'Call ownership'],
        ],
        'DOCUMENT PROCESSING' => [
            'CRITICAL' => ['Incorrect PO amount', 'Rudeness'],
            'MAJOR' => ['Incorrect document attached', 'Missed to upload PPW', 'Documents not split', 'Reason code'],
            'MINOR' => ['Invalid transfer', 'Incorrect document label', 'Duplicate attachment'],
        ],
        'CUSTOMER SERVICE REP' => [
            'CRITICAL' => ['Incorrect location - dispatch', 'Rudeness', 'Back charge/Unapproved Work'],
            'MAJOR' => ['Email not sent to AH support', 'Incorrect account status', 'Incorrect NTE', 'Incorrect Trade - Dispatch', 'PO not accepted – Portal (IVR)', 'Missed check in/out – (IVR)', 'Missing/incorrect Portal notes', 'Missed to email vendor', 'Missed to email Account team', 'Incomplete Follow up', 'Missed to upload documents', 'Missed to follow special instructions'],
            'MINOR' => ['Incomplete Portal details', 'Incorrect ETA date', 'Missing/Incorrect work order notes', 'Duplicate attachment'],
        ],
        'ACCOUNTS RECEIVABLE/PAYABLE' => [
            'CRITICAL' => ['Incorrect Unit Price', 'Rudeness'],
            'MAJOR' => ['Incorrect document attached', 'Missed to upload paperwork', 'Missing Email', 'Missed/Incorrect address', 'Incorrect invoice number', 'Incorrect invoice date', 'Incorrect Type info'],
            'MINOR' => ['Duplicate attachment'],
        ],
        'CINTAS ACCOUNTS RECEIVABLE' => [
            'CRITICAL' => ['Incorrect Unit Price', 'Rudeness'],
            'MAJOR' => ['Failed to follow R&R'],
            'MINOR' => [
                'Failed to follow correct MLA Pricing',
                'Exceeded QTY Restrictions',
                'Incorrect Total',
                'Incorrect Subtotal',
                'Incorrect Date',
                'Incorrect INV Number',
                'Incorrect Tax Code',
                'Incorrect item quantities in VA01',
                'Incorrect SGST',
                'Incorrect Tax Amount',
                'Industrial Management (Incorrect %)',
                'Invoice doesn\'t match VA01',
                'Overpaid Invoice',
                'No \'REF PO\' keyed in FB60',
                'No data entered in Reference column',
                'Incorrect Service Charge',
                'Paid to the wrong vendor',
                'Incorrect Surcharge Amount',
                'Missing Employees',
                'Tax Not Included',
                'Incorrect Document Number in Completed Invoice Copy',
                'Others',
                'Incorrect Pricing',
                'Did not follow Minimum - Stop Charge',
                'Incorrect Adjustment - did not match the R&R',
                'Missing or Incorrect Details on Text Reference Key',
                'Failed to Include Tax'
            ],
        ],
    ];

    public function run(): void
    {
        // Create teams
        $teams = [
            ['name' => 'TrueSource', 'slug' => 'truesource-team'],
            ['name' => 'SOS', 'slug' => 'sos-team'],
            ['name' => 'Cintas AR', 'slug' => 'cintas-ar-team'],
        ];

        foreach ($teams as $teamData) {
            Team::create($teamData);
        }

        // Create users and associate them with teams
        $users = [

            [
                'name' => 'dev',
                'email' => 'dev.truesource@teamspan.com',
                'password' => Hash::make('password'),
                'user_role' => 'Manager',
                'user_lob' => ['CALL ENTERING', 'ERG FOLLOW-UP', 'DOCUMENT PROCESSING'],
                'audit_create' => true, 'audit_view' => true, 'audit_update' => true, 'audit_delete' => true,
                'pqc_create' => true, 'pqc_view' => true, 'pqc_update' => true, 'pqc_delete' => true,
                'user_create' => true, 'user_view' => true, 'user_update' => true, 'user_delete' => true,
                'teams' => ['TrueSource']
            ],
            [
                'name' => 'dev',
                'email' => 'dev.cintasar@teamspan.com',
                'password' => Hash::make('password'),
                'user_role' => 'Manager',
                'user_lob' => ['CINTAS ACCOUNTS RECEIVABLE'],
                'audit_create' => true, 'audit_view' => true, 'audit_update' => true, 'audit_delete' => true,
                'pqc_create' => true, 'pqc_view' => true, 'pqc_update' => true, 'pqc_delete' => true,
                'user_create' => true, 'user_view' => true, 'user_update' => true, 'user_delete' => true,
                'teams' => ['Cintas AR']
            ],
            [
                'name' => 'dev',
                'email' => 'dev.sos@gmail.com',
                'password' => Hash::make('password'),
                'user_role' => 'Manager',
                'user_lob' => ['CUSTOMER SERVICE REP', 'ACCOUNTS RECEIVABLE/PAYABLE'],
                'audit_create' => true, 'audit_view' => true, 'audit_update' => true, 'audit_delete' => true,
                'pqc_create' => true, 'pqc_view' => true, 'pqc_update' => true, 'pqc_delete' => true,
                'user_create' => true, 'user_view' => true, 'user_update' => true, 'user_delete' => true,
                'teams' => ['SOS']
            ],

        ];

        foreach ($users as $userData) {
            $teamNames = $userData['teams'];
            unset($userData['teams']);

            $user = User::create($userData);

            foreach ($teamNames as $teamName) {
                $team = Team::where('name', $teamName)->first();
                if ($team) {
                    $user->teams()->attach($team);
                }
            }
        }

        // Group users by team for auditing
        $trueSourceUsers = User::whereHas('teams', function ($query) {
            $query->where('name', 'TrueSource');
        })->get()->groupBy('user_role');

        $sosUsers = User::whereHas('teams', function ($query) {
            $query->where('name', 'SOS');
        })->get()->groupBy('user_role');

        $cintasUsers = User::whereHas('teams', function ($query) {
            $query->where('name', 'Cintas AR');
        })->get()->groupBy('user_role');

        // Seed audits
        $this->seedAuditsForTeam($trueSourceUsers, 'TrueSource');
        $this->seedAuditsForTeam($sosUsers, 'SOS');
        $this->seedAuditsForTeam($cintasUsers, 'Cintas AR');
    }

    private function seedAuditsForTeam($teamUsers, $teamName)
    {
        $associates = $teamUsers['Associate'] ?? collect();
        $auditors = ($teamUsers['Manager'] ?? collect())->merge($teamUsers['Auditor'] ?? collect());

        if ($associates->isEmpty() || $auditors->isEmpty()) {
            return;
        }

        $team = Team::where('name', $teamName)->first();

        for ($i = 0; $i < 5; $i++) {  // Create 5 audits per team
            $user = $associates->random();
            $auditor = $auditors->random();
            $lob = $user->user_lob[array_rand($user->user_lob)];
            $errorCategory = ['CRITICAL', 'MAJOR', 'MINOR'][array_rand(['CRITICAL', 'MAJOR', 'MINOR'])];
            $errorTypes = $this->errorTypes[$lob][$errorCategory] ?? [];

            if (empty($errorTypes)) {
                // If no error types for this category, default to MINOR
                $errorCategory = 'MINOR';
                $errorTypes = $this->errorTypes[$lob][$errorCategory] ?? ['General error'];
            }

            $errorType = $errorTypes[array_rand($errorTypes)];

            Audit::create([
                'lob' => $lob,
                'user_id' => $user->id,
                'team_id' => $team->id,
                'aud_auditor' => $auditor->name,
                'aud_date' => Carbon::now()->subDays(rand(0, 30))->format('Y-m-d'),
                'aud_date_processed' => Carbon::now()->subDays(rand(31, 60))->format('Y-m-d'),
                'aud_time_processed' => ['Prime', 'Afterhours'][array_rand(['Prime', 'Afterhours'])],
                'aud_case_number' => 'CASE-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT),
                'aud_audit_type' => ['Internal', 'Client'][array_rand(['Internal', 'Client'])],
                'aud_customer' => 'Customer-' . chr(rand(65, 90)) . rand(100, 999),
                'aud_area_hit' => ['Work Order Level', 'Case Level', 'Portal', 'Emails', 'Others', 'Not Applicable'][array_rand(['Work Order Level', 'Case Level', 'Portal', 'Emails', 'Others', 'Not Applicable'])],
                'aud_error_category' => $errorCategory,
                'aud_type_of_error' => $errorType,
                'aud_source_type' => $lob === 'CALL ENTERING' ? ['System Integration', 'Manual'][array_rand(['System Integration', 'Manual'])] : null,
                'aud_feedback' => 'Sample feedback for audit #' . ($i + 1),
                'aud_status' => ['Pending', 'Disputed', 'Acknowledged'][array_rand(['Pending', 'Disputed', 'Acknowledged'])],
            ]);
        }
    }
}
