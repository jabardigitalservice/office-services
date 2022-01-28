<td style="width: 31px; vertical-align: top;">:</td>
<td style="vertical-align: top;" colspan="3">
    @if ($draft->type_naskah == 'XxJyPn38Yh.1')
        SURAT
    @elseif ($draft->type_naskah == 'XxJyPn38Yh.2')
        SURAT UNDANGAN
    @elseif ($draft->type_naskah == 'XxJyPn38Yh.3')
        SURAT PANGGILAN
    @endif
    @if ($draft->TtdText == 'PLT')
        Plt. {!! $draft->reviewer->role->RoleName !!},
    @elseif ($draft->TtdText == 'PLH')
        Plh. {!! $draft->reviewer->role->RoleName !!},
    @elseif ($draft->TtdText2 == 'Atas_Nama')
        {!! $draft->approver->role->RoleName !!},
    @else
        {!! $draft->reviewer->role->RoleName !!},
    @endif
    <table class="table-collapse no-padding-table" style="text-align: justify">
        <tr>
            <td style="width: 80px; vertical-align: top;">NOMOR</td>
            <td style="width: 15px; vertical-align: top;">:</td>
            <td style="vertical-align: top;">
                @if ($draft->nosurat != null)
                    {{ $draft->nosurat }}
                @else
                    .../{{ $draft->classification->ClCode; }}/{{ $draft->RoleCode; }}
                @endif
            </td>
        </tr>
        <tr>
            <td style="width: 80px; vertical-align: top;">TANGGAL</td>
            <td style="width: 15px; vertical-align: top;">:</td>
            @php
                $defaultStringDate = (($draft->Ket != 'outboxnotadinas') ? 'Tempat / ' : '') . 'Tanggal / Bulan / Tahun';
            @endphp
            <td style="vertical-align: top;">{{ ($generateQrCode) ? $draft->lokasi . ', ' . parseSetLocaleDate($draft->TglReg, 'id', 'd F Y') : $defaultStringDate; }}</td>
        </tr>
        <tr>
            <td style="width: 80px; vertical-align: top;">PERIHAL</td>
            <td style="width: 15px; vertical-align: top;">:</td>
            <td style="vertical-align: top;"> <?= $draft->Hal; ?></td>
        </tr>
    </table>
</td>
