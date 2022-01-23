<?php

namespace App\Http\Controllers\V1;

use App\Exceptions\CustomException;
use App\Http\Controllers\Controller;
use App\Http\Traits\DraftTrait;
use Illuminate\Http\Request;

class DocumentDraftPdfController extends Controller
{
    use DraftTrait;

    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request, $id)
    {
        try {
            return $this->setDraftDocumentPdf($id);
        } catch (\Throwable $th) {
            throw new CustomException(
                'Document can not be generate',
                'Invalid generate pdf. Message : ' . $th->getMessage() . ' on line ' . $th->getLine(),
            );
        }
    }
}
