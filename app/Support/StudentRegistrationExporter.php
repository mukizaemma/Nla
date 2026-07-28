<?php

namespace App\Support;

use App\Models\StudentRegistration;
use App\Models\WebsiteSetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentRegistrationExporter
{
    /**
     * @param  Builder<StudentRegistration>  $query
     * @return Collection<int, StudentRegistration>
     */
    public static function collect(Builder $query): Collection
    {
        return $query->orderByDesc('created_at')->get();
    }

    /**
     * @param  Collection<int, StudentRegistration>  $registrations
     */
    public static function excel(Collection $registrations, ?string $from = null, ?string $to = null): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Registrations');

        $headers = [
            'Submitted At',
            'Student First Name',
            'Student Last Name',
            'Academic Level',
            'Date of Birth',
            'Status',
            'Channel',
            'Primary Contact',
            'Contact Name',
            'Contact Email',
            'Contact Phone',
            'Previous School',
        ];

        foreach ($headers as $col => $label) {
            $sheet->setCellValue([$col + 1, 1], $label);
        }

        $row = 2;
        foreach ($registrations as $reg) {
            $sheet->fromArray([
                optional($reg->created_at)?->format('Y-m-d H:i'),
                $reg->student_first_name,
                $reg->student_last_name,
                $reg->academic_level,
                optional($reg->date_of_birth)?->format('Y-m-d'),
                $reg->status ?? 'pending',
                $reg->submission_channel,
                $reg->primary_contact,
                $reg->primaryContactName(),
                $reg->primaryContactEmail(),
                $reg->primaryContactPhone(),
                $reg->previous_school_name,
            ], null, 'A'.$row);
            $row++;
        }

        foreach (range(1, count($headers)) as $col) {
            $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
        }

        $filename = self::filename('xlsx', $from, $to);

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * @param  Collection<int, StudentRegistration>  $registrations
     */
    public static function pdf(Collection $registrations, ?string $from = null, ?string $to = null): \Illuminate\Http\Response
    {
        $schoolName = WebsiteSetting::first()?->company_name ?? config('app.name');

        $pdf = Pdf::loadView('admin.exports.registrations-pdf', [
            'registrations' => $registrations,
            'schoolName' => $schoolName,
            'from' => $from,
            'to' => $to,
            'generatedAt' => now(),
        ])->setPaper('a4', 'landscape');

        return $pdf->download(self::filename('pdf', $from, $to));
    }

    protected static function filename(string $ext, ?string $from, ?string $to): string
    {
        $parts = ['student-registrations'];
        if ($from) {
            $parts[] = $from;
        }
        if ($to) {
            $parts[] = $to;
        }

        return implode('_', $parts).'.'.$ext;
    }
}
