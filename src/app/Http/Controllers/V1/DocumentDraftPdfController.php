<?php

namespace App\Http\Controllers\V1;

use App\Exceptions\CustomException;
use App\Http\Controllers\Controller;
use App\Models\Draft;
use App\Models\MasterDraftHeader;
use App\Models\Role;
use Illuminate\Http\Request;
use Spipu\Html2Pdf\Html2Pdf;
use PDF;

class DocumentDraftPdfController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request, $id)
    {
        try {
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

            $pdf = PDF::loadView('pdf.outboxkeluar', compact('draft', 'header', 'carbonCopy'));
            return $pdf->stream();
        } catch (\Throwable $th) {
            throw new CustomException(
                'Invalid generate pdf. Message : ' . $th->getMessage(),
                'Document can not be generate',
            );
        }
    }
}
