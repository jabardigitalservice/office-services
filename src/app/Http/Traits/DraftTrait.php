<?php

namespace App\Http\Traits;

use App\Models\Draft;
use App\Models\MasterDraftHeader;
use App\Models\People;
use App\Models\Role;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeMargin;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\Storage;
use PDF;

/**
 * Create draft on pdf format.
 */
trait DraftTrait
{
    public function setDraftDocumentPdf($id, $verifyCode = null)
    {
        $draft  = Draft::where('NId_Temp', $id)->firstOrFail();
        $header = MasterDraftHeader::where('GRoleId', $draft->createdBy->role->GRoleId)->first();
        $customData = $this->customData($draft);

        $generateQrCode = ($verifyCode) ? $this->generateQrCode($id) : null;
        $pdf = PDF::loadView($draft->document_template_name, compact('draft', 'header', 'customData', 'generateQrCode', 'verifyCode'));
        if ($draft->Ket == 'outboxsprint') {
            $pdf->setPaper(array(0,0,609.4488,935.433), 'portrait'); //F4 Size
        }
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

        header('Content-Type: ' . $result->getMimeType());
        $fileName = $id . '.png';
        Storage::disk('local')->put($fileName, $result->getString());

        return $fileName;
    }

    /**
     * customData
     *
     * @param  collection $draft
     * @return array
     */
    public function customData($draft)
    {
        $customData = match ($draft->Ket) {
            'outboxnotadinas'       => $this->setDataNotaDinas($draft),
            'outboxkeluar'          => $this->setDataSuratDinas($draft),
            default                 => $this->setDataSuratPerintah($draft),
        };

        return $customData;
    }

    /**
     * setDataSuratDinas
     *
     * @param  collection $draft
     * @return array
     */
    public function setDataNotaDinas($draft)
    {
        $response['carbonCopy'] = $this->getCarbonCopy($draft);
        $response['receivers'] = $this->getReceivers($draft);
        return $response;
    }

    /**
     * setDataSuratDinas
     *
     * @param  collection $draft
     * @return array
     */
    public function setDataSuratDinas($draft)
    {
        $response['carbonCopy'] = $this->getCarbonCopy($draft);
        return $response;
    }

    /**
     * setDataSuratPerintah
     *
     * @param  collection $draft
     * @return array
     */
    public function setDataSuratPerintah($draft)
    {
        $response['receivers'] = $this->getReceivers($draft);
        return $response;
    }

    /**
     * getCarbonCopy
     *
     * @param  collection $draft
     * @return array
     */
    public function getCarbonCopy($draft)
    {
        $carbonCopy = [];
        if ($draft->RoleId_Cc) {
            $explodeCarbonCopy = explode(',', $draft->RoleId_Cc);
            //Query like existing code
            $collection = People::whereIn('PrimaryRoleId', $explodeCarbonCopy)
                                ->where('GroupId', '<>', 8)
                                ->get();
            //Manipulate data for getting latest record from same PrimaryRoleId value
            $carbonCopy = $collection->mapWithKeys(function ($item, $key) {
                return [$item['PrimaryRoleId'] => $item['PeoplePosition']];
            });
        }

        return $carbonCopy;
    }

    public function getReceivers($draft)
    {
        $receivers = [];
        if ($draft->RoleId_To) {
            $explodeReceivers = explode(',', $draft->RoleId_To);
            $receivers = People::whereIn('PeopleId', $explodeReceivers);

            if ($draft->Ket == 'outboxsprint') {
                $receivers = $receivers->orderBy('GroupId', 'ASC')
                                    ->orderBy('Golongan', 'DESC')
                                    ->orderBy('Eselon', 'ASC')
                                    ->orderBy('PrimaryRoleId', 'ASC');
            }

            $receivers = $receivers->get();
        }

        return $receivers;
    }
}
