<?php

namespace App\Services;

use App\Mail\InductionAssignedMail;
use App\Models\Questionnaire;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class InductionAssignmentService
{
    /**
     * Only inductions authored by this admin are auto-assigned (both at
     * registration and by the daily cron) for either audience type.
     */
    const ADMIN_ID = 292;

    /**
     * Assign every induction of the given audience type ("staff" or
     * "contractor") to a user, skipping anything already assigned, and
     * email the user about whatever is newly assigned.
     *
     * Called at the moment a staff account is registered (by itself, or by
     * an admin creating a "Staffoo staff" record) and at the moment a
     * contractor account is registered, so each new account only ever gets
     * the inductions meant for its audience. Also called once a day by
     * app:assign-daily-inductions so inductions created *after* a user
     * already has an account still reach them.
     *
     * @return \App\Models\Questionnaire[] the inductions newly assigned on this call
     */
    public static function assignToUser(int $userId, string $type = 'staff', string $state = 'Victoria'): array
    {
        $inductions = Questionnaire::where('type', $type)
            ->get();

        if ($inductions->isEmpty()) {
            return [];
        }

        $alreadyAssignedIds = DB::table('guard_questionnaire_details')
            ->where('guard_id', $userId)
            ->pluck('questionnaire_id')
            ->toArray();

        $now = Carbon::now();
        $inductionHistoryData = [];
        $guardQuestionnaireDetailsData = [];
        $newlyAssigned = [];

        foreach ($inductions as $induction) {
            if (in_array($induction->id, $alreadyAssignedIds)) {
                continue;
            }

            $inductionHistoryData[] = [
                'guard_id' => $userId,
                'induction_id' => $induction->id,
                'state' => $state,
                'read_status' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $guardQuestionnaireDetailsData[] = [
                'guard_id' => $userId,
                'questionnaire_id' => $induction->id,
                'marks' => 0,
                'answers' => null,
                'certificate_path' => null,
                'expiry_date' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $newlyAssigned[] = $induction;
        }

        if (!empty($inductionHistoryData)) {
            DB::table('induction_history')->insert($inductionHistoryData);
        }
        if (!empty($guardQuestionnaireDetailsData)) {
            DB::table('guard_questionnaire_details')->insert($guardQuestionnaireDetailsData);
        }

        if (!empty($newlyAssigned)) {
            self::sendAssignmentEmail($userId, $newlyAssigned);
        }

        return $newlyAssigned;
    }

    /**
     * Email a user that one or more inductions have just been assigned to
     * them. Safe to call directly (e.g. from an admin "assign induction to
     * selected guards" action) as well as from assignToUser() above.
     *
     * @param int|\App\Models\User $user
     * @param \App\Models\Questionnaire[]|array $inductions
     */
    public static function sendAssignmentEmail($user, array $inductions): void
    {
        if (empty($inductions)) {
            return;
        }

        $user = $user instanceof User ? $user : User::find($user);

        if (!$user || empty($user->email)) {
            return;
        }

        try {
            Mail::to($user->email)->queue(new InductionAssignedMail($user, $inductions));
        } catch (\Throwable $e) {
            Log::error('Failed to send induction-assigned email', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Whether a user has cleared every induction assigned to them for the
     * given audience type (marks >= 80 on each). Returns true when nothing
     * is assigned yet, so existing accounts created before this feature
     * aren't retroactively blocked — it only gates once an induction has
     * actually been handed to them.
     */
    public static function hasCompletedRequiredInductions(int $userId, string $type = 'staff'): bool
    {
        $incomplete = DB::table('guard_questionnaire_details')
            ->join('questionnaires', 'questionnaires.id', '=', 'guard_questionnaire_details.questionnaire_id')
            ->where('guard_questionnaire_details.guard_id', $userId)
            ->where('questionnaires.type', $type)
            ->where('guard_questionnaire_details.marks', '<', 80)
            ->exists();

        return !$incomplete;
    }
}
