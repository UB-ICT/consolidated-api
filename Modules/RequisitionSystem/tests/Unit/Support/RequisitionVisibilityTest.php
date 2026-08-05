<?php

namespace Modules\RequisitionSystem\Tests\Unit\Support;

use Modules\Auth\Models\User;
use Modules\RequisitionSystem\Models\Requisition;
use Modules\RequisitionSystem\Models\UserStage;
use Modules\RequisitionSystem\Support\RequisitionVisibility;
use Modules\RequisitionSystem\Tests\Support\CreatesRequisitionWorkflowSchema;
use Tests\TestCase;

class RequisitionVisibilityTest extends TestCase
{
    use CreatesRequisitionWorkflowSchema;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpRequisitionWorkflowSchema();
    }

    public function test_stage_assignee_sees_only_pending_requisitions_at_their_stage(): void
    {
        $user = $this->makeStageUser($this->stageIds["Director's Approval"]);

        $visibleId = $this->createTestRequisition(
            $this->stageIds["Director's Approval"],
            $this->statusIds['Pending'],
            2
        );
        $otherStageId = $this->createTestRequisition(
            $this->stageIds['Budget Officer'],
            $this->statusIds['Pending'],
            3
        );
        $draftAtStageId = $this->createTestRequisition(
            $this->stageIds["Director's Approval"],
            $this->statusIds['Draft'],
            2
        );

        $ids = $this->visibleIdsFor($user);

        $this->assertContains($visibleId, $ids);
        $this->assertNotContains($otherStageId, $ids);
        $this->assertNotContains($draftAtStageId, $ids);
    }

    public function test_user_without_stage_or_cost_center_sees_nothing(): void
    {
        $user = $this->makeBareUser();

        $this->createTestRequisition(
            $this->stageIds["Director's Approval"],
            $this->statusIds['Pending'],
            2
        );

        $this->assertSame([], $this->visibleIdsFor($user));
    }

    public function test_user_can_view_matches_stage_queue_rules(): void
    {
        $user = $this->makeStageUser($this->stageIds["Director's Approval"]);

        $visible = Requisition::findOrFail($this->createTestRequisition(
            $this->stageIds["Director's Approval"],
            $this->statusIds['Pending'],
            2
        ));
        $visible->setRelation('status', (object) [
            'id' => $this->statusIds['Pending'],
            'name' => 'Pending',
        ]);

        $hidden = Requisition::findOrFail($this->createTestRequisition(
            $this->stageIds['Budget Officer'],
            $this->statusIds['Pending'],
            3
        ));
        $hidden->setRelation('status', (object) [
            'id' => $this->statusIds['Pending'],
            'name' => 'Pending',
        ]);

        $this->assertTrue(RequisitionVisibility::userCanView($visible, $user));
        $this->assertFalse(RequisitionVisibility::userCanView($hidden, $user));
    }

    /**
     * @return list<int>
     */
    private function visibleIdsFor(User $user): array
    {
        $query = Requisition::query();
        RequisitionVisibility::constrainQuery($query, $user);

        return $query->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    private function makeBareUser(): User
    {
        $user = $this->getMockBuilder(User::class)
            ->onlyMethods(['hasAnyRole'])
            ->getMock();
        $user->method('hasAnyRole')->willReturn(false);
        $user->forceFill([
            'name' => 'Bare',
            'email' => 'bare@ub.edu.bz',
        ]);
        $user->id = '11111111-1111-1111-1111-111111111111';
        $user->exists = true;
        $user->setRelation('costCenters', collect());

        return $user;
    }

    private function makeStageUser(int $stageId): User
    {
        $user = $this->makeBareUser();
        $user->id = '22222222-2222-2222-2222-222222222222';

        UserStage::create([
            'user_id' => $user->id,
            'stage_id' => $stageId,
        ]);

        return $user;
    }
}
