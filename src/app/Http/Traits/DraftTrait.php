<?php

namespace App\Http\Traits;

use App\Models\Draft;
use App\Models\MasterDraftHeader;
use App\Models\Role;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelLow;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Label\Label;
use Endroid\QrCode\Logo\Logo;
use Endroid\QrCode\Label\Font\NotoSans;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeMargin;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\Storage;
use PDF;

trait DraftTrait
{
    public function setDraftDocumentPdf($id, $verifyCode = null)
    {
        $verifyCode = substr(sha1(uniqid(mt_rand(), TRUE)), 0, 10); // remove

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

        $generateQrCode = ($verifyCode) ? $this->generateQrCode($id, $verifyCode) : null;

        $pdf = PDF::loadView('pdf.outboxkeluar', compact('draft', 'header', 'carbonCopy', 'generateQrCode', 'verifyCode'));
        return $pdf->stream();
    }

    public function generateQrCode($id, $verifyCode)
    {
        $writer = new PngWriter();
        // Create QR code
        $QrCode = QrCode::create($id)
            ->setEncoding(new Encoding('UTF-8'))
            ->setErrorCorrectionLevel(new ErrorCorrectionLevelLow())
            ->setSize(70)
            ->setMargin(0)
            ->setRoundBlockSizeMode(new RoundBlockSizeModeMargin())
            ->setForegroundColor(new Color(0, 0, 0))
            ->setBackgroundColor(new Color(255, 255, 255));
        // Create generic logo
        $logo = Logo::create(public_path('images/logo-jabar.jpg'))
            ->setResizeToWidth(20);

        $result = $writer->write($QrCode, $logo, null);
        header('Content-Type: '.$result->getMimeType());

        $fileName = $id . '.png';
        Storage::disk('local')->put($fileName, $result->getString());

        return $fileName;
    }
}
