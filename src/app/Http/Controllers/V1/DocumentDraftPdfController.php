<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\Draft;
use App\Models\MasterDraftHeader;
use App\Models\Role;
use Illuminate\Http\Request;
use Spipu\Html2Pdf\Html2Pdf;


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

        $explodeCarbonCopy = explode(',', $draft->RoleId_Cc);
        if (count($explodeCarbonCopy) > 0) {
            $carbonCopy = Role::whereIn('RoleId', $explodeCarbonCopy)->get();
        } else {
            $carbonCopy = [];
        }

        // return view('pdf.outboxkeluar', compact('draft', 'header', 'carbonCopy'));

        $html2pdf = new Html2Pdf('P', 'A4', 'en', TRUE, 'UTF-8', array(21, 5, 20, 5));
        $html2pdf->pdf->SetTitle($draft->Hal);
        $html2pdf->WriteHTML(view('pdf.outboxkeluar', compact('draft', 'header', 'carbonCopy')));
        $html2pdf->output();

        // $pdf = PDF::loadView('pdf.outboxkeluar', compact('draft', 'header', 'carbonCopy'));
        // return $pdf->stream();

    }
}
