<?php

namespace App\Excel;

use App\Support\XlsxWriter;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Delivery instruction as an Excel sheet: the same rows as the PDF, one per requirement
 * with the part repeated on each one, so it can be filtered and sorted.
 */
final class DeliveryInstruction
{
    /**
     * @param  list<array<string, mixed>>  $parts  from App\Services\SupplierDocuments::deliveryInstructionDetails()
     */
    public function render(array $parts, int $vendor, string $vendorName, CarbonInterface $issued, string $company): string
    {
        $origins = ['PO' => __('Orden de compra'), 'FP' => __('Firme'), 'FR' => __('Pronóstico')];

        $sheet = (new XlsxWriter(__('Instrucción de entrega')))
            ->widths([20, 36, 8, 12, 16, 18, 12, 10])
            ->row([__('Instrucción de entrega')], XlsxWriter::TITLE)
            ->row([$company])
            ->row([__('Proveedor'), "{$vendor} · {$vendorName}"])
            ->row([__('Fecha de emisión'), $issued])
            ->row()
            ->header([
                __('Número de parte'), __('Descripción'), 'SNP', __('Fecha'),
                __('Origen'), __('Orden de compra'), __('Cantidad'), __('Cajas'),
            ]);

        foreach ($parts as $part) {
            foreach ($part['rows'] as $row) {
                $sheet->row([
                    $part['part'],
                    $part['description'],
                    $part['snp'],
                    CarbonImmutable::parse($row['date']),
                    $origins[$row['origin']] ?? $row['origin'],
                    $row['order'],
                    $row['quantity'],
                    $row['boxes'],
                ]);
            }
        }

        return $sheet->render();
    }
}
