<?php

namespace App\Http\Controllers\V1;

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
        $draft  = Draft::where('NId_Temp', $id)->firstOrFail();
        $header = MasterDraftHeader::where('GRoleId', $draft->sender->role->GRoleId)->first();

        $carbonCopy = [];
        if ($draft->RoleId_Cc) {
            $explodeCarbonCopy = explode(',', $draft->RoleId_Cc);
            if (!empty($explodeCarbonCopy)) {
                $carbonCopy = Role::whereIn('RoleId', $explodeCarbonCopy)->get();
            } else {
                $carbonCopy = Role::where('RoleId', $explodeCarbonCopy[0])->first();
            }
        }

        // return view('pdf.outboxkeluar', compact('draft', 'header', 'carbonCopy'));
        $pdf = PDF::loadView('pdf.outboxkeluar', compact('draft', 'header', 'carbonCopy'));
        return $pdf->stream();
    }
}
