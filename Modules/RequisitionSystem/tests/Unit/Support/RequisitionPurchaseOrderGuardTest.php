<?php

namespace Modules\RequisitionSystem\Tests\Unit\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\Auth\Models\Role;
use Modules\Auth\Models\User;
use Modules\RequisitionSystem\Models\Requisition;
use Modules\RequisitionSystem\Support\GuardsRequisitionPurchaseOrder;
use Modules\RequisitionSystem\Tests\Support\CreatesRequisitionWorkflowSchema;
use Tests\TestCase;

class RequisitionPurchaseOrderGuardTest extends TestCase
{
    use CreatesRequisitionWorkflowSchema;
    use GuardsRequisitionPurchaseOrder {
        canEditPurchaseOrderNumber as public;
        userIsPurchaseOfficer as public;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpRequisitionWorkflowSchema();
        $this->setUpAuthRoleSchema();
    }

    public function test_purchase_officer_can_edit_purchase_order_number_when_approved(): void
    {
        [$user, $requisition] = $this->makePurchaseOfficerWithApprovedRequisition();

        $this->assertTrue($this->userIsPurchaseOfficer($user));
        $this->assertTrue($this->canEditPurchaseOrderNumber($requisition, $user));
    }

    public function test_non_purchase_officer_cannot_edit_purchase_order_number(): void
    {
        [, $requisition] = $this->makePurchaseOfficerWithApprovedRequisition();

        $otherUser = new User([
            'name'   => 'Requester',
            'email'  => 'requester@ub.edu.bz',
            'status' => 'active',
        ]);
        $otherUser->id = '66666666-6666-6666-6666-666666666666';
        $otherUser->exists = true;

        $this->assertFalse($this->userIsPurchaseOfficer($otherUser));
        $this->assertFalse($this->canEditPurchaseOrderNumber($requisition, $otherUser));
    }

    public function test_purchase_officer_cannot_edit_purchase_order_number_before_approval(): void
    {
        [$user, $requisition] = $this->makePurchaseOfficerWithApprovedRequisition(
            $this->statusIds['Pending']
        );

        $this->assertFalse($this->canEditPurchaseOrderNumber($requisition, $user));
    }

    /**
     * @return array{0: User, 1: Requisition}
     */
    private function makePurchaseOfficerWithApprovedRequisition(?int $statusId = null): array
    {
        $roleId = (string) Str::uuid();

        DB::connection('pgsql')->table('roles')->insert([
            'id'          => $roleId,
            'role_name'   => 'purchase-officer',
            'description' => 'Purchase Officer',
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        $user = new User([
            'name'   => 'Purchase Officer',
            'email'  => 'purchase.officer@ub.edu.bz',
            'status' => 'active',
        ]);
        $user->id = '55555555-5555-5555-5555-555555555555';
        $user->exists = true;

        DB::connection('pgsql')->table('user_roles')->insert([
            'user_id' => $user->id,
            'role_id' => $roleId,
        ]);

        $requisitionId = $this->createTestRequisition(
            $this->stageIds['Finance Approval'],
            $statusId ?? $this->statusIds['Approved'],
            5
        );

        $requisition = Requisition::findOrFail($requisitionId);
        $requisition->setRelation('status', (object) [
            'id'   => $statusId ?? $this->statusIds['Approved'],
            'name' => ($statusId ?? $this->statusIds['Approved']) === $this->statusIds['Pending']
                ? 'Pending'
                : 'Approved',
        ]);

        return [$user, $requisition];
    }

    private function setUpAuthRoleSchema(): void
    {
        Schema::connection('pgsql')->dropIfExists('user_roles');
        Schema::connection('pgsql')->dropIfExists('roles');

        Schema::connection('pgsql')->create('roles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('role_name')->unique();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::connection('pgsql')->create('user_roles', function (Blueprint $table) {
            $table->uuid('user_id');
            $table->uuid('role_id');
        });

        Role::unguard();
    }
}
