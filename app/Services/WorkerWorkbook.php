<?php
namespace App\Services;
use ZipArchive;
class WorkerWorkbook {
    public function create(iterable $rows): string {
        if (!class_exists(ZipArchive::class)) { throw new \RuntimeException('Aktifkan ekstensi PHP zip untuk ekspor Excel.'); }
        $path = tempnam(sys_get_temp_dir(), 'mitra-xlsx-');
        $sheet = tmpfile();
        if ($path === false || $sheet === false) { throw new \RuntimeException('File sementara tidak dapat dibuat.'); }
        try {
            fwrite($sheet, '<?xml version="1.0" encoding="UTF-8"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>');
            $rowNumber = 0;
            foreach ($rows as $row) {
                $rowNumber++;
                fwrite($sheet, '<row r="'.$rowNumber.'">');
                foreach (array_values($row) as $index => $value) {
                    $column = ''; $n = $index + 1;
                    while ($n > 0) { $n--; $column = chr(65 + $n % 26).$column; $n = intdiv($n, 26); }
                    $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', (string) $value);
                    $escaped = htmlspecialchars(mb_substr($value ?? '', 0, 32767), ENT_XML1 | ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                    // Explicit strings prevent spreadsheet formula execution for user input.
                    fwrite($sheet, '<c r="'.$column.$rowNumber.'" t="inlineStr"><is><t xml:space="preserve">'.$escaped.'</t></is></c>');
                }
                fwrite($sheet, '</row>');
            }
            fwrite($sheet, '</sheetData></worksheet>'); fflush($sheet);
            $zip = new ZipArchive();
            if ($zip->open($path, ZipArchive::OVERWRITE) !== true) { throw new \RuntimeException('Excel gagal dibuat.'); }
            $zip->addFromString('[Content_Types].xml', '<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>');
            $zip->addFromString('_rels/.rels', '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
            $zip->addFromString('xl/workbook.xml', '<?xml version="1.0"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Mitra Kerja" sheetId="1" r:id="rId1"/></sheets></workbook>');
            $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>');
            $zip->addFile(stream_get_meta_data($sheet)['uri'], 'xl/worksheets/sheet1.xml');
            if (!$zip->close()) { throw new \RuntimeException('Excel gagal disimpan.'); }
            return $path;
        } catch (\Throwable $e) { @unlink($path); throw $e; }
        finally { fclose($sheet); }
    }
}
