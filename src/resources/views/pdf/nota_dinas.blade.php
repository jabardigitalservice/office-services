@extends('pdf.layouts.master')

@section('content')
    <style>
        body {
            font-size: 13px;
        }
    </style>
    @if (($draft->lampiran != null && count(array_filter(json_decode($draft->lampiran))) > 0) || $draft->lampiran2 != null || $draft->lampiran3 != null || $draft->lampiran4 != null)
        @php $firstPageBreak = 'always'; @endphp
    @else
        @php $firstPageBreak = 'never'; @endphp
    @endif
    <div style="page-break-after: {{ $firstPageBreak }};">
        <section id="header-section">
            <img style="width: 100%" src="{{ config('sikd.base_path_file') . 'kop/' . $header->Header }}">
        </section>
        <section>
            <div style="margin-top: 44px">
                <p style="font-weight: bold; text-align: center; font-size: 14px;">NOTA DINAS</p>
            </div>
            <div style="margin-top: 19px; padding: 10px;">
                <table class="table-collapse mini-padding-table">
                    <tr>
                        <td valign="top" style="width: 86px">Kepada</td>
                        <td valign="top" style="width: 8px">:</td>
                        <td valign="top">
                            @php $totalReceivers = count($customData['receivers']) @endphp
                            @forelse ($customData['receivers'] as $index => $value)
                                @php $index++; @endphp
                                <table border="0" height="20" width="100" class="table-collapse mini-list-table">
                                    <tr style="width:80px" margin-right="50px" height="20">
                                        @if ($totalReceivers > 1)
                                            <td valign="top" style="text-align: justify; width: 16px;"valign="top">{{ $index }}.</td>
                                        @endif
                                        <td valign="top" style="text-align: justify; width: 545;"valign="top">{{ Str::title($value->PeoplePosition); }}</td>
                                    </tr>
                                </table>
                            @empty
                                -
                            @endforelse
                        </td>
                    </tr>
                    <tr>
                        <td valign="top" style="width: 86px">Dari</td>
                        <td valign="top" style="width: 8px">:</td>
                        <td valign="top">{!! ($draft->reviewer != null) ? Str::title($draft->reviewer->role->RoleName) : '-'; !!}</td>
                    </tr>
                    <tr>
                        <td valign="top" style="width: 86px">Tembusan</td>
                        <td valign="top" style="width: 8px">:</td>
                        <td valign="top">
                            @php $totalCarbonCopy = count($customData['carbonCopy']) @endphp
                            @forelse ($customData['carbonCopy'] as $index => $value)
                                @php
                                    $index++;
                                    $role = Str::title(strtolower($value->PeoplePosition));
                                    $str = str_replace('Dan', 'dan', $role);
                                    $str = str_replace('Uptd', 'UPTD', $str);
                                    $str = str_replace('Dprd', 'DPRD', $str);
                                @endphp

                                <table border="0" height="20" width="100" class="table-collapse mini-list-table">
                                    <tr style="width:80px" margin-right="50px" height="20">
                                        @if ($totalCarbonCopy > 1)
                                            <td valign="top" style="text-align: justify; width: 16px;"valign="top">{{ $index }}.</td>
                                        @endif
                                        <td style="text-align: justify; width: 545;"valign="top">{!! rtrim($str) !!}</td>
                                    </tr>
                                </table>
                            @empty
                                -
                            @endforelse
                        </td>
                    </tr>
                    <tr>
                        <td valign="top" style="width: 86px">Nomor</td>
                        <td valign="top" style="width: 8px">:</td>
                        <td valign="top">
                            @if ($draft->nosurat != null)
                                {{ $draft->nosurat }}
                            @else
                                .../{{ $draft->classification->ClCode; }}/{{ $draft->RoleCode; }}
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td valign="top" style="width: 86px">Tanggal</td>
                        <td valign="top" style="width: 8px">:</td>
                        <td valign="top">{{ parseSetLocaleDate($draft->TglReg, 'id', 'd F Y'); }}</td>
                    </tr>
                    <tr>
                        <td valign="top" style="width: 86px">Sifat</td>
                        <td valign="top" style="width: 8px">:</td>
                        <td valign="top">{!! $draft->classified->SifatName; !!}</td>
                    </tr>
                    <tr>
                        <td valign="top" style="width: 86px">Hal</td>
                        <td valign="top" style="width: 8px">:</td>
                        <td valign="top">{!! $draft->Hal; !!}</td>
                    </tr>
                </table>
            </div>
        </section>
        <div style="margin: 0px 5px;">
            <hr style="background: #000000;">
            <section id="body-content-section" style="line-height: 19px; padding: 12px 7px 7px 7px;">
                {!! $draft->Konten; !!}
            </section>
            @include('pdf.layouts.signature')
        </div>
    </div>
    @if ($draft->lampiran != null && count(array_filter(json_decode($draft->lampiran))) > 0)
        @include('pdf.layouts.attachment.attachment')
    @endif
@endsection
