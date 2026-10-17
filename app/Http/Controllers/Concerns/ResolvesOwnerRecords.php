<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Asset;
use App\Models\Boat;
use App\Models\BoatInspection;
use App\Models\BoatMaintenance;
use App\Models\Consignment;
use App\Models\CrewAdvance;
use App\Models\Customer;
use App\Models\DalalInvoiceReview;
use App\Models\DalalPayout;
use App\Models\Expense;
use App\Models\Fisher;
use App\Models\FishingEquipment;
use App\Models\FleetDocument;
use App\Models\MonthClosing;
use App\Models\OwnerEmployee;
use App\Models\Payroll;
use App\Models\PayrollLine;
use App\Models\Sale;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vendor;

/**
 * سجلات المالك تُقرأ دائمًا مقيّدةً به: معرّف يخصّ مالكًا آخر = 404، لا
 * تسريب بين الملاك في الويب ولا في الـAPI.
 */
trait ResolvesOwnerRecords
{
    protected function ownedBoat(User $owner, int|string $id): Boat
    {
        return Boat::forOwner($owner)->findOrFail($id);
    }

    /**
     * القارب مقفولًا للتعديل — يُستدعى داخل المعاملة التي تتحقق من نسخته
     * وتكتب، فلا يكتب جهازان بين القراءة والكتابة.
     */
    protected function lockedBoat(User $owner, int|string $id): Boat
    {
        return Boat::forOwner($owner)->lockForUpdate()->findOrFail($id);
    }

    protected function lockedCrew(User $owner, int|string $id): Fisher
    {
        return Fisher::forOwner($owner)->crew()->lockForUpdate()->findOrFail($id);
    }

    protected function lockedCaptain(User $owner, int|string $id): Fisher
    {
        return Fisher::forOwner($owner)->captains()->lockForUpdate()->findOrFail($id);
    }

    protected function ownedMaintenance(User $owner, int|string $id): BoatMaintenance
    {
        return BoatMaintenance::whereHas('boat', fn ($q) => $q->where('owner_id', $owner->id))->findOrFail($id);
    }

    /**
     * كابتن المالك — سجلّ صياد بـ is_captain، لا حساب دخول.
     */
    protected function ownedCaptain(User $owner, int|string $id): Fisher
    {
        return Fisher::forOwner($owner)->captains()->findOrFail($id);
    }

    protected function ownedCrew(User $owner, int|string $id): Fisher
    {
        return Fisher::forOwner($owner)->crew()->findOrFail($id);
    }

    protected function ownedEmployee(User $owner, int|string $id): OwnerEmployee
    {
        return OwnerEmployee::forOwner($owner)->findOrFail($id);
    }

    protected function ownedCustomer(User $owner, int|string $id): Customer
    {
        return Customer::forAccount($owner)->findOrFail($id);
    }

    protected function ownedVendor(User $owner, int|string $id): Vendor
    {
        return Vendor::forOwner($owner)->findOrFail($id);
    }

    protected function ownedTrip(User $owner, int|string $id): Trip
    {
        return Trip::forOwner($owner)->findOrFail($id);
    }

    protected function ownedSale(User $owner, int|string $id): Sale
    {
        return Sale::forSeller($owner)->findOrFail($id);
    }

    protected function ownedConsignment(User $owner, int|string $id): Consignment
    {
        return Consignment::forOwner($owner)->findOrFail($id);
    }

    protected function ownedExpense(User $owner, int|string $id): Expense
    {
        return Expense::forOwner($owner)->findOrFail($id);
    }

    protected function ownedEquipment(User $owner, int|string $id): FishingEquipment
    {
        return FishingEquipment::forOwner($owner)->findOrFail($id);
    }

    protected function ownedAsset(User $owner, int|string $id): Asset
    {
        return Asset::forOwner($owner)->findOrFail($id);
    }

    protected function ownedInspection(User $owner, int|string $id): BoatInspection
    {
        return BoatInspection::forOwner($owner)->findOrFail($id);
    }

    protected function ownedDocument(User $owner, int|string $id): FleetDocument
    {
        return FleetDocument::forOwner($owner)->findOrFail($id);
    }

    /**
     * كابتن أو فرد طاقم — أي سجلّ صياد للمالك (أجره وسلفه وكشفه).
     */
    protected function ownedFisher(User $owner, int|string $id): Fisher
    {
        return Fisher::forOwner($owner)->findOrFail($id);
    }

    protected function ownedPayroll(User $owner, int|string $id): Payroll
    {
        return Payroll::forOwner($owner)->findOrFail($id);
    }

    protected function ownedPayrollLine(Payroll $payroll, int|string $id): PayrollLine
    {
        return $payroll->lines()->findOrFail($id);
    }

    protected function ownedAdvance(User $owner, int|string $id): CrewAdvance
    {
        return CrewAdvance::forOwner($owner)->findOrFail($id);
    }

    protected function ownedMonthClosing(User $owner, int|string $id): MonthClosing
    {
        return MonthClosing::forOwner($owner)->findOrFail($id);
    }

    /**
     * فاتورة دلال فيها مصيد هذا المالك — بمعرّف البيع. بيعٌ لا سطر له فيه 404.
     */
    protected function ownedDalalInvoice(User $owner, int|string $saleId): DalalInvoiceReview
    {
        return DalalInvoiceReview::forOwner($owner)->where('sale_id', $saleId)->with('sale', 'dalal')->firstOrFail();
    }

    protected function ownedDalalPayout(User $owner, User $dalal, int|string $id): DalalPayout
    {
        return DalalPayout::forOwner($owner)->forDalal($dalal)->findOrFail($id);
    }
}
