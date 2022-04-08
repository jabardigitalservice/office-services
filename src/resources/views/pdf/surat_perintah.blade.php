@extends('pdf.layouts.master')

@section('content')
    <style>
        body {
            font-size: 16px;
        }
    </style>
    <div style="page-break-after: auto; page-break-inside: auto !important;">
        <section id="header-section">
            <img style="width: 100%" src="{{ config('sikd.base_path_file') . 'kop/' . $header->Header }}">
        </section>
        <div style="margin-top: 84px"> <!-- Custom condition header for gubernur level -->
            <p style="text-align: center; font-size: 16px;">
                SURAT PERINTAH<br>
                @if ($draft->nosurat != null)
                    NOMOR : {{ $draft->nosurat }}
                @else
                    NOMOR : .........../{{ $draft->classification->ClCode; }}/{{ $draft->RoleCode; }}
                @endif
            </p>
        </div>
        <div style="margin-top: 18px;">
            <div class="row has-margin-bottom">
                <div class="column" style="width: 100px">DASAR</div>
                <div class="column" style="width: 13px">:</div>
                <div class="column align-justify" style="width: 490px;">{!! $draft->Hal !!}</div>
            </div>
            <div class="row has-margin-bottom">
                <div class="column" style="width: 100%; text-align: center;">MEMERINTAHKAH</div>
            </div>
            <div class="row has-margin-bottom">
                <div class="column" style="width: 100px">Kepada</div>
                <div class="column" style="width: 13px">:</div>
            </div>
            <div class="align-justify" style="position: relative; left: 120px; top: -30px; width: 490px;">
                @php $totalReceivers = count($customData['receivers']) @endphp
                @forelse ($customData['receivers'] as $index => $value)
                    @php $index++; @endphp
                    <table class="table-collapse no-margin-bottom" width="100%">
                        <tr>
                            <td valign="top" style="width: 22px">{{ $index }}.</td>
                            <td valign="top" style="width: 150px">Nama</td>
                            <td valign="top" style="width: 13px">:</td>
                            <td class="align-justify" valign="top">{{ $value->PeopleName }}</td>
                        </tr>
                        <tr>
                            <td style="width: 22px">&nbsp;</td>
                            <td valign="top" style="width: 150px">Pangkat/Golongan</td>
                            <td valign="top" style="width: 13px">:</td>
                            <td class="align-justify" valign="top">{{ ($value->Pangkat != null && $value->Golongan != null) ? $value->Pangkat . ' / ' . $value->Golongan : '-' }}</td>
                        </tr>
                        <tr>
                            <td style="width: 22px">&nbsp;</td>
                            <td valign="top" style="width: 150px">NIP</td>
                            <td valign="top" style="width: 13px">:</td>
                            <td class="align-justify" valign="top">{{ ($value->NIP != null) ? $value->NIP : '-' }}</td>
                        </tr>
                        <tr>
                            <td style="width: 22px">&nbsp;</td>
                            <td valign="top" style="width: 150px">Jabatan</td>
                            <td valign="top" style="width: 13px">:</td>
                            <td class="align-justify" valign="top">
                                @php
                                    $str = str_replace('Dan', 'dan', $value->PeoplePosition);
                                    $str = str_replace('Uptd', 'UPTD', $str);
                                    $str = str_replace('Dprd', 'DPRD', $str);
                                @endphp
                                {{ Str::title($str) }}
                            </td>
                        </tr>
                    </table>
                @empty
                    -
                @endforelse
            </div>
            <div class="row has-margin-bottom">
                <div class="column" style="width: 100px">Untuk</div>
                <div class="column" style="width: 13px">:</div>
            </div>
            <div class="align-justify" style="position: relative; left: 120px; top: -30px; width: 490px;">
                <div class="is-table-content-on-table">
                    {!! $draft->Konten !!}
                </div>
            </div>
        </div>
    </div>
    <div style="margin: 5px;">
        @include('pdf.layouts.signature')
    </div>
@endsection
