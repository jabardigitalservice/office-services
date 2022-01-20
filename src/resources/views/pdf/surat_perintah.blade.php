@extends('pdf.layouts.master')

@section('content')
    <style>
        body {
            font-size: 16px;
        }
    </style>
    <div style="page-break-after: never;">
        <section id="header-section">
            <img style="width: 100%" src="{{ config('sikd.base_path_file') . 'kop/' . $header->Header }}">
        </section>
        <div style="margin-top: 84px"> <!-- Custom condition header for gubernur level -->
            <p style="text-align: center; font-size: 16px;">
                SURAT PERINTAH<br>
                @if (!$draft->nosurat != null)
                    {{ $draft->nosurat }}
                @else
                    NOMOR : .........../{{ $draft->classification->ClCode; }}/{{ $draft->RoleCode; }}
                @endif
            </p>
        </div>
        <div style="margin-top: 18px;">
            <table class="table-collapse has-margin-bottom" width="100%">
                <tr>
                    <td valign="top" style="width: 100px">DASAR</td>
                    <td valign="top" style="width: 8px">:</td>
                    <td valign="top" class="align-justify">
                        {!! $draft->Hal !!}
                    </td>
                </tr>
                <tr>
                    <td valign="top" colspan="3" style="width: 100px; text-align:center;">MEMERINTAHKAN</td>
                </tr>
                <tr>
                    <td valign="top" style="width: 100px">Kepada</td>
                    <td valign="top" style="width: 8px">:</td>
                    <td valign="top" class="align-justify">
                        @php $totalReceivers = count($customData['receivers']) @endphp
                        @forelse ($customData['receivers'] as $index => $value)
                            @php $index++; @endphp
                            <table class="table-collapse no-margin-bottom" width="100%">
                                <tr>
                                    <td rowspan="4" valign="top" style="width: 22px">{{ $index }}.</td>
                                    <td valign="top" style="width: 150px">Nama</td>
                                    <td valign="top" style="width: 8px">:</td>
                                    <td valign="top">{{ $value->PeopleName }}</td>
                                </tr>
                                <tr>
                                    <td valign="top" style="width: 150px">Pangkat/Golongan</td>
                                    <td valign="top" style="width: 8px">:</td>
                                    <td valign="top">{{ $value->Pangkat . ' / ' . $value->Golongan }}</td>
                                </tr>
                                <tr>
                                    <td valign="top" style="width: 150px">NIP</td>
                                    <td valign="top" style="width: 8px">:</td>
                                    <td valign="top">{{ $value->NIP }}</td>
                                </tr>
                                <tr>
                                    <td valign="top" style="width: 150px">Jabatan</td>
                                    <td valign="top" style="width: 8px">:</td>
                                    <td valign="top">
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
                    </td>
                </tr>
                <tr>
                    <td valign="top" style="width: 100px">Untuk</td>
                    <td valign="top" style="width: 8px">:</td>
                    <td valign="top" class="align-justify is-table-content-on-table">
                        {!! $draft->Konten !!}
                    </td>
                </tr>
            </table>
        </div>
    </div>
    <div style="margin: 0px 5px;">
        @include('pdf.layouts.signature')
    </div>
@endsection
