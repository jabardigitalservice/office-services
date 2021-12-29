
@php
    $fontSizeInBox = '10px;';
    $fontSizeOutBox = '12px;';
    $boxSignature = '0px;';
    $imageOnBoxSignature = '45px;';
    $signatureBoxSize = '300px';
    if ($draft->Ket == 'outboxnotadinas') {
        $signatureBoxSize = '310px;';
        $fontSizeInBox = '11px;';
        $boxSignature = '-62px;';
        $imageOnBoxSignature = '80px;';
    }
@endphp
@if ($draft->Ket == 'outboxnotadinas')
    <style>
        .signature-table td:last-child {
            padding-right: 5px;
        }
    </style>
@endif
<section class="signature-content-section">
    <div style="float:right; width: {{ $signatureBoxSize }} position: relative; left: {{ $boxSignature; }}">
        <p style="text-align: center; font-size: 12px;">
            @if ($draft->TtdText == 'PLT')
                Plt. {!! $draft->reviewer->role->RoleName !!},
            @elseif ($draft->TtdText == 'PLH')
                Plh. {!! $draft->reviewer->role->RoleName !!},
            @elseif ($draft->TtdText2 == 'Atas_Nama')
                a.n.&nbsp;{!! $draft->approver->role->RoleName !!},
                    <br>{!! $draft->reviewer->role->RoleName !!},
            @elseif ($draft->TtdText2 == 'untuk_beliau')
                a.n.&nbsp;{!! $draft->approver->role->RoleName !!},
                    <br>{!! $draft->reviewer->parentRole->RoleName !!},
                    <br>u.b.<br>{!! $draft->reviewer->role->RoleName !!},
            @else
                {!! $draft->reviewer->role->RoleName !!},
            @endif
        </p>
        @if (!$generateQrCode)
            <p style="text-align: center;">PEMERIKSA</p>
        @endif
        <div style="border: 1px solid #000000; font-size: {{ $fontSizeInBox; }}; margin: 0px 6px 0px 20px;">
            <table class="table-collapse no-padding-table signature-table">
                <tr>
                    <td rowspan="4" style="vertical-align: middle; text-align:center">
                        @if ($generateQrCode)
                            <img src="{{ public_path('/images/logo-jabar.jpg') }}" width="55px">
                        @else
                            <img src="{{ public_path('/images/logo-empty.jpg') }}" width="{{ $imageOnBoxSignature }}">
                        @endif
                    </td>
                    <td>Ditandatangani secara elekronik oleh:</td>
                </tr>
                <tr>
                    <td>
                        <div style="margin-bottom: 20px">
                            @if ($draft->TtdText == 'PLT')
                                Plt. {!! $draft->reviewer->role->RoleName !!},
                            @elseif ($draft->TtdText == 'PLH')
                                Plh. {!! $draft->reviewer->role->RoleName !!},
                            @elseif ($draft->TtdText2 == 'Atas_Nama')
                                a.n.&nbsp;{!! $draft->approver->role->RoleName !!},
                                    <br>{!! $draft->reviewer->role->RoleName !!},
                            @elseif ($draft->TtdText2 == 'untuk_beliau')
                                a.n.&nbsp;{!! $draft->approver->role->RoleName !!},
                                    <br>{!! $draft->reviewer->parentRole->RoleName !!},
                                    <br>u.b.<br>{!! $draft->reviewer->role->RoleName !!},
                            @else
                                {!! $draft->reviewer->role->RoleName !!},
                            @endif
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        {{ $draft->Nama_ttd_konsep }}
                    </td>
                </tr>
                <tr>
                    <td>
                        {{ $draft->reviewer->Pangkat }}
                    </td>
                </tr>
            </table>
        </div>
    </div>
    <div class="clearfix"></div>
</section>
