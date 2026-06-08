<?php

namespace Modules\RequisitionSystem\Tests\Unit\Models;

use Modules\RequisitionSystem\Models\Address;
use Modules\RequisitionSystem\Models\Approval;
use Modules\RequisitionSystem\Models\Attachment;
use Modules\RequisitionSystem\Models\Bank;
use Modules\RequisitionSystem\Models\ConversionRate;
use Modules\RequisitionSystem\Models\CostCenter;
use Modules\RequisitionSystem\Models\Country;
use Modules\RequisitionSystem\Models\Currency;
use Modules\RequisitionSystem\Models\Item;
use Modules\RequisitionSystem\Models\Pipeline;
use Modules\RequisitionSystem\Models\Requisition;
use Modules\RequisitionSystem\Models\Stage;
use Modules\RequisitionSystem\Models\Status;
use Modules\RequisitionSystem\Models\Supplier;
use Modules\RequisitionSystem\Models\SupplierBank;
use Modules\Auth\Models\User;
use Modules\RequisitionSystem\Models\UserStage;
use PHPUnit\Framework\TestCase;
use Tests\Support\AssertsModelConfiguration;

class RequisitionSystemModelsTest extends TestCase
{
    use AssertsModelConfiguration;

    public function test_address_model_configuration(): void
    {
        $this->assertModelFillable(Address::class, [
            'supplier_id',
            'street',
            'city',
            'district',
            'postal_code',
            'country_id',
        ]);
    }

    public function test_approval_model_configuration(): void
    {
        $this->assertModelFillable(Approval::class, [
            'requisition_id',
            'user_id',
            'comments',
        ]);
        $this->assertModelCastsInclude(Approval::class, [
            'signed_at' => 'datetime:M d, Y',
        ]);
    }

    public function test_attachment_model_configuration(): void
    {
        $this->assertModelFillable(Attachment::class, [
            'file_name',
            'file_path',
            'uploaded_by',
            'requisition_id',
            'supplier_id',
        ]);
        $this->assertModelCastsInclude(Attachment::class, [
            'uploaded_at' => 'datetime:M d, Y',
        ]);
    }

    public function test_bank_model_configuration(): void
    {
        $this->assertModelFillable(Bank::class, ['name']);
    }

    public function test_conversion_rate_model_configuration(): void
    {
        $this->assertModelFillable(ConversionRate::class, ['rate', 'currency_id']);
    }

    public function test_cost_center_model_configuration(): void
    {
        $this->assertModelFillable(CostCenter::class, ['name', 'type']);
    }

    public function test_country_model_configuration(): void
    {
        $this->assertModelFillable(Country::class, ['name']);
    }

    public function test_currency_model_configuration(): void
    {
        $this->assertModelFillable(Currency::class, ['name', 'symbol']);
    }

    public function test_item_model_configuration(): void
    {
        $this->assertModelFillable(Item::class, [
            'description',
            'quantity',
            'line_item_number',
            'unit_cost',
            'total',
            'comments',
            'requisition_id',
        ]);
    }

    public function test_pipeline_model_configuration(): void
    {
        $this->assertModelFillable(Pipeline::class, ['name']);
    }

    public function test_requisition_model_configuration(): void
    {
        $this->assertModelFillable(Requisition::class, [
            'number',
            'cost_center_id',
            'supplier_id',
            'status_id',
            'currency_id',
            'conversion_rate',
            'total',
            'stage_id',
        ]);
        $this->assertModelCastsInclude(Requisition::class, [
            'date_prepared' => 'datetime:M d, Y',
        ]);
    }

    public function test_stage_model_configuration(): void
    {
        $this->assertModelFillable(Stage::class, ['name', 'pipeline_id']);
    }

    public function test_status_model_configuration(): void
    {
        $this->assertModelFillable(Status::class, ['name']);
    }

    public function test_supplier_model_configuration(): void
    {
        $this->assertModelFillable(Supplier::class, [
            'name',
            'contact_person',
            'phone_number',
            'email',
            'TIN',
        ]);
    }

    public function test_supplier_bank_model_configuration(): void
    {
        $this->assertModelFillable(SupplierBank::class, [
            'supplier_id',
            'bank_id',
            'account_number',
            'account_name',
            'address',
        ]);
    }

    public function test_user_model_configuration(): void
    {
        $this->assertModelFillable(User::class, [
            'name',
            'email',
            'type',
            'cost_center_id',
        ]);
    }

    public function test_user_stage_model_configuration(): void
    {
        $this->assertModelFillable(UserStage::class, ['user_id', 'stage_id']);
    }
}
