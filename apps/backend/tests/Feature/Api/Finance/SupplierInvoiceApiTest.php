<?php

namespace Tests\Feature\Api\Finance;

use App\Mail\Finance\SupplierInvoicePaymentReceiptMail;
use App\Models\DigitalFileModel;
use App\Models\EntityModel;
use App\Models\Finance\SupplierInvoiceModel;
use App\Models\Orders\SupplierOrderModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SupplierInvoiceApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_does_not_allow_paid_status_without_payment_receipt(): void
    {
        $user = User::factory()->create();
        $this->grantPermissions($user, ['supplier-invoices.update']);
        $this->actingAs($user, 'sanctum');

        $supplier = $this->createSupplierEntity();
        $invoice = $this->createSupplierInvoice($supplier->id);

        $response = $this->putJson("/api/v1/supplier-invoices/{$invoice->id}", [
            'status' => 'paid',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['payment_proof_file']);
    }

    public function test_it_allows_paid_status_with_payment_receipt(): void
    {
        Storage::fake('private');
        $user = User::factory()->create();
        $this->grantPermissions($user, ['supplier-invoices.update']);
        $this->actingAs($user, 'sanctum');

        $supplier = $this->createSupplierEntity();
        $invoice = $this->createSupplierInvoice($supplier->id);

        $response = $this->post("/api/v1/supplier-invoices/{$invoice->id}", [
            '_method' => 'PUT',
            'status' => 'paid',
            'payment_proof_file' => UploadedFile::fake()->create('receipt.pdf', 100, 'application/pdf'),
        ]);

        $response->assertOk()->assertJsonPath('data.status', 'paid');
        $this->assertDatabaseHas('supplier_invoices', [
            'id' => $invoice->id,
            'status' => 'paid',
        ]);
    }

    public function test_it_sends_email_when_flag_is_active(): void
    {
        Storage::fake('private');
        Mail::fake();
        $user = User::factory()->create();
        $this->grantPermissions($user, ['supplier-invoices.update']);
        $this->actingAs($user, 'sanctum');

        $supplier = $this->createSupplierEntity('supplier-mail@example.test');
        $invoice = $this->createSupplierInvoice($supplier->id);
        $receiptFile = UploadedFile::fake()->create('receipt.pdf', 120, 'application/pdf');

        $response = $this->post("/api/v1/supplier-invoices/{$invoice->id}", [
            '_method' => 'PUT',
            'status' => 'paid',
            'send_payment_receipt_email' => '1',
            'payment_proof_file' => $receiptFile,
        ]);

        $response->assertOk();
        Mail::assertSent(SupplierInvoicePaymentReceiptMail::class);
        $mailable = Mail::sent(SupplierInvoicePaymentReceiptMail::class)->first();
        $this->assertNotNull($mailable);
        $this->assertTrue($this->mailableHasPaymentReceiptAttachment($mailable));
    }

    public function test_it_uploads_and_downloads_document(): void
    {
        Storage::fake('private');
        $user = User::factory()->create();
        $this->grantPermissions($user, ['supplier-invoices.create', 'supplier-invoices.read', 'digital-files.read']);
        $this->actingAs($user, 'sanctum');

        $supplier = $this->createSupplierEntity();
        $supplierOrder = $this->createSupplierOrder($supplier->id);

        $createResponse = $this->post('/api/v1/supplier-invoices', [
            'number' => 'SI-UPL-001',
            'invoice_date' => '2026-04-20',
            'due_date' => '2026-05-20',
            'supplier_id' => $supplier->id,
            'supplier_order_id' => $supplierOrder->id,
            'total_amount' => '100.00',
            'status' => 'pending_payment',
            'document_file' => UploadedFile::fake()->create('invoice.pdf', 100, 'application/pdf'),
        ]);

        $createResponse->assertCreated();
        $invoiceId = (int) $createResponse->json('data.id');
        $fileId = (int) $createResponse->json('data.files.0.id');
        $filePath = (string) DigitalFileModel::query()->findOrFail($fileId)->file_path;

        $this->assertTrue(Storage::disk('private')->exists($filePath));

        $downloadResponse = $this->get("/api/v1/digital-files/{$fileId}/download");
        $downloadResponse->assertOk();
    }

    public function test_it_creates_supplier_invoice_without_attachments(): void
    {
        $user = User::factory()->create();
        $this->grantPermissions($user, ['supplier-invoices.create']);
        $this->actingAs($user, 'sanctum');

        $supplier = $this->createSupplierEntity();
        $supplierOrder = $this->createSupplierOrder($supplier->id);

        $response = $this->post('/api/v1/supplier-invoices', [
            'number' => 'SI-NOFILE-001',
            'invoice_date' => '2026-04-20',
            'due_date' => '2026-05-20',
            'supplier_id' => $supplier->id,
            'supplier_order_id' => $supplierOrder->id,
            'total_amount' => '100.00',
            'status' => 'pending_payment',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.files', []);
    }

    public function test_it_creates_supplier_invoice_with_payment_proof_file(): void
    {
        Storage::fake('private');
        $user = User::factory()->create();
        $this->grantPermissions($user, ['supplier-invoices.create']);
        $this->actingAs($user, 'sanctum');

        $supplier = $this->createSupplierEntity();
        $supplierOrder = $this->createSupplierOrder($supplier->id);

        $response = $this->post('/api/v1/supplier-invoices', [
            'number' => 'SI-PROOF-001',
            'invoice_date' => '2026-04-20',
            'due_date' => '2026-05-20',
            'supplier_id' => $supplier->id,
            'supplier_order_id' => $supplierOrder->id,
            'total_amount' => '100.00',
            'status' => 'paid',
            'payment_proof_file' => UploadedFile::fake()->create('receipt.pdf', 100, 'application/pdf'),
        ]);

        $response->assertCreated()
            ->assertJsonFragment(['category' => 'supplier-invoice-payment-proof']);

        $invoiceId = (int) $response->json('data.id');
        $this->assertDatabaseHas('digital_files', [
            'fileable_id' => $invoiceId,
            'fileable_type' => SupplierInvoiceModel::class,
            'category' => 'supplier-invoice-payment-proof',
        ]);
    }

    public function test_it_creates_supplier_invoice_with_document_and_payment_proof_files(): void
    {
        Storage::fake('private');
        $user = User::factory()->create();
        $this->grantPermissions($user, ['supplier-invoices.create']);
        $this->actingAs($user, 'sanctum');

        $supplier = $this->createSupplierEntity();
        $supplierOrder = $this->createSupplierOrder($supplier->id);

        $response = $this->post('/api/v1/supplier-invoices', [
            'number' => 'SI-BOTH-001',
            'invoice_date' => '2026-04-20',
            'due_date' => '2026-05-20',
            'supplier_id' => $supplier->id,
            'supplier_order_id' => $supplierOrder->id,
            'total_amount' => '100.00',
            'status' => 'paid',
            'document_file' => UploadedFile::fake()->create('invoice.pdf', 100, 'application/pdf'),
            'payment_proof_file' => UploadedFile::fake()->create('receipt.pdf', 100, 'application/pdf'),
        ]);

        $response->assertCreated();
        $invoiceId = (int) $response->json('data.id');

        $this->assertDatabaseHas('digital_files', [
            'fileable_id' => $invoiceId,
            'fileable_type' => SupplierInvoiceModel::class,
            'category' => 'supplier-invoice-document',
        ]);
        $this->assertDatabaseHas('digital_files', [
            'fileable_id' => $invoiceId,
            'fileable_type' => SupplierInvoiceModel::class,
            'category' => 'supplier-invoice-payment-proof',
        ]);
    }

    public function test_it_deletes_invoice_and_attached_files(): void
    {
        Storage::fake('private');
        $user = User::factory()->create();
        $this->grantPermissions($user, ['supplier-invoices.delete']);
        $this->actingAs($user, 'sanctum');

        $supplier = $this->createSupplierEntity();
        $invoice = $this->createSupplierInvoice($supplier->id);

        $file = DigitalFileModel::query()->create([
            'name' => 'document-test',
            'file_path' => UploadedFile::fake()->create('doc.pdf', 50, 'application/pdf')->store('supplier-invoices', 'private'),
            'mime_type' => 'application/pdf',
            'size' => 51200,
            'category' => 'supplier-invoice-document',
            'uploaded_by' => $user->id,
            'entity_id' => $supplier->id,
            'fileable_id' => $invoice->id,
            'fileable_type' => SupplierInvoiceModel::class,
        ]);

        $this->assertTrue(Storage::disk('private')->exists($file->file_path));

        $response = $this->deleteJson("/api/v1/supplier-invoices/{$invoice->id}");
        $response->assertOk();

        $this->assertDatabaseMissing('supplier_invoices', ['id' => $invoice->id]);
        $this->assertDatabaseMissing('digital_files', ['id' => $file->id]);
        $this->assertFalse(Storage::disk('private')->exists($file->file_path));
    }

    public function test_it_enforces_permissions_for_supplier_invoices_routes(): void
    {
        $withoutPermission = User::factory()->create();
        $this->actingAs($withoutPermission, 'sanctum');

        $forbiddenResponse = $this->getJson('/api/v1/supplier-invoices');
        $forbiddenResponse->assertForbidden();

        $withPermission = User::factory()->create();
        $this->grantPermissions($withPermission, ['supplier-invoices.read']);
        $this->actingAs($withPermission, 'sanctum');

        $allowedResponse = $this->getJson('/api/v1/supplier-invoices');
        $allowedResponse->assertOk();
    }

    private function createSupplierEntity(string $email = 'supplier@example.test'): EntityModel
    {
        return EntityModel::query()->create([
            'number' => 'ENT-'.str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT),
            'nif' => (string) random_int(100000000, 999999999),
            'name' => 'Supplier Test '.random_int(1, 9999),
            'email' => $email,
            'is_supplier' => true,
            'is_client' => false,
            'gdpr_consent' => false,
            'is_active' => true,
        ]);
    }

    private function createSupplierOrder(int $supplierId): SupplierOrderModel
    {
        return SupplierOrderModel::query()->create([
            'number' => 'SO-'.str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT),
            'supplier_id' => $supplierId,
            'order_date' => now()->toDateString(),
            'status' => 'draft',
            'total_amount' => '100.00',
        ]);
    }

    private function createSupplierInvoice(int $supplierId): SupplierInvoiceModel
    {
        return SupplierInvoiceModel::factory()->create([
            'supplier_id' => $supplierId,
            'supplier_order_id' => null,
            'total_amount' => '100.00',
            'status' => 'pending_payment',
        ]);
    }

    /**
     * @param list<string> $permissions
     */
    private function grantPermissions(User $user, array $permissions): void
    {
        foreach ($permissions as $permission) {
            Permission::query()->firstOrCreate([
                'name' => $permission,
                'guard_name' => 'sanctum',
            ]);
        }

        $user->givePermissionTo($permissions);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function readMailableAttachmentsProperty(object $mailable, string $propertyName): array
    {
        $reflection = new \ReflectionClass($mailable);
        if (! $reflection->hasProperty($propertyName)) {
            return [];
        }

        $property = $reflection->getProperty($propertyName);
        $property->setAccessible(true);
        $value = $property->getValue($mailable);

        return is_array($value) ? $value : [];
    }

    private function mailableHasPaymentReceiptAttachment(SupplierInvoicePaymentReceiptMail $mailable): bool
    {
        if (method_exists($mailable, 'toSymfonyMessage')) {
            $email = $mailable->toSymfonyMessage();

            return collect($email->getAttachments())
                ->contains(fn ($attachment): bool => str_contains(strtolower((string) $attachment->getFilename()), 'receipt'));
        }

        // Fallback para versões onde toSymfonyMessage() não está disponível.
        $mailable->build();
        $diskAttachments = $this->readMailableAttachmentsProperty($mailable, 'diskAttachments');
        if ($diskAttachments === []) {
            return false;
        }
        $matchesByName = collect($diskAttachments)->contains(function (array $attachment): bool {
            $asName = strtolower((string) ($attachment['options']['as'] ?? ''));
            $filePath = strtolower((string) ($attachment['file'] ?? ''));

            return str_contains($asName, 'receipt') || str_contains($filePath, 'receipt');
        });

        return $matchesByName || count($diskAttachments) > 0;
    }

}
