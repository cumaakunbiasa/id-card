<?php

namespace App\Services;

use ZipArchive;

class WorkerWorkbook
{
    /**
     * Create a minimal XLSX workbook. This path is used when PHP ZipArchive
     * is installed; createCsv() is the Excel-compatible fallback otherwise.
     */
    public function create(iterable $rows): string
    {
        if (!class_exists(ZipArchive::class)) {
            throw new \RuntimeException('Ekstensi PHP zip tidak tersedia untuk membuat XLSX.');
        }

        $path = tempnam(sys_get_temp_dir(), 'mitra-xlsx-');
        $sheet = tmpfile();

        if ($path === false || $sheet === false) {
            if (is_resource($sheet)) {
                fclose($sheet);
            }

            if (is_string($path)) {
                @unlink($path);
            }

            throw new \RuntimeException('File sementara tidak dapat dibuat.');
        }

        $zip = null;

        try {
            $this->writeAll($sheet, '<?xml version="1.0" encoding="UTF-8"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>');

            $rowNumber = 0;

            foreach ($rows as $row) {
                $rowNumber++;
                $this->writeAll($sheet, '<row r="' . $rowNumber . '">');

                foreach (array_values($row) as $index => $value) {
                    $column = '';
                    $number = $index + 1;

                    while ($number > 0) {
                        $number--;
                        $column = chr(65 + $number % 26) . $column;
                        $number = intdiv($number, 26);
                    }

                    $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', (string) $value) ?? '';
                    $escaped = htmlspecialchars(
                        mb_substr($value, 0, 32767),
                        ENT_XML1 | ENT_QUOTES | ENT_SUBSTITUTE,
                        'UTF-8'
                    );

                    // Explicit inline strings prevent spreadsheet formula execution.
                    $this->writeAll(
                        $sheet,
                        '<c r="' . $column . $rowNumber . '" t="inlineStr"><is><t xml:space="preserve">' . $escaped . '</t></is></c>'
                    );
                }

                $this->writeAll($sheet, '</row>');
            }

            $this->writeAll($sheet, '</sheetData></worksheet>');

            if (!fflush($sheet)) {
                throw new \RuntimeException('Data Excel gagal disiapkan.');
            }

            $zip = new ZipArchive();
            if ($zip->open($path, ZipArchive::OVERWRITE) !== true) {
                throw new \RuntimeException('File XLSX gagal dibuka untuk ditulis.');
            }

            $files = [
                '[Content_Types].xml' => '<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>',
                '_rels/.rels' => '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>',
                'xl/workbook.xml' => '<?xml version="1.0"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Mitra Kerja" sheetId="1" r:id="rId1"/></sheets></workbook>',
                'xl/_rels/workbook.xml.rels' => '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>',
            ];

            foreach ($files as $name => $contents) {
                if (!$zip->addFromString($name, $contents)) {
                    throw new \RuntimeException('Komponen file XLSX gagal ditulis.');
                }
            }

            $sheetPath = stream_get_meta_data($sheet)['uri'] ?? null;
            if (!is_string($sheetPath) || !$zip->addFile($sheetPath, 'xl/worksheets/sheet1.xml')) {
                throw new \RuntimeException('Data lembar kerja XLSX gagal ditambahkan.');
            }

            if (!$zip->close()) {
                $zip = null;
                throw new \RuntimeException('File XLSX gagal diselesaikan.');
            }

            $zip = null;

            return $path;
        } catch (\Throwable $exception) {
            if ($zip instanceof ZipArchive) {
                $zip->close();
            }

            @unlink($path);
            throw $exception;
        } finally {
            fclose($sheet);
        }
    }

    /**
     * Create UTF-8 CSV that opens directly in Excel when ZipArchive is absent.
     * Semicolon separators match common Indonesian Excel regional settings.
     */
    public function createCsv(iterable $rows): string
    {
        $path = tempnam(sys_get_temp_dir(), 'mitra-csv-');

        if ($path === false) {
            throw new \RuntimeException('File CSV sementara tidak dapat dibuat.');
        }

        $handle = fopen($path, 'wb');

        if ($handle === false) {
            @unlink($path);
            throw new \RuntimeException('File CSV sementara tidak dapat dibuka.');
        }

        try {
            $this->writeAll($handle, "\xEF\xBB\xBF");

            foreach ($rows as $row) {
                $cells = array_map(
                    fn ($value): string => $this->safeCsvCell($value),
                    array_values($row)
                );

                if (fputcsv($handle, $cells, ';', '"', '') === false) {
                    throw new \RuntimeException('Baris data gagal ditulis ke file CSV.');
                }
            }

            if (!fflush($handle)) {
                throw new \RuntimeException('File CSV gagal diselesaikan.');
            }

            fclose($handle);

            return $path;
        } catch (\Throwable $exception) {
            fclose($handle);
            @unlink($path);
            throw $exception;
        }
    }

    private function safeCsvCell(mixed $value): string
    {
        $cell = (string) ($value ?? '');

        // Prevent CSV formula injection from user-controlled names and text.
        if (preg_match('/^[\x00-\x20]*[=+\-@]/', $cell) === 1) {
            return "'" . $cell;
        }

        return $cell;
    }

    /** Write the complete string or fail rather than returning a corrupt file. */
    private function writeAll($stream, string $contents): void
    {
        $length = strlen($contents);
        $written = 0;

        while ($written < $length) {
            $result = fwrite($stream, substr($contents, $written));

            if ($result === false || $result === 0) {
                throw new \RuntimeException('Gagal menulis file ekspor.');
            }

            $written += $result;
        }
    }
}
