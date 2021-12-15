<?php

namespace App\Http\Traits;

use App\Models\Draft;
use App\Models\MasterDraftHeader;
use App\Models\Role;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Endroid\QrCode\Label\Alignment\LabelAlignmentCenter;
use Endroid\QrCode\Label\Font\NotoSans;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeMargin;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\Storage;
use PDF;

trait DraftTrait
{
    public function setDraftDocumentPdf($id, $verifyCode = null)
    {
        $verifyCode = substr(sha1(uniqid(mt_rand(), TRUE)), 0, 10);
        $draft  = Draft::where('NId_Temp', $id)->firstOrFail();
        $header = MasterDraftHeader::where('GRoleId', $draft->createdBy->role->GRoleId)->first();

        $carbonCopy = [];
        if ($draft->RoleId_Cc) {
            $explodeCarbonCopy = explode(',', $draft->RoleId_Cc);
            if (!empty($explodeCarbonCopy)) {
                $carbonCopy = Role::whereIn('RoleId', $explodeCarbonCopy)->get();
            } else {
                $carbonCopy = Role::where('RoleId', $explodeCarbonCopy[0])->first();
            }
        }

        $generateQrCode = ($verifyCode) ? $this->generateQrCode($id) : null;
        $pdf = PDF::loadView('pdf.outboxkeluar', compact('draft', 'header', 'carbonCopy', 'generateQrCode', 'verifyCode'));
        return $pdf->stream();
    }

    /**
     * generateQrCode
     *
     * @param  mixed $id
     * @return void
     */
    public function generateQrCode($id)
    {
        // Create QR code
        $result = Builder::create()
            ->writer(new PngWriter())
            ->writerOptions([])
            ->data($id)
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(new ErrorCorrectionLevelHigh())
            ->size(500)
            ->margin(0)
            ->roundBlockSizeMode(new RoundBlockSizeModeMargin())
            ->logoPath(public_path('images/logo-jabar.jpg'))
            ->logoResizeToWidth(150)
            ->build();

        header('Content-Type: '.$result->getMimeType());
        $fileName = $id . '.png';
        Storage::disk('local')->put($fileName, $result->getString());

        return $fileName;
    }
}
